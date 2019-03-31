<?php

namespace Net\Mesh\Mesh\Network;

use Net\Ematos\Mesh\Node\Node;
use Net\Ematos\Node\TypeNode;


class RCNNetwork extends TypeNetwork
{
    public function getFeatureNode($idFeatureNode)
    {
        $featureNode = $this->nodes[$idFeatureNode];
        if (!is_null($featureNode)) {
            return $featureNode;
        } else { // se o featureNode ainda não foi incluido na typeNetwork, obtem toda a estrutura via bd
            try {
                //$typeNetwork = $this;
                $constraintRegion = [];
                $relays = [];
                $featureStructure = $this->graphService->getFeatureStructure($idFeatureNode);
                if (count($featureStructure) == 0) {
                    $nodeFeature = $this->getOrCreateNode($idFeatureNode);
                } else {
                    foreach ($featureStructure as $structure) {
                        $nodeFeature = $this->getOrCreateNode($structure['f']->id, $structure['f']);
                        $nodePool = $this->getOrCreateNode($structure['p']->id, $structure['p']);
                        $nodeRelay = $this->getOrCreateNode($structure['r']->id, $structure['r']);
                        //$nodeValue = $typeNetwork->getOrCreateNode($structure['v']->id, $structure['v']);
                        $nodeValue = $this->getFeatureNode($structure['v']->id);
                        $relays[$structure['r']->id] = $structure['r']->id;
                        $constraintRegion[$structure['r']->id] = $nodeFeature->region;
                        $multiple = (($nodeFeature->category == 'comp') && ($nodeRelay->optional));
                        $this->createLink($nodeValue, $nodeRelay, (object)[
                            'optional' => $nodeRelay->optional,
                            'multiple' => $multiple,
                            'label' => 'rel_value',
                            'head' => $nodeRelay->head
                        ]);
                        $this->createLink($nodeRelay, $nodePool, (object)[
                            'optional' => $nodePool->optional,
                            'multiple' => $multiple,
                            'label' => 'rel_value',
                            'head' => $nodeRelay->head
                        ]);
                        $this->createLink($nodePool, $nodeFeature, (object)[
                            'optional' => $nodePool->optional,
                            'multiple' => $multiple,
                            'label' => 'rel_elementof',
                            'head' => $nodePool->head
                        ]);
                    }
                    // verifica as constraints entre os relays que foram criados
                    $constraints = $this->graphService->getConstraintsByRegion($nodeFeature->region);
                    foreach ($constraints as $constraint) {
                        if ((isset($relays[$constraint['r1']])) && (isset($relays[$constraint['r2']]))) {
                            $typeNodeConstraint = $this->graphService->getNodeById($constraint['c']->type);
                            $nodeConstraint = $this->getOrCreateNode($constraint['c']->id, (object)[
                                'logic' => 'C',
                                'name' => $constraint['c']->name,
                                'type' => $constraint['c']->type,
                                'class' => $constraint['c']->class,
                                'region' => $constraintRegion[$constraint['r1']],//$constraint['c']->region
                            ]);
                            $this->createLinkById($constraint['r1'], $constraint['c']->id, (object)[
                                'optional' => false,
                                'label' => 'rel_argument1',
                                'head' => false
                            ]);
                            $this->createLinkById($constraint['r2'], $constraint['c']->id, (object)[
                                'optional' => true,
                                'label' => 'rel_argument2',
                                'head' => false
                            ]);
                            $this->constraintsByRegion[$nodeConstraint->region][$constraint['r1']][$constraint['r2']][$constraint['c']->id] = 1;
                        }

                    }
                }

                return $nodeFeature;
            } catch (\Exception $e) {
                print_r($e);
                return [];
            }
        }


    }


    public function createAtomNode($atomParams, $values)
    {
        // create feature
        $name = $atomParams['name'];
        $nodeAtom = $this->getOrCreateNode($name, (object)[
            'logic' => 'A',
            'name' => $name,
            'type' => 'cxn',
            'category' => 'cxn',
            'h' => $atomParams['wordIndex'],
            'region' => $name,
            'wordIndex' => $atomParams['wordIndex']
        ]);
        foreach ($values as $i => $value) {
            $nodeValue = $value[0];
            $head = $value[1];
            $nameRelay = $nodeValue->id . '_relay_' . $name;

            // create pools
            // create relays
            $nodeRelay = $this->getOrCreateNode($nameRelay, (object)[
                'logic' => 'O',
                'name' => $nameRelay,
                'type' => 'relay',
                'class' => 'relay',
                'region' => $name,
                'optional' => false,
                'head' => $head
            ]);
            if ($head) {
                $nodePoolHead = $this->getOrCreateNode($name . '_Head_' . $i, (object)[
                    'logic' => 'O',
                    'name' => $name . 'Head',
                    'type' => 'ce',
                    'region' => $name,
                ]);
                $this->createLink($nodePoolHead, $nodeAtom, (object)[
                    'optional' => false,
                    'multiple' => false,
                    'label' => 'rel_elementof',
                    'head' => true
                ]);
                $this->createLink($nodeValue, $nodeRelay, (object)[
                    'optional' => false,
                    'multiple' => false,
                    'label' => 'rel_value',
                    'head' => true
                ]);
                $this->createLink($nodeRelay, $nodePoolHead, (object)[
                    'optional' => false,
                    'multiple' => false,
                    'label' => 'rel_value',
                    'head' => true
                ]);
            } else {
                $nodePoolDep = $this->getOrCreateNode($name . '_Dep_' . $i, (object)[
                    'logic' => 'O',
                    'name' => $name . 'Dep',
                    'type' => 'ce',
                    'region' => $name,
                ]);
                $this->createLink($nodePoolDep, $nodeAtom, (object)[
                    'optional' => false,
                    'multiple' => false,
                    'label' => 'rel_elementof',
                    'head' => false
                ]);
                $this->createLink($nodeValue, $nodeRelay, (object)[
                    'optional' => false,
                    'multiple' => false,
                    'label' => 'rel_value',
                    'head' => false
                ]);
                $this->createLink($nodeRelay, $nodePoolDep, (object)[
                    'optional' => false,
                    'multiple' => false,
                    'label' => 'rel_value',
                    'head' => false
                ]);
            }
        }
    }

    // procura pela features que tenham com um dos values o idTypeNode
    public function getFeatureFromValueIdNetwork($idTypeNode)
    {
        $idFeature = [];
        $idRelayNodes = $this->getIdNodesOutput($idTypeNode);
        foreach ($idRelayNodes as $idRelayNode) {
            $idPoolNodes = $this->getIdNodesOutput($idRelayNode);
            foreach ($idPoolNodes as $idPoolNode) {
                $idFeatureNodes = $this->getIdNodesOutput($idPoolNode);
                foreach ($idFeatureNodes as $id) {
                    $idFeature[] = $id;
                }
            }
        }
        return $idFeature;
    }

    // procura pela features que tenham com um dos values o idTypeNode
    public function getFeatureFromValueId($idTypeNode)
    {
        $idFeature = $this->graphService->getFeatureFromValue($idTypeNode);
        return $idFeature;
    }

    // procura pela features que tenham com um dos values o classTypeNode
    public function getFeatureFromValueClass($classTypeNode)
    {
        $idFeature = $this->graphService->getFeatureFromValueClass($classTypeNode);
        return $idFeature;
    }

    /*

    public function getFeaturesByType($type, $childType)
    {
        $typeNetwork = $this;
        $relays = [];
        $constraintRegion = [];
        $subnet = function ($parent) use ($typeNetwork, &$relays, &$constraintRegion) {
            $nodeParent = $typeNetwork->getOrCreateNode($parent['parent']->id, $parent['parent']);
            $nodeV1 = $typeNetwork->getOrCreateNode($parent['v1']->id);
            $nodeV2 = $typeNetwork->getOrCreateNode($parent['v2']->id);
            $nodeR1 = $typeNetwork->getOrCreateNode($parent['r1']->id, $parent['r1']);
            $nodeR2 = $typeNetwork->getOrCreateNode($parent['r2']->id, $parent['r2']);
            $nodeP1 = $typeNetwork->getOrCreateNode($parent['p1']->id, $parent['p1']);
            $nodeP2 = $typeNetwork->getOrCreateNode($parent['p2']->id, $parent['p2']);
            $relays[$parent['r1']->id] = $parent['r1']->id;
            $relays[$parent['r2']->id] = $parent['r2']->id;
            $constraintRegion[$parent['r1']->id] = $nodeParent->region;
            $typeNetwork->createLink($nodeV1, $nodeR1, (object)[
                'optional' => $nodeR1->optional,
                'label' => 'rel_value',
                'head' => $nodeR1->head
            ]);
            $typeNetwork->createLink($nodeV2, $nodeR2, (object)[
                'optional' => $nodeR2->optional,
                'label' => 'rel_value',
                'head' => $nodeR2->head
            ]);
            $typeNetwork->createLink($nodeR1, $nodeP1, (object)[
                'optional' => $nodeP1->optional,
                'label' => 'rel_value',
                'head' => $nodeP1->head
            ]);
            $typeNetwork->createLink($nodeR2, $nodeP2, (object)[
                'optional' => $nodeP2->optional,
                'label' => 'rel_value',
                'head' => $nodeP2->head
            ]);
            $typeNetwork->createLink($nodeP1, $nodeParent, (object)[
                'optional' => $nodeP1->optional,
                'label' => 'rel_elementof',
                'head' => $nodeP1->head
            ]);
            $typeNetwork->createLink($nodeP2, $nodeParent, (object)[
                'optional' => $nodeP2->optional,
                'label' => 'rel_elementof',
                'head' => $nodeP2->head
            ]);
        };

        try {
            $relays = [];
            $udrel = [];
            $idFeatures = [];
            $valueNodes = $this->getIdNodesByRegion($childType);
            if (count($valueNodes)) {

                $parents = $this->graphService->ListParentByChild($type, 'deprel', $valueNodes);
                foreach ($parents as $parent) {
                    $subnet($parent);
                    $udrel[$parent['parent']->class][$parent['parent']->id] = $parent['parent']->id;
                    $idFeatures[$parent['parent']->id] = $parent['parent']->id;
                }
                $parents = $this->graphService->ListParentByChild($type, 'root', $valueNodes);
                foreach ($parents as $parent) {
                    $subnet($parent);
                    $idFeatures[$parent['parent']->id] = $parent['parent']->id;
                }
                // verifica as constraints entre os relays que foram criados
                $constraints = $this->graphService->getConstraintsByRegion($type);
                foreach ($constraints as $constraint) {
                    if ((isset($relays[$constraint['r1']])) && (isset($relays[$constraint['r2']]))) {
                        $typeNodeConstraint = $this->graphService->getNodeById($constraint['c']->type);
                        $nodeConstraint = $this->getOrCreateNode($constraint['c']->id, (object)[
                            'logic' => 'C',
                            'name' => $constraint['c']->name,
                            'type' => $typeNodeConstraint->type,
                            'class' => $typeNodeConstraint->class,
                            'region' => $constraintRegion[$constraint['r1']],//$constraint['c']->region
                        ]);
                        $this->createLinkById($constraint['r1'], $constraint['c']->id, (object)[
                            'optional' => false,
                            'label' => 'rel_argument1',
                            'head' => false
                        ]);
                        $this->createLinkById($constraint['r2'], $constraint['c']->id, (object)[
                            'optional' => true,
                            'label' => 'rel_argument2',
                            'head' => false
                        ]);
                    }

                }

            }
            return $idFeatures;
        } catch (\Exception $e) {
            print_r($e);
            return [];
        }
    }

    public function getFeaturesUDRelation()
    {
        $idDepRel = [];
        $idValueNodes = $this->getIdNodesByType('deprel');
        foreach ($idValueNodes as $idValueNode) {
            $nodeValue = $this->getNode($idValueNode);
            $depRelNode = $this->getNode($nodeValue->class);
            $idDepRel[$nodeValue->class] = $depRelNode->id;
        }
        $idFeature = [];
        $list = $this->graphService->listUDRelation($idDepRel);
        foreach ($list as $link) {
            $idFeature[$link['rel']] = ['rel' => $link['rel'], 'head' => $link['head'], 'dep' => $link['dep']];
        }
        return $idFeature;
    }

    public function getFeaturesUD1()
    {
        $idFeature = [];
        $idValueNodes = $this->getIdNodesByType('pos');

        foreach ($idValueNodes as $head) {
            foreach ($idValueNodes as $dep) {
                $relations = $this->udService->listRelationsByPOS($head, $dep);

                //$nodeValue = $this->getNode($idValueNode);
                //$depRelNode = $this->getNode($nodeValue->class);
                //$idDepRel[$nodeValue->class] = $depRelNode->id;
            }
        }
        $idFeature = [];
        $list = $this->graphService->listUDRelation($idDepRel);
        foreach ($list as $link) {
            $idFeature[$link['rel']] = ['rel' => $link['rel'], 'head' => $link['head'], 'dep' => $link['dep']];
        }

        return $idFeature;
    }

    public function getLink($nodeSource, $nodeTarget)
    {
        if (is_null($nodeSource) || is_null($nodeTarget)) {
            return null;
        }
        $idSource = $nodeSource->getId();
        $idTarget = $nodeTarget->getId();
        if (!isset($this->links[$idSource][$idTarget])) {
            $list = $this->getLinksInput($idTarget);
            foreach ($list as $link) {
                $this->links[$idSource][$idTarget] = $link;
            }
        }
        return $this->links[$idSource][$idTarget];
    }

    public function getLinkByClass($nodeSource, $nodeTarget)
    {
        if (is_null($nodeSource) || is_null($nodeTarget)) {
            return null;
        }
        $idSource = $nodeSource->getClass();
        $idTarget = $nodeTarget->getId();
        if (!isset($this->links[$idSource][$idTarget])) {
            $list = $this->getLinksInput($idTarget);
            foreach ($list as $link) {
                $this->links[$idSource][$idTarget] = $link;
            }
        }
        return $this->links[$idSource][$idTarget];
    }

    public function getLinksInput($idNode)
    {
        $links = parent::getLinksInput($idNode);
        if (count($links) == 0) {
            $list = $this->graphService->listLinksInput($idNode);
            foreach ($list as $link) {
                $this->createLinkById($link['idSource'], $link['idTarget'], (object)['label' => $link['relation'], 'optional' => $link['optional'], 'head' => $link['head']]);
            }
            $links = parent::getLinksInput($idNode);
        }
        return $links;
    }
    */

}


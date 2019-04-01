<?php

namespace Net\Ematos\Mesh\Network;

use Net\Ematos\Mesh\Structure\ {
    SiteList, Link
};
use Net\Ematos\Mesh\Node\ {
    RelayNode, ConstraintNode
};
use Net\Ematos\Mesh\Node\Constraint\ {
    ConstraintAndNode,
    ConstraintAfterNode,
    ConstraintBeforeNode,
    ConstraintDifferentNode,
    ConstraintDominanceNode,
    ConstraintDominoNode,
    ConstraintHeadNode,
    ConstraintFollowsNode,
    ConstraintMeetsNode,
    ConstraintSameNode,
    ConstraintHasWordNode,
    ConstraintXORNode,
    ConstraintNearNode
};
use Net\Ematos\Mesh\Infra\ {
    GraphViz
};

class TokenNetwork extends Network
{
    public $typeNetwork;



    //public $siteList;

    //public $wordOrder; // posição da palavra na sentença

    public $activations; // número de tokens que já foram instanciados para um dado type
    public $projections; // count for projections of a featureNode

    public $tokensByType; // listas dos tokens instanciados pelo idNodeType
    public $tokensByClass; // listas dos tokens instanciados pela class

    public $statusMemory;
    public $className;
    public $rootNodes;

    public $activation; // incrementado cada vez que um nó é processado

    public $debug;

    public function __construct()
    {
        parent::__construct();
        //$this->siteList = new SiteList();
        $this->activation = 0;
        $this->activations = [];
        $this->projections = [];
        $this->tokensByType = [];
        $this->tokensByClass = [];
        $this->rootNodes = [];
        //$this->wordOrder = 1;
    }

    public function setTypeNetwork(TypeNetwork $typeNetwork)
    {
        $this->typeNetwork = $typeNetwork;
    }

    public function clearAll()
    {
        foreach ($this->nodes as $node) {
            $id = $node->getId();
            $node->clearAll();
            unset($this->nodes[$id]);
        }
        $this->nodes = [];
        $this->nodesByName = [];
        $this->nodesByFullName = [];
        $this->nodesByType = [];
        $this->nodesByClass = [];
        $this->nodesByRegion = [];
        $this->activations = [];
        $this->tokensByType = [];
        $this->tokensByClass = [];
        $this->statusMemory = [];
        $this->rootNodes = [];
        $this->tokens =[];
        $this->tokenLinks =[];
        // $this->siteList = new SiteList();
    }

    public function createNode($id, $params = [])
    {
        $className = strtolower($params['type']) . 'Node';
        $node = $this->container->make($className);
        $node->setId($id);
        $node->setTokenNetwork($this);
        $node->setParams($params);
        $this->addNode($node);
        $node->manager = $this->manager;
        if ($node instanceof NodeRoot) {
            $this->rootNodes[] = $node;
        }
        return $node;
    }

    public function createUniqueToken($params) {
        $params->activation = 1;
        $id = md5(uniqid($params->name));
        $tokenNode = $this->getOrCreateNode($id, $params);
        $tokenNode->debug = $this->debug;
        $tokenNode->typeNode = $tokenNode;
        // $this->siteList->initialize($id);
        if ($params->class != '') {
            $this->pushTokenByClass($params->class, $tokenNode);
        }
        return $tokenNode;
    }

    public function createNodeToken($typeNode, $tokenForProjection = null)
    {
        if (is_null($tokenForProjection)) {
            if (!isset($this->activations[$typeNode->id])) {
                $this->activations[$typeNode->id] = 0;
            }
            $activation = ++$this->activations[$typeNode->id];
            $id = $typeNode->id . '_' . $activation;
        } else {
            $activation = ++$this->projections[$typeNode->id];
            $id = $tokenForProjection->id . '_n_' . $activation;
        }
        $name = $typeNode->getName() . '_' . $activation;
        $params = [
            'type' => $typeNode->type,
            'name' => $name,
            'status' => 'inactive',
            'logic' => $typeNode->logic,
            'class' => $typeNode->class,
            'category' => $typeNode->category,
            //'h' => $typeNode->wordIndex,//$typeNode->h,
            //'d' => $typeNode->wordIndex,//$typeNode->d,
            //'idHead' => $typeNode->idHead,
            //'wordIndex' => $typeNode->wordIndex
        ];
        $tokenNode = $this->getOrCreateNode($id, $params);
        $tokenNode->typeNode = $typeNode;
        $tokenNode->region = ($typeNode->region ?: $this->region);
        $tokenNode->debug = $this->debug;
        //$this->siteList->initialize($id);
        $this->pushTokenByType($typeNode->id, $tokenNode);
        if ($typeNode->class != '') {
            $this->pushTokenByClass($typeNode->class, $tokenNode);
        }
        // inicializa o slot no caso de words
        /*
        if (($typeNode->type == 'word') && ($activation == 1)) {
            $tokenNode->setLayer(0);
            $tokenNode->getSlots()->set($typeNode->wordIndex);
        }
        */
        return $tokenNode;
    }

    public function getLinkFor($sourceNodeToken, $targetNodeToken)
    {
        $link = $this->typeNetwork->getLink($sourceNodeToken->typeNode, $targetNodeToken->typeNode);
        if (!($link instanceof Link)) {
            $link = $this->typeNetwork->getLinkByClass($sourceNodeToken->typeNode, $targetNodeToken->typeNode);
        }
        if (!($link instanceof Link)) {
            $link = $this->typeNetwork->createLinkById($sourceNodeToken->id, $targetNodeToken->id, [
                'label' => 'rel_common',
                'optional' => $sourceNodeToken->optional,
                'head' => $sourceNodeToken->head
            ]);
        }
        return $link;
    }

    /*
    public function createSite($sourceNodeToken, $targetNodeToken, $link = null)
    {
        if ($link == null) {
            $link = $this->getLinkFor($sourceNodeToken, $targetNodeToken);
        }
        $this->siteList->createSite($sourceNodeToken, $targetNodeToken, $link);
    }
    */

    public function pushTokenByClass($class, $nodeToken)
    {
        $this->tokensByClass[$class][] = $nodeToken;
    }

    public function getTokensByClass($class)
    {
        return (isset($this->tokensByClass[$class]) ? $this->tokensByClass[$class] : []);
    }

    public function pushTokenByType($idTypeNode, $nodeToken)
    {
        $this->tokensByType[$idTypeNode][] = $nodeToken;
    }

    public function getTokensByType($idNodeType)
    {
        return (isset($this->tokensByType[$idNodeType]) ? $this->tokensByType[$idNodeType] : []);
    }

    public function getAvailable($idNodeType)
    {
        $available = [];
        $list = $this->getTokensByType($idNodeType);
        foreach ($list as $candidateToken) {
            if ($candidateToken->logic != 'N') { // não é um projection
                $available[] = $candidateToken;
            }
        }
        return $available;
    }

    public function getTokensAvailableByTypeOrClass($idNodeType)
    {
        $available = $this->getAvailable($idNodeType);
        if (count($available) == 0) {
            $list = $this->getTokensByClass($idNodeType);
            foreach ($list as $candidateToken) {
                if ($candidateToken->logic != 'N') { // não é um projection
                    $available[] = $candidateToken;
                }
            }
        }
        return $available;
    }

    /*
     * Build
     */

    public function build($idNode)
    {
        $processed = [];
        $baseNode = $this->typeNetwork->getNode($idNode);
        $idBaseNode = $baseNode->getId();
        $next = [$idBaseNode];
        while (count($next) > 0) {
            $this->dump('= outer =========');
            $nextNodes = [];
            foreach ($next as $idTypeNode) {
                $typeNode = $this->typeNetwork->getNode($idTypeNode);
                $this->dump('- begin ------- ' . $typeNode->name);
                if(!isset($processed[$idTypeNode])) {
                    $baseToken = $this->createTokenById($idTypeNode);
                    $idNextNodes = $this->typeNetwork->getIdNodesOutput($idTypeNode);
                    if (count($idNextNodes)) {
                        foreach ($idNextNodes as $idNextNode) {
                            if (!isset($this->tokens[$idNextNode])) {
                                $nextToken = $this->createTokenById($idNextNode);
                            } else {
                                $nextToken = $this->tokens[$idNextNode];
                            }
                            $this->createTokenLink($baseToken, $nextToken);
                            $nextNodes[$idNextNode] = $idNextNode;
                        }
                    }
                    $this->typeNodes[$idTypeNode] = $idTypeNode;
                    $processed[$idTypeNode] = $idTypeNode;
                }
                $this->dump('- end -------');
            }
            $next = $nextNodes;
            $this->dump('= end outer ========= ' . count($next) . ' next');
        }
    }

    public function createToken($typeNode)
    {
        $id = $typeNode->getId();
        if (isset($this->tokens[$id])) {
            return $this->tokens[$id];
        }
        $tokenNode = $this->createNodeToken($typeNode);
        $this->tokens[$id] = $tokenNode;
        return $tokenNode;
    }

    public function createTokenById($idTypeNode)
    {
        return $this->createToken($this->typeNetwork->getNode($idTypeNode));
    }

    public function createTokenLink($baseToken, $nextToken) {
        $idBaseToken = $baseToken->id;
        $idNextToken = $nextToken->id;
        if ((!isset($this->tokenLinks[$idBaseToken][$idNextToken])) && (!isset($this->tokenLinks[$idNextToken][$idBaseToken]))) {
            $baseToken->createLinkTo($nextToken);
            $this->tokenLinks[$idBaseToken][$idNextToken] = 1;
            $nextToken->createLinkFrom($baseToken);
            $this->tokenLinks[$idNextToken][$idBaseToken] = 1;
        }
    }

    /*
     * Activation
     */

    public function activate($idNode)
    {
        $this->currentLayer = 0;
        $this->currentPhase = 'feature';
        $baseNode = $this->typeNetwork->getNode($idNode);
        $idBaseNode = $baseNode->getId();
        $baseToken = $this->tokens[$idBaseNode];
        $baseToken->status = 'active';
        $baseToken->a = 1;
        $this->nextNodes = $baseToken->fire();
        $end = (count($this->nextNodes) == 0);
        return $end;
    }

    public function activateNext()
    {
        $this->dump('*************************************************************');
        $this->dump('***** activateNext');
        $this->dump('*************************************************************');
        $next = [];
        foreach($this->nextNodes as $nextNode) {
            $nextNodes = $nextNode->process();
            if(count($nextNodes)) {
                foreach($nextNodes as $nextNode) {
                    $next[] = $nextNode;
                }
            }
        }
        $this->nextNodes = $next;
        $end = (count($this->nextNodes) == 0);
        $this->dump('*************************************************************');
        $this->dump('***** end activateNext - more? ' . ($end ? 'no' : 'yes'));
        $this->dump('*************************************************************');
        foreach($this->nextNodes as $nextNode) {
            $this->dump('-> ' . $nextNode->name);
        }
        return $end;
    }


    /*
     * Structure
     */

    public function getStructure()
    {
        $structure = (object)[
            'nodes' => [],
            'links' => [],
            'groups' => [],
            'layers' => []
        ];
        $slots = [];
        $i = 0;
        $validNodes = [];
        $cola = [];
        $regions = [];
        ksort($this->nodes);
        foreach ($this->nodes as $node) {
            //$this->dump('>>>>>>>>>>>>  ' . $node->id . ' - ' . $node->label . ' - ' . $node->type . ' - ' . $node->status . ' -  ' . $node->energy . ' -  ' . $node->a . ' -  ' . $node->idHead);
            $validNodes[$node->getId()] = 1;
            //$cola[$node->getId()] = $i;
            $cola[$node->getId()] = $node->getId();
            if ($node->layer == 2) {
                print_r();
            }
            $structure->nodes[$i] = [
                'index' => $i,
                'id' => $node->getId(),
                'name' => $node->getName(),
                'position' => $node->index,
                'activation' => $node->activation, //round($node->getA(), 5),
                'o' => $node->o,
                'type' => $node->getType(),
                'class' => $node->getClass(),
                'status' => $node->status,
                'phase' => $node->phase,
                'region' => $node->region,
                'logic' => $node->logic,
                'idHead' => $node->idHead,
                'wordIndex' => $node->wordIndex,
                'h' => $node->h,
                'd' => $node->d,
                'w' => $node->w,
                'strSlots' => substr($node->getSlotsStr(), 1),
                'slots' => $node->getSlots(),
                'layer' => $node->layer,
                'group' => $node->group
            ];
            $this->statusMemory[$node->getId()] = $node->status;
            $regions[$node->region] = $node->region;
            $i++;
        }
        //$layers = [];
        //foreach ($regions as $region) {
        //    $layers[$region] = $this->manager->regionNetwork->getLayerByRegion($region);
        //}
        foreach ($this->nodes as $node) {
            foreach ($node->siteList->getOutputSites() as $site) {
                //var_dump($site);
                $idSource = $node->getId();
                $idTarget = $site->idLinkedToken;
                if (isset($cola[$idSource]) && isset($cola[$idTarget])) {
                    $sourceNode = $cola[$idSource];
                    $targetNode = $cola[$idTarget];

                    //if ($site->isCloned()) {
                    //    continue;
                    //}
                    $status = ($site->active() ? 'active' : 'inactive');
                    $structure->links[] = [
                        'source' => $sourceNode,
                        'target' => $targetNode,
                        'label' => $site->label() ?: 'rel_common',
                        'status' => $status,
                        'optional' => $site->optional(),
                        'head' => $site->head()
                    ];
                    $this->statusMemory[md5($idSource . '-' . $idTarget)] = $status;
                }

            }
        }
        $structure->regions = $regions;
        //$structure->layers = $layers;
        return $structure;
    }

    public function getUpdatedStructure()
    {
        $structure = (object)[
            'nodes' => [],
            'links' => []
        ];
        $cola = [];
        $i = 0;
        foreach ($this->nodes as $node) {
            if ($node->status != $this->statusMemory[$node->getId()]) {
                $cola[$node->getId()] = $node->getId();
                $structure->nodes[$i] = [
                    'index' => $i,
                    'id' => $node->getId(),
                    'name' => $node->getName(),
                    'position' => $node->index,
                    'activation' => round($node->getA(), 5),
                    'type' => $node->getType(),
                    'class' => $node->getClass(),
                    'status' => $node->status,
                    'phase' => $node->phase,
                    'region' => $node->region,
                    'logic' => $node->logic,
                    'idHead' => $node->idHead,
                    'wordIndex' => $node->wordIndex,
                    'h' => $node->h,
                    'd' => $node->d,
                    'w' => $node->w,
                    'slots' => $node->getSlotsStr(),
                    'layer' => $node->layer,
                    'group' => $node->group
                ];
                $this->statusMemory[$node->getId()] = $node->status;
                $i++;
            }
        }
        foreach ($this->nodes as $node) {
            foreach ($this->siteList->getOutputSites($node->id) as $site) {
                $idSource = $node->getId();
                $idTarget = $site->idTargetToken;
                //if (isset($cola[$idSource]) && isset($cola[$idTarget])) {
                $status = ($site->active() ? 'active' : 'inactive');
                if ($status != $this->statusMemory[md5($idSource . '-' . $idTarget)]) {
                    //$sourceNode = $cola[$idSource];
                    //$targetNode = $cola[$idTarget];
                    $structure->links[] = [
                        'source' => $idSource,
                        'target' => $idTarget,
                        'label' => $site->label() ?: 'rel_common',
                        'status' => $status,
                        'optional' => $site->optional(),
                        'head' => $site->head()
                    ];
                    $this->statusMemory[md5($idSource . '-' . $idTarget)] = $status;
                }
                //}

            }
        }
        return $structure;
    }

    public function getUpdatedGraph()
    {
        $updatedStructure = $this->getUpdatedStructure();
        $graphviz = new GraphViz($updatedStructure);
        $graphObj = $graphviz->createGraph();
        $graph = [
            'nodes' => $graphObj->graph['nodes'],
            'edges' => $graphObj->graph['edgesFrom']
        ];
        return json_encode($graph);
    }

}


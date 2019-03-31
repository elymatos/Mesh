<?php

namespace Net\Ematos\Mesh\Network;

use Net\Ematos\Mesh\Node\Node;
use Net\Ematos\Mesh\Node\TypeNode;


class TypeNetwork extends LinkNetwork
{
    public function createNode($id, $params)
    {
        $node = new TypeNode($id);
        $node->setParams($params);
        $this->addNode($node);
        return $node;
    }

    public function getOrCreateNode($id, $params = NULL)
    {
        $node = $this->getNode($id);
        if (is_null($node)) {
            if ($params) {
                $node = $this->createNode($id, $params);
            } else {
                $params = $this->graphService->getNodeById($id);
                if (isset($params->type)) {
                    $node = $this->createNode($id, $params);
                }
            }
        }
        return $node;
    }

    public function clearAll()
    {
        foreach ($this->nodes as $node) {
            $id = $node->getId();
            unset($this->nodesByName[$node->getName()]);
            unset($this->nodes[$id]);
            foreach ($this->links as $idSource => $targets) {
                foreach ($targets as $idTarget => $link) {
                    if ($idTarget == $id) {
                        unset($this->links[$idSource][$idTarget]);
                    }
                }
            }
            unset($this->links[$id]);
            unset($this->nodesInput[$id]);
            unset($this->nodesOutput[$id]);
        }
        $this->nodesByName = [];
        $this->nodesByFullName = [];
        $this->nodesByType = [];
        $this->nodesByClass = [];
        $this->nodesByRegion = [];
        $this->constraintsByRegion = [];
        $this->linksInput = [];
        $this->linksById = [];
    }

    public function getIdNodesInput($idNode)
    {
        if (!isset($this->nodesInput[$idNode])) {
            $this->nodesInput[$idNode] = [];
            $list = $this->getLinksInput($idNode);
            foreach ($list as $link) {
                $this->nodesInput[$idNode][$link->idSource] = $link->idSource;
            }
        }
        return $this->nodesInput[$idNode];
    }

    public function getIdNodesOutput($idNode)
    {
        if (count($this->nodesOutput[$idNode])) {
            return array_keys($this->nodesOutput[$idNode]);
        } else {
            return [];
        }
    }

    public function getStructure()
    {
        $structure = (object)[
            'nodes' => [],
            'links' => [],
            'groups' => []
        ];
        $slots = [];
        $i = 0;
        $validNodes = [];
        $cola = [];
        $regions = [];
        $nodes = [];
        ksort($this->nodes);
        foreach ($this->nodes as $node) {
            //$this->dump('>>>>>>>>>>>>  ' . $node->id . ' - ' . $node->label . ' - ' . $node->type . $node->idHead);
            $validNodes[$node->getId()] = 1;
            $cola[$node->getId()] = $node->getId();
            $nodes[$node->getId()] = $node;
            $i++;
        }
        foreach ($this->nodes as $node) {
            $idSource = $node->getId();
            if (isset($this->links[$idSource])) {
                foreach ($this->links[$idSource] as $idTarget => $link) {
                    if (isset($cola[$idSource]) && isset($cola[$idTarget])) {
                        $iSourceNode = $cola[$idSource];
                        $iTargetNode = $cola[$idTarget];
                        $sourceNode = $nodes[$idSource];
                        $targetNode = $nodes[$idTarget];
                        if (!isset($structure->nodes[$iSourceNode])) {
                            $structure->nodes[$iSourceNode] = [
                                'index' => $cola[$idSource],
                                'id' => $sourceNode->getId(),
                                'name' => $sourceNode->getName(),
                                'type' => $sourceNode->getType(),
                                'class' => $sourceNode->getClass(),
                                'region' => $sourceNode->region,
                                'logic' => $sourceNode->logic,
                            ];
                            $regions[$sourceNode->region] = $sourceNode->region;

                        }
                        if (!isset($structure->nodes[$iTargetNode])) {
                            $structure->nodes[$iTargetNode] = [
                                'index' => $cola[$idTarget],
                                'id' => $targetNode->getId(),
                                'name' => $targetNode->getName(),
                                'type' => $targetNode->getType(),
                                'class' => $targetNode->getClass(),
                                'region' => $targetNode->region,
                                'logic' => $targetNode->logic,
                            ];
                            $regions[$targetNode->region] = $targetNode->region;

                        }
                        $structure->links[] = [
                            'source' => $iSourceNode,
                            'target' => $iTargetNode,
                            'label' => $link->label ?: 'rel_common',
                            'status' => 'active',
                            'optional' => $link->optional,
                            'head' => $link->head
                        ];
                    }

                }
            }
        }
        $structure->regions = $regions;
        return $structure;
    }

}


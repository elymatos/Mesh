<?php
use DI\Factory\RequestedEntry;
use function DI\create;
use Net\Ematos\Mesh\Infra\Manager;
use Net\Ematos\Mesh\Infra\GraphViz;


return [
    'MeshLogger' => create('Net\Ematos\Mesh\Infra\Logger')
        ->constructor('MeshLog'),
    Manager::class => function(\Psr\Container\ContainerInterface $c) {
        $manager = Manager::getInstance();
        $manager->setLogger($c->get('MeshLogger'));
        $manager->setContainer($c);
        return $manager;
    },
    'TypeNetwork' => create('Net\Ematos\Mesh\Network\TypeNetwork')
        ->method('setManager', DI\get(Manager::class))
        ->method('setGraphViz', DI\get(GraphViz::class)),
    'TokenNetwork' => create('Net\Ematos\Mesh\Network\TokenNetwork')
        ->method('setManager', DI\get(Manager::class)),
    'RegionNetwork' => create('Net\Ematos\Mesh\Network\RegionNetwork')
        ->method('setManager', DI\get(Manager::class)),

/*


    'C5\ORM\Service\GraphService' => create()
        ->constructor(EntityManager::getInstance('mtm')),
    'C5\ORM\Service\C5Service' => create()
        ->constructor(EntityManager::getInstance('mtm')),
    'C5\ORM\Service\MTMService' => create()
        ->constructor(EntityManager::getInstance('mtm')),
    'C5\Infra\GraphViz' => create()
        ->constructor(),
    'ResourceFull' => create('C5\Service\ResourceFull')
        ->method('setManager', DI\get(Manager::class))
        ->method('setDataService', DI\get('C5\ORM\Service\MTMService'))
        ->method('setGraphService', DI\get('C5\ORM\Service\GraphService')),

    'FullNetwork' => create('C5\Network\FullNetwork')
        ->method('setManager', DI\get(Manager::class))
        ->method('setGraphViz', DI\get('C5\Infra\GraphViz'))
        ->method('setGraphService', DI\get('C5\ORM\Service\GraphService')),
    'TypeNetwork' => create('Mesh\Element\Network\TypeNetwork')
        ->method('setManager', DI\get(Manager::class))
        ->method('setMeshService', DI\get('C5\ORM\Service\MeshService'))
        ->method('setGraphService', DI\get('C5\ORM\Service\GraphService')),
    'TokenNetwork' => create('Mesh\Element\Network\TokenNetwork')
        ->method('setGraphViz', DI\get('C5\Infra\GraphViz'))
        ->method('setManager', DI\get(Manager::class)),
    'ConceptNetwork' => create('C5\Network\ConceptNetwork')
        ->method('setGraphViz', DI\get('C5\Infra\GraphViz'))
        ->method('setManager', DI\get(Manager::class)),
    'RegionNetwork' => create('Mesh\Element\Network\RegionNetwork')
        ->method('setGraphViz', DI\get('C5\Infra\GraphViz'))
        ->method('setManager', DI\get(Manager::class)),
    //'CxnNode' => create('C5\Node\CxnNode')
    //    ->constructor(DI\get('idNode')),
    'CxnNode' => function() {
        return new C5\Node\CxnNode();
    },
    'CENode' => function() {
        return new C5\Node\CENode();
    },
    'FrameNode' => function() {
        return new C5\Node\FrameNode();
    },
    'UDRelationNode' => function() {
        return new C5\Node\UDRelationNode();
    },
    'NodeConcept' => function() {
        return new C5\Node\NodeConcept();
    },
    'ConstraintNode' => function() {
        return new C5\Node\ConstraintNode();
    },
*/
];

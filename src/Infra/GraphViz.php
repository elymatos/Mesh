<?php
namespace Net\Ematos\Mesh\Infra;

class GraphViz
{

    /* nodes [
            'index' => $i,
            'id' => $node->getId(),
            'name' => utf8_encode($node->getLabel()),
            'position' => $node->index,
            'activation' => round($node->getO(), 5),
            'type' => $node->getType(),
            'status' => $node->status,
            'group' => $node->group
       ];

        links [
            'source' => $cola[$node->getId()],
            'target' => $cola[$this->getSite($target)->idNode()],
            'label' => $site->label() ?: 'rel_common',
            'status' => (($site->active() || $site->predictive()) ? 'active' : 'inactive'),
            'optional' => $site->optional()
        ];

        groups[nameGroup][link]

     */

    public $structure;
    public $typeNode;
    public $typeEdge;
    public $typeStatus;
    public $graphAttributes;
    public $fontSize;
    public $definitions;

    public function __construct($definitions = 'graphviz.json')
    {
        $this->fontSize = 8;
        $this->definitions = json_decode(file_get_contents($definitions));
    }

    public function setStructure($structure) {
        $this->structure = $structure;
    }

    public function generateDot()
    {
        $graph = $this->createGraph();
        $dot = $graph->parse();
        return $dot;
    }

    public function createGraph()
    {
        $directed = true;
        $graph = new Image_GraphViz($directed, ['colorscheme' => 'svg'], 'G', false, true);
        if (isset($this->structure->regions)) {
            $regions = $this->structure->regions;
            foreach ($regions as $region) {
                $graph->addCluster('cluster_r' . $region, 'Region ' . $region);
            }
        }
        if (count($this->structure->nodes)) {
            $this->addNodes($graph, $this->structure->nodes);
        }
        if (count($this->structure->links)) {
            $this->addEdges($graph, $this->structure->links);
        }
        $graph->addAttributes($this->definitions->attributes);
        $this->fontSize = $this->definitions->attributes->fontSize;
        return $graph;
    }

    public function getInfo($node) {
        return '';
    }

    public function getNodeDefinition($node) {

        $nodeType = $node['type'];
        $definitions = $this->definitions->typeNode->$nodeType;
        $labelType = $definitions->labelType ?: 'x';
        $a = '';
        /*
        $a = substr($node['activation'],0,6);
        $words = '';
        $slots = $node['slots'];
        for($i = 1; $i <= count($this->words); $i++) {
            if ($slots->get($i)) {
                $words .= $this->words[$i] . ' ';
            }
        }
        */
        $info = $this->getInfo($node);

        if ($labelType == 'l') {
            $label = $node['name'] . ' [' . $a . ']';
            $xlabel = $info;
            //$xlabel = $node['name'] . ' [' . $a . ']';
            //$label = '';
        } else if ($labelType == 'i') {
            $label = '';
            $xlabel = $info;
        } else if ($labelType == 'x') {
            $label = '';
            $xlabel = $info;
        }
        $style = $definitions->style;
        /*
        $status = $node['status'];
        if ($status == 'active') {
            $color = $this->typeNode[$nodeType]['bgcolor'];
            $fontColor = $this->typeNode[$nodeType]['fontColor'] ?: 'black';
        } else {
            //$color = $this->typeStatus[$status]['color'];
            //$fontColor = $this->typeStatus[$status]['fontColor'] ?: 'black';
            $color = 'white';
            $fontColor = 'black';
        }
        */
        $color = $definitions->bgcolor;
        $fontColor = $definitions->fontColor ?: 'black';
        $tooltip = $xlabel;
        $xlabel = '';

        $shape = $definitions->shape;
        /*
        if ($node['logic'] == 'N') {
            $shape = 'component';
            $label = $xlabel = '';
        }
        */
        $size = (($shape == 'triangle')) ? '0.15' : '0.1';

        return (object)[
            'id' => $node['id'],
            'xlabel' => $xlabel,
            'label' => $label,
            'tooltip' => $tooltip,
            'fontName' => 'helvetica',
            'size' => $size,
            'style' => $style,
            'color' => $color,
            'fontColor' => $fontColor,
            'shape' => $shape,
            'region' => $node['region']
        ];

    }

    private function addNodes(&$graph, $nodes)
    {
        foreach ($nodes as $node) {
            $nodeDefinition = $this->getNodeDefinition($node);
            $id = $nodeDefinition->id;
            $params = [
                'xlabel' => $nodeDefinition->xlabel,
                'label' => $nodeDefinition->label,
                'tooltip' => $nodeDefinition->tooltip,
                'fontname' => $nodeDefinition->fontName,
                'shape' => $nodeDefinition->shape,
                'height' => $nodeDefinition->size,
                'width' => $nodeDefinition->size,
                'style' => $nodeDefinition->style,
                'fillcolor' => $nodeDefinition->color,
                'fontcolor' => $nodeDefinition->fontColor,
                'fontsize' => $this->fontSize
            ];
            if (($nodeDefinition->region == '') || (!isset($this->structure->regions))) {
                $graph->addNode($id, $params);
            } else {
                $graph->addNode($id, $params, 'cluster_r' . $nodeDefinition->region);
            }
        }
    }

    private function addEdges(&$graph, $links)
    {
        foreach ($links as $link) {
            $linkLabel = $link['label'];
            $definitions = $this->definitions->typeEdge->$linkLabel;
            print_r($definitions);
            $label = '';//' ' . $e[1];
            $optional = $link['optional'];
            $head = $link['head'];
            $color = ($link['status'] == 'active') ? $definitions->color : 'gray';
            //var_dump($t);
            $graph->addEdge(
                [
                    $link['source'] => $link['target'],
                ], [
                    'color' => $color,
                    'label' => $label,
                    'xlabel' => $label,
                    'tooltip' => $label,
                    'minlen' => '1',
                    'fontname' => 'helvetica',
                    'fontsize' => $this->fontSize,
                    'arrowsize' => '0.5',
                    'arrowhead' => ($head == '1' ? 'normal' : $definitions->arrowType),
                    'penwidth' => $definitions->penwidth,
                    'style' => ($optional == '1' ? 'dashed' : $definitions->style)
                ]
            );
        }
    }

}


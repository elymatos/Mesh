<?php
namespace Net\Ematos\Mesh\Infra;

class GraphViz
{

    public $structure;
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

    public function generateImage($dotfile, $outputfile, $format = 'svg', $command = 'dot') {

        $graph = $this->createGraph();
        $graph->renderDotFile($dotfile, $outputfile, $format, $command);
    }

    public function createGraph()
    {
        $directed = true;
        $graph = new Image_GraphViz($directed, ['colorscheme' => $this->definitions->attributes->colorScheme], 'G', false, true);
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

    protected function getInfo($node) {
        return '';
    }

    protected function getNodeDefinition($node) {

        $nodeType = $node['type'];
        $definitions = $this->definitions->typeNode->$nodeType;
        $labelType = $definitions->labelType ?: 'x';
        $a = '';
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
        $color = $definitions->bgcolor;
        $fontColor = $definitions->fontColor ?: 'black';
        $tooltip = $xlabel;
        $xlabel = '';

        $shape = $definitions->shape;
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
            $label = '';//' ' . $e[1];
            $optional = $link['optional'];
            $head = $link['head'];
            $color = ($link['status'] == 'active') ? $definitions->color : 'gray';
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


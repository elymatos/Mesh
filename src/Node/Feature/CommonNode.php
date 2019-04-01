<?php
namespace Net\Ematos\Mesh\Node\Feature;

use Net\Ematos\Mesh\Node\FeatureNode;

class CommonNode extends FeatureNode
{

    public function spread()
    {
        return $this->spreadForward();
    }

}


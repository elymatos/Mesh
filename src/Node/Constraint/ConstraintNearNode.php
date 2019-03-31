<?php
namespace \Net\Ematos\Mesh\Node\Constraint;

use \Net\Ematos\Mesh\Node\ConstraintNode;

class ConstraintNearNode extends ConstraintNode
{
    public function evaluate($argument1Token, $argument2Token) {
        $argument1WordIndex = $argument1Token->wordIndex;
        $argument2WordIndex = $argument2Token->wordIndex;
        $w = 1 - (abs($argument1WordIndex - $argument2WordIndex) / $argument1WordIndex);
        $argument2Token->w = $argument2Token->w * $w;
        return true;
    }

}


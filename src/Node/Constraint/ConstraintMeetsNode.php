<?php

namespace \Net\Ematos\Mesh\Node\Constraint;

use \Net\Ematos\Mesh\Node\ConstraintNode;

class ConstraintMeetsNode extends ConstraintNode
{
    public function evaluate($argument1Token, $argument2Token)
    {
        // result = arg1 meets arg2
        $argument1WordIndex = $argument1Token->getSlots()->max();
        $argument2WordIndex = $argument2Token->getSlots()->min();
        $result = (($argument1WordIndex + 1) == $argument2WordIndex);
        $argument2Token->w *= ($result ? 1.0 : 0.0);
        return $result;
    }

}


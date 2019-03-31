<?php
namespace \Net\Ematos\Mesh\Node\Constraint;

use \Net\Ematos\Mesh\Node\ConstraintNode;

class ConstraintXORNode extends ConstraintNode
{
    public function evaluate($argument1Token, $argument2Token) {
        $argument1Active = ($argument1Token->status == 'active') || ($argument1Token->status == 'predictive');
        $argument2Active = ($argument2Token->status == 'active') || ($argument2Token->status == 'predictive');
        $result = ($argument1Active && $argument2Active);
        $argument2Token->w *= ($result ? 1.0 : 0.0);
        return ($argument1Token->status != 'active');
    }

}


<?php
namespace \Net\Ematos\Mesh\Node\Constraint;

use \Net\Ematos\Mesh\Node\ConstraintNode;

class ConstraintHeadNode extends ConstraintNode
{
    public function evaluate($argument1Token, $argument2Token)
    {
        $argument1H = $argument1Token->h;
        $argument2H = $argument2Token->h;
        $this->dump("constraint head  a1 = " . $argument1H . ' a2 = ' . $argument2H );
        $result = ($argument2H == $argument1H);
        $argument2Token->w *= ($result ? 1.0 : 0.0);
        return $result;
    }
}

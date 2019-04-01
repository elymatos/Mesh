<?php

namespace Net\Ematos\Mesh\Node;


class FeatureNode extends TokenNode
{

    public function canFire()
    {
        return (($this->energy > 0) && (($this->status == 'active') || ($this->status == 'fired')));
    }

    protected function spreadForward()
    {
        $next = [];
        foreach ($this->outputSites as $site) {
            //if ($site->forward) {
            //    continue;
            //}
            $this->dump( 'spreading from site [' . $site->id . ' ' . $site->label . ' ' . $site->status . ' ' . $site->type . ' ' . $site->idATargetToken . ']');
            $nextNodes = $this->feedforward($site);
            foreach ($nextNodes as $nextNode) {
                $next[$nextNode->id] = $nextNode;
            }
        }
        return $next;
    }

    protected function spreadBack()
    {
        $next = [];
        foreach ($this->inputSites as $site) {
            if (!$site->forward) { // só tem feedback se teve forward
                continue;
            }
            if ($site->label == 'rel_constraint') {
                continue;
            }
            if ($site->label == 'rel_projection') {
                continue;
            }
            $this->dump('feedback to input site ' . $site->id . ' ' . $site->label . ' ' . $site->status . ' ' . $site->idSourceToken);
            $nextNodes = $this->feedback($site);
            foreach ($nextNodes as $nextNode) {
                $next[$nextNode->id] = $nextNode;
            }
        }
        return $next;
    }

    public function spreadBoth()
    {
        foreach ($this->spreadBack() as $nextNode) {
            $next[$nextNode->id] = $nextNode;
        }
        foreach ($this->spreadForward() as $nextNode) {
            $next[$nextNode->id] = $nextNode;
        }
        return $next;
    }


    public function spread()
    {
        return $this->spreadForward();
    }

    public function inheritance()
    {
    }

    public function updateStatus()
    {
        $this->status = 'active';
        parent::updateStatus();
        $isActive = ($this->status == 'active');
        $this->status = ($isActive ? 'active' : 'predictive');
    }

}


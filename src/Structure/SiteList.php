<?php
namespace Net\Ematos\Mesh\Structure;

use Net\Ematos\Mesh\Infra\Base;

/*
 * lista de Sites de um TokenNode
 * Vários métodos de acesso
 */
class SiteList extends Base
{
    public $inputSites;
    public $outputSites;
    public $sites;
    public $myTokenNode;

    function __construct($myTokenNode = null)
    {
        $this->inputSites = [];
        $this->outputSites = [];
        $this->sites = [];
        $this->myTokenNode = $myTokenNode;
    }

    public function createInputSite($linkedNodeToken, $link) {
        $site = new Site($linkedNodeToken->id, $link);
        $this->sites[] = $site;
        $this->inputSites[$linkedNodeToken->id] = $site;
    }

    public function createOutputSite($linkedNodeToken, $link) {
        $site = new Site($linkedNodeToken->id, $link);
        $this->sites[] = $site;
        $this->outputSites[$linkedNodeToken->id] = $site;
    }

    public function getInputSiteFrom($linkedNodeToken, $create = true) {
        $site =  $this->inputSites[$linkedNodeToken->id] ?? '';
        if (($site == '') && $create) {
            $link = $linkedNodeToken->tokenNetwork->getLinkFor($linkedNodeToken, $this->myTokenNode);
            $site = $this->createInputSite($linkedNodeToken, $link);
        }
        return $site;
    }

    public function getOutputSiteTo($linkedNodeToken, $create = true) {
        $site =  $this->outputSites[$linkedNodeToken->id] ?? '';
        if (($site == '') && $create) {
            $link = $linkedNodeToken->tokenNetwork->getLinkFor($this->myTokenNode, $linkedNodeToken);
            $site = $this->createOutputSite($linkedNodeToken, $link);
        }
        return $site;
    }

    public function getOutputSites() {
        return $this->outputSites;
    }

    public function getInputSites() {
        return $this->inputSites;
    }

    public function getAllSites() {
        return $this->sites;
    }

    /*
    public function initialize($idTokenNode) {
        $this->inputSites[$idTokenNode] = [];
        $this->outputSites[$idTokenNode] = [];
        $this->sites[$idTokenNode] = [];
    }

    public function createSite($sourceNodeToken, $targetNodeToken, $link) {
        $site = new Site($sourceNodeToken->id, $targetNodeToken->id, $link);
        $this->outputSites[$sourceNodeToken->id][$targetNodeToken->id] = $site;
        $this->inputSites[$targetNodeToken->id][$sourceNodeToken->id] = $site;
        $this->sites[$sourceNodeToken->id][] = $site;
        $this->sites[$targetNodeToken->id][] = $site;
    }

    public function getOutputSites($idTokenNode) {
        return $this->outputSites[$idTokenNode];
    }

    public function getInputSites($idTokenNode) {
        return $this->inputSites[$idTokenNode];
    }

    public function getAllSites($idTokenNode) {
        return $this->sites[$idTokenNode];
    }

    public function getOutputSiteFromTo($sourceNodeToken, $targetNodeToken, $create = true) {
        $site =  $this->outputSites[$sourceNodeToken->id][$targetNodeToken->id];
        if (($site == '') && $create) {
            $link = $sourceNodeToken->tokenNetwork->getLinkFor($sourceNodeToken, $targetNodeToken);
            $this->createSite($sourceNodeToken, $targetNodeToken, $link);
            $site = $this->outputSites[$sourceNodeToken->id][$targetNodeToken->id];
        }
        return $site;

    }

    public function getInputSiteFromTo($targetNodeToken, $sourceNodeToken, $create = true) {
        $site =  $this->inputSites[$targetNodeToken->id][$sourceNodeToken->id];
        if (($site == '') && $create) {
            $link = $sourceNodeToken->tokenNetwork->getLinkFor($sourceNodeToken, $targetNodeToken);
            $this->createSite($sourceNodeToken, $targetNodeToken, $link);
            $site =  $this->inputSites[$targetNodeToken->id][$sourceNodeToken->id];
        }
        return $site;

    }
    */


}


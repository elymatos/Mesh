<?php
namespace Net\Ematos\Mesh\Structure;

use Net\Ematos\Mesh\Infra\Base;

class Site extends Base
{
    public static $staticId = 0;
    public $id;
    public $idSourceToken;
    public $idTargetToken;
    public $idLinkedToken;
    public $link;
    public $status; // 'frozen', 'active', 'inactive', 'inhibited'
    public $a;
    public $forward;
    public $feedback;


    //function __construct($idSourceToken, $idTargetToken, $link)
    function __construct($idLinkedToken, $link)
    {
        $this->id = ++self::$staticId;
        //$this->idSourceToken = $idSourceToken;
        //$this->idTargetToken = $idTargetToken;
        $this->idLinkedToken = $idLinkedToken;
        $this->link = $link;
        $this->status = 'inactive';
        $this->a = 0;
        $this->forward = $this->feedback = false;
    }

    public function __get($name)
    {
        if (isset($this->link->$name)) {
            return $this->link->$name;
        }
    }

    public function active()
    {
        return ($this->status == 'active');
    }

    public function activate($a, $w) {
        $this->a = $a;
        $this->w = $w;
        $this->status = 'active';
    }

    public function label() {
        return $this->link->label;
    }

    public function optional() {
        return $this->link->optional;
    }

    public function head() {
        return $this->link->head;
    }

    public function w() {
        return $this->link->w;
    }

    public function isInhibited() {
        return ($this->status == 'inhibited');
    }

    public function activateForward() {
        $this->forward = true;
        if ($this->status == 'inactive') { // só muda se o status anterior é inactive (por causa das constraints)
            $this->status = 'active';
        }
    }

    public function activateFeedback() {
        $this->feedback = true;
        if ($this->status == 'inactive') { // só muda se o status anterior é inactive (por causa das constraints)
            $this->status = 'active';
        }
    }
}


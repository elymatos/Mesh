<?php

namespace Net\Ematos\Mesh\Infra;

/**
 * Class Base
 * All Mesh classes must inherit from Base
 * This class provides access to Manager and Logger (via dump method)
 */
class Base
{
    public $manager;
    public $container;

    public function __construct()
    {
        $this->manager = null;
        $this->container = null;
    }

    public function setManager($manager)
    {
        $this->manager = $manager;
        $this->container = $this->manager->getContainer();
    }

    public function dump($msg)
    {
        $this->manager->dump($msg);
    }

}


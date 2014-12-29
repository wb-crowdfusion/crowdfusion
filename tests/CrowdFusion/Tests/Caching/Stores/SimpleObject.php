<?php

namespace CrowdFusion\Tests\Caching\Stores;

class SimpleObject
{
    public $param1 = 'peach';
    private $param2 = 3;

    public function getParam1()
    {
        return $this->param1;
    }

    public function getParam2()
    {
        return $this->param2;
    }
}

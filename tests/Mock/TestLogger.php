<?php

namespace Ray\Di\Mock;

use Ray\Di\LoggerInterface;
use ArrayObject;

class TestLogger implements LoggerInterface
{
    static public $log = false;
    
    public function log($class, array $params, array $setter, $object)
    {
        $construct = serialize($params);
        $setter = serialize($setter);
        $intercept = ($object instanceof Weaver) ? (string)$object->___getBind() : '[]';
        $log = "Injector class={$class} constructor={$construct} setter={$setter} intercepter={$intercept}";
        self::$log = $log;
    }
}
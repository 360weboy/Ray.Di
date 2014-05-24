<?php
/**
 * This file is part of the Ray package.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

interface BoundInstanceInterface
{
    /**
     * @param string         $class
     * @param AbstractModule $module
     *
     * @return bool
     */
    public function hasBound($class, AbstractModule $module);

    /**
     * @return object
     */
    public function getBound();

    /**
     * @return array
     */
    public function getDefinition();
}

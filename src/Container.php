<?php

/**
 * This file is part of the Ray package.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

use Ray\Aop\Compiler;
use Ray\Aop\Pointcut;
use Ray\Di\Exception\Unbound;

final class Container
{
    /**
     * @var DependencyInterface[]
     */
    private $container = [];

    /**
     * @var Pointcut[]
     */
    private $pointcuts = [];

    /**
     * @param Bind $bind
     */
    public function add(Bind $bind)
    {
        $dependency = $bind->getBound();
        $dependency->register($this->container, $bind);
    }

    /**
     * @param Pointcut $pointcut
     */
    public function addPointcut(Pointcut $pointcut)
    {
        $this->pointcuts[] = $pointcut;
    }

    /**
     * @param string $interface
     * @param string $name
     *
     * @return mixed
     */
    public function getInstance($interface, $name)
    {
        return $this->getDependency($interface . '-' . $name);
    }

    /**
     * Return dependency injected instance
     *
     * @param string $index
     *
     * @return mixed
     * @throws Unbound
     */
    public function getDependency($index)
    {
        if (! isset($this->container[$index])) {
            list($class, $name) = explode('-', $index);
            throw new Unbound("interface:{$class} name:{$name}");
        }
        $dependency = $this->container[$index];
        $instance = $dependency->inject($this);

        return $instance;
    }

    /**
     * @return DependencyInterface[]
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return \Ray\Aop\Pointcut[]
     */
    public function getPointcuts()
    {
        return $this->pointcuts;
    }

    /**
     * @param Container $container
     */
    public function merge(Container $container)
    {
        $this->container = $this->container + $container->getContainer();
        $this->pointcuts = $this->pointcuts + $container->getPointcuts();
    }

    /**
     * @param Compiler $compiler
     */
    public function weaveAspects(Compiler $compiler)
    {
        foreach ($this->container as $dependency) {
            if (! $dependency instanceof Dependency) {
                continue;
            }
            /** @var $dependency Dependency */
            $dependency->weaveAspects($compiler, $this->pointcuts);
        }
    }

    /**
     * @param Compiler   $compiler
     * @param Dependency $dependency
     *
     * @return $this
     */
    public function weaveAspect(Compiler $compiler, Dependency $dependency)
    {
        $dependency->weaveAspects($compiler, $this->pointcuts);

        return $this;
    }

    public function __sleep()
    {
        return ['container'];
    }
}

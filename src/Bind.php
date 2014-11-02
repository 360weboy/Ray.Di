<?php

/**
 * This file is part of the Ray package.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

use Ray\Di\Di\PostConstruct;
use Ray\Di\Exception\InvalidBind;
use Ray\Di\Exception\NotFound;

final class Bind
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $interface;

    /**
     * @var string
     */
    private $name = Name::ANY;

    /**
     * @var InjectInterface
     */
    private $bound;

    /**
     * @param Container $container dependency container
     * @param string    $interface interface or concrete class name
     */
    public function __construct(Container $container, $interface)
    {
        $this->container = $container;
        $this->interface = $interface;
        if (class_exists($interface)) {
            $this->bound = (new DependencyFactory)->newAnnotatedDependency(new \ReflectionClass($interface));
            $container->add($this);

            return;
        }
        if ($interface && ! interface_exists($interface)) {
            throw new NotFound($interface);
        }
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function annotatedWith($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $class
     *
     * @return $this
     * @throws NotFound
     */
    public function to($class)
    {
        if (! class_exists($class)) {
            throw new NotFound($class);
        }
        $this->bound = (new DependencyFactory)->newAnnotatedDependency(new \ReflectionClass($class));
        $this->container->add($this);

        return $this;
    }

    /**
     * @param string          $class
     * @param string          $name            varName=bindName,...
     * @param InjectionPoints $injectionPoints
     * @param null            $postConstruct
     *
     * @return $this
     */
    public function toConstructor($class, $name, InjectionPoints $injectionPoints = null, $postConstruct = null)
    {
        $setterMethods = $injectionPoints ? $injectionPoints($class) : new SetterMethods([]);
        $postConstruct = $postConstruct ? new \ReflectionMethod($class, $postConstruct) : null;
        $newInstance = new NewInstance(new \ReflectionClass($class), $setterMethods, new Name($name));
        $this->bound = new Dependency($newInstance, $postConstruct);
        $this->container->add($this);

        return $this;
    }

    /**
     * @param string          $class
     * @param InjectionPoints $injectionPoints
     * @param string          $postConstruct
     *
     * @return $this
     */
    public function toExplicit($class, InjectionPoints $injectionPoints, $postConstruct)
    {
        $this->bound = (new DependencyFactory)->newExplicit(
            new \ReflectionClass($class),
            $injectionPoints,
            new \ReflectionMethod($class, $postConstruct)
        );
        $this->container->add($this);

        return $this;

    }

    /**
     * @param string $provider
     *
     * @return $this
     * @throws NotFound
     */
    public function toProvider($provider)
    {
        if (! class_exists($provider)) {
            throw new NotFound($provider);
        }
        $this->bound = (new DependencyFactory)->newProvider(new \ReflectionClass($provider));
        $this->container->add($this);

        return $this;
    }

    /**
     * @param Injector $instance
     *
     * @return $this
     */
    public function toInstance($instance)
    {
        $this->bound = new Instance($instance);
        $this->container->add($this);

        return $this;
    }

    /**
     * @param string $scope
     *
     * @return $this
     */
    public function in($scope)
    {
        if ($this->bound instanceof Dependency || $this->bound instanceof Provider) {
            $this->bound->setScope($scope);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->interface . '-' . $this->name;
    }

    /**
     * @return InjectInterface
     */
    public function getBound()
    {
        return $this->bound;
    }
}

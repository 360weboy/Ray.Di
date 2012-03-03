<?php
/**
 * Ray
 *
 * @package Ray.Di
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

use Ray\Aop\Weave;

use Aura\Di\Lazy,
    Aura\Di\ContainerInterface,
    Aura\Di\Exception\ContainerLocked;
use Ray\Aop\Bind,
    Ray\Aop\Weaver;

/**
 * Dependency Injector.
 *
 * @package Ray.Di
 *
 * @Scope("singleton")
 */
class Injector implements InjectorInterface
{
    /**
     * Config
     *
     * @var Config
     *
     */
    protected $config;

    /**
     *
     * A convenient reference to the Config::$params object, which itself
     * is contained by the Forge object.
     *
     * @var \ArrayObject
     *
     */
    protected $params;

    /**
     *
     * A convenient reference to the Config::$setter object, which itself
     * is contained by the Forge object.
     *
     * @var \ArrayObject
     *
     */
    protected $setter;

    /**
     * Container
     *
     * @var Container
     */
    protected $container;

    /**
     * Binding module
     *
     * @var AbstractModule
     */
    protected $module;

    /**
     * Pre-destory objects
     *
     * @var SplObjectStorage
     */
    private $preDestroyObjects;

    /**
     * Constructor
     *
     * @param ContainerInterface $container  The class to instantiate.
     * @param AbstractModule     $module Binding configuration module
     */
    public function __construct(ContainerInterface $container, AbstractModule $module = null)
    {
        $this->container = $container;
        $this->config = $container->getForge()->getConfig();
        $this->preDestroyObjects = new \SplObjectStorage;
        if ($module == null) {
            $module = new EmptyModule;
        }
        $this->module = $module;
        $this->bind = new Bind;
        $this->params = $this->config->getParams();
        $this->setter = $this->config->getSetter();
    }

    /**
     * Set binding module
     *
     * @param AbstractModule $module
     *
     * @return void
     */
    public function setModule(AbstractModule $module)
    {
        if ($this->container->isLocked()) {
            throw new ContainerLocked;
        }
        $this->module = $module;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->notifyPreShutdown();
    }

    /**
     * Clone
     */
    public function __clone()
    {
        $this->container = clone $this->container;
    }

    /**
     * Get a service object using binding module, optionally with overriding params.
     *
     * @param string $class The class to instantiate.
     * @param AbstractModule binding module
     * @param array $params An associative array of override parameters where
     * the key the name of the constructor parameter and the value is the
     * parameter value to use.
     *
     * @return object
     */
    public function getInstance($class, array $params = null)
    {
        list($config, $setter, $definition) = $this->config->fetch($class);
        // annotation dependency
        /* @var $definition Ray\Di\Definition */
        $hasDirectBinding =  isset($this->module->bindings[$class]);
        if ($definition->hasDefinition() || $hasDirectBinding) {
            list($config, $setter) = $this->bindModule($setter, $definition, $this->module);
        }

        $params = is_null($params) ? $config : array_merge($config, (array) $params);
        // lazy-load params as needed
        foreach ($params as $key => $val) {
            if ($params[$key] instanceof Lazy) {
                $params[$key] = $params[$key]();
            }
        }

        // check provision
        $this->checkProvision($class, $params, $this->module);

        // create the new instance
        $object = call_user_func_array(
                [$this->config->getReflect($class), 'newInstance'],
                $params
        );
        // call setters after creation
        foreach ($setter as $method => $value) {
            // does the specified setter method exist?
            if (method_exists($object, $method)) {
                if (!is_array($value)) {
                    // call the setter
                    $object->$method($value);
                } else {
                    call_user_func_array([$object, $method], $value);
                }
            }
        }
        $module = $this->module;
        $bind = $module($class, new $this->bind);
        /** @var $bind \BEAR\Di\Bind */
        if ($bind->hasBinding() === true) {
            $object = new Weaver($object, $bind);
        }
        // set life cycle
        if ($definition) {
            $this->setLifeCycle($object, $definition);
        }

        return $object;
    }

    /**
     * Check parameter provision
     *
     * @param string $class
     * @param array  $params
     *
     * @return void
     * @throws Exception\Provision
     */
    private function checkProvision($class, array &$params, AbstractModule $module)
    {
        $ref = method_exists($class, '__construct') ? new \ReflectionMethod($class, '__construct')  : false;
        if ($ref === false) {
            return;
        }
        $parameters = $ref->getParameters();
        foreach ($parameters as $index => $parameter) {
            $params = array_values($params);
            if (! isset($params[$index])) {
                $hasConstrcutorBinding = ($module[$class]['*'][AbstractModule::TO][0] === AbstractModule::TO_CONSTRUCTOR);
                if ($hasConstrcutorBinding) {
                    $param = isset($module[$class]['*'][AbstractModule::TO][1][$parameter->name]) ? $module[$class]['*'][AbstractModule::TO][1][$parameter->name] : false;
                    if ($param) {
                        $params[$index] = $param;
                        continue;
                    }
                }
                throw new Exception\Provision("Bind not found. argument #{$index}(\${$parameter->name}) in {$class} constructor.");
            }
        }
    }

    /**
     * Notify pre-destory
     *
     * @return void
     */
    private function notifyPreShutdown()
    {
        $this->preDestroyObjects->rewind();
        while ($this->preDestroyObjects->valid()) {
            $object = $this->preDestroyObjects->current();
            $method = $this->preDestroyObjects->getInfo();
            $object->$method();
            $this->preDestroyObjects->next();
        }
    }

    /**
     * Set object life cycle
     *
     * @param object $instance
     * @param array  $definition
     *
     * @return void
     */
    private function setLifeCycle($instance, Definition $definition = null)
    {
        $postConstructMethod = $definition[Definition::POST_CONSTRUCT];
        if ($postConstructMethod) {
            //signal
            call_user_func(array($instance, $postConstructMethod));
        }
        if (! is_null($definition[Definition::PRE_DESTROY])) {
            $this->preDestroyObjects->attach($instance, $definition[Definition::PRE_DESTROY]);
        }

    }

    /**
     * Return dependency using modules.
     *
     * @param array $setter
     * @param array $definition
     * @param AbstractModule $module
     * @throws Exception\Binding
     *
     * @return array <$constructorParams, $setter>
     */
    private function bindModule(array $setter, Definition $definition, AbstractModule $module)
    {
        // @return array [AbstractModule::TO => [$toMethod, $toTarget]]
        $container = $this->container;
        $config = $this->container->getForge()->getConfig();
        /* @var $forge Ray\Di\Forge */
        $injector = $this;
        $getInstance = function($in, $bindingToType, $target) use ($container, $definition, $injector) {
            if ($in === Scope::SINGLETON && $container->has($target)) {
                $instance = $container->get($target);
                return $instance;
            }
            switch ($bindingToType) {
                case AbstractModule::TO_CLASS:
                    $instance = $injector->getInstance($target);
                    break;
                case AbstractModule::TO_PROVIDER:
                    $provider = $injector->getInstance($target);
                    $instance = $provider->get();
                    break;
            }
            if ($in === Scope::SINGLETON) {
                $container->set($target, $instance);
            }
            return $instance;
        };
        $bindMethod = function ($item) use ($definition, $module, $getInstance) {
            list($method, $settings) = each($item);
            array_walk($settings, [$this, 'bindOneParameter'], [$definition, $getInstance]);
            return [$method, $settings];
        };
        // main
        $setterDefinitions = (isset($definition[Definition::INJECT][Definition::INJECT_SETTER]))
        ? $definition[Definition::INJECT][Definition::INJECT_SETTER] : false;
        if ($setterDefinitions !== false) {
            $injected = array_map($bindMethod, $setterDefinitions);
            $setter = [];
            foreach ($injected as $item) {
                $setterMethod = $item[0];
                $object =  (count($item[1]) === 1 && $setterMethod !== '__construct') ? $item[1][0] : $item[1];
                $setter[$setterMethod] = $object;
            }
        }
        // constuctor injection ?
        if (isset($setter['__construct'])) {
            $params = $setter['__construct'];
            unset($setter['__construct']);
        } else {
            $params = [];
        }
        return [$params, $setter];
    }

    /**
     * Set one parameter with definitio, or JIT binding.
     *
     * @param string $param
     * @param string $key
     * @param array  $userData
     *
     * @return void
     */
    private function bindOneParameter(&$param, $key, array $userData)
    {
        list($definition, $getInstance) = $userData;
        $annotate = $param[Definition::PARAM_ANNOTATE];
        $typeHint = $param[Definition::PARAM_TYPEHINT];
        $hasTypeHint = isset($this->module[$typeHint])
        && isset($this->module[$typeHint][$annotate])
        && ($this->module[$typeHint][$annotate] !== []);
        $binding = $hasTypeHint ? $this->module[$typeHint][$annotate] : false;
        if ($binding === false || isset($binding[AbstractModule::TO]) === false) {
            // default bindg by @ImplemetedBy or @ProviderBy
            $binding = $this->jitBinding($param, $definition, $typeHint, $annotate);
        }
        $bindingToType = $binding[AbstractModule::TO][0];
        $target = $binding[AbstractModule::TO][1];
        if ($bindingToType === AbstractModule::TO_INSTANCE) {
            $param = $target;
            return;
        } elseif ($bindingToType === AbstractModule::TO_CALLABLE) {
            $param = $target();
            return;
        }
        if (isset($binding[AbstractModule::IN])) {
            $in = $binding[AbstractModule::IN];
        } else {
            list($param, $setter, $definition) = $this->config->fetch($target);
            $in = isset($definition[Definition::SCOPE]) ? $definition[Definition::SCOPE] : Scope::PROTOTYPE;
        }
        $param = $getInstance($in, $bindingToType, $target);
    }

    private function jitBinding($param, $definition, $typeHint, $annotate)
    {
        $typehintBy = $param[Definition::PARAM_TYPEHINT_BY];
        if ($typehintBy == []) {
            $typehint = $param[Definition::PARAM_TYPEHINT];
            throw new Exception\Binding("$typeHint:$annotate");
        }
        if ($typehintBy[0] === Definition::PARAM_TYPEHINT_METHOD_IMPLEMETEDBY) {
            return [AbstractModule::TO => [AbstractModule::TO_CLASS, $typehintBy[1]]];
        }
        return [AbstractModule::TO => [AbstractModule::TO_PROVIDER, $typehintBy[1]]];
    }


    /**
     *
     * Lock the Container so that configuration cannot be accessed externally,
     * and no new service definitions can be added.
     *
     * @return void
     *
     */
    public function lock()
    {
        $this->container->lock();
    }

    /**
     * Returns a Lazy that creates a new instance. This allows you to replace
     * the following idiom:
     *
     * @param string $class The type of class of instantiate.
     *
     * @param array $params Override parameters for the instance.
     *
     * @return Lazy A lazy-load object that creates the new instance.
     *
     */
    public function lazyNew($class, array $params = null)
    {
        return $this->container->lazyNew($class, $params);
    }

    /**
     * Magic get to provide access to the Config::$params and $setter
     * objects.
     *
     * @param string $key The property to retrieve ('params' or 'setter').
     *
     * @return mixed
     * @throws Exception\ContainerLocked
     * @throws \UnexpectedValueException
     */
    public function __get($key)
    {
        return $this->container->__get($key);
    }

    /**
     * Set params or setter
     *
     * @param string params | setter
     * @param mixed $val
     *
     * @return $this
     */
    public function __set($key, $val)
    {
        $this->$key = $val;
        return $this;
    }

    /**
     * Return module information.
     *
     * @return string
     */
    public function __toString()
    {
        $result = (string)($this->module);
        return $result;
    }
}

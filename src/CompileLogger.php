<?php
/**
 * This file is part of the Ray package.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

use Ray\Aop\Bind;
use Aura\Di\ConfigInterface;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

class CompileLogger implements LoggerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $ref;
    protected $refs = [];

    /**
     * @var DependencyFactory[]
     */
    protected $instanceContainer = [];

    /**
     * @var ConfigInterface
     */
    protected $config;
    /**
     * @param LoggerInterface $logger
     *
     * @Inject
     * @Named("logger")
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ConfigInterface $config
     */
    public function setConfig(ConfigInterface $config)
    {
        $this->config = $config;

        return $this;
    }
    /**
     * Log injection
     *
     * @param string        $class
     * @param array         $params
     * @param array         $setter
     * @param object        $object
     * @param \Ray\Aop\Bind $bind
     */
    public function log($class, array $params, array $setters = [], $instance, Bind $bind)
    {
        $this->logger->log($class, $params, $setters, $instance, $bind);
        $this->build($class, $instance, $params, $setters);
    }

    /**
     * @param $ref
     *
     * @return mixed
     * @throws \LogicException
     */
    public function newInstance($ref)
    {
        if (! isset($this->instanceContainer[$ref])) {
            // @codeCoverageIgnoreStart
            throw new Exception\Compile($ref);
            // @codeCoverageIgnoreEnd
        }
        return $this->instanceContainer[$ref]->newInstance();
    }

    public function getLashHash()
    {
        $container = $this->instanceContainer;
        $factory = array_pop($container);

        return $factory->hash;
    }

    /**
     * @param object $instance
     * @param array  $params
     * @param array  $setters
     */
    private function build($class, $instance, array $params, array $setters)
    {
        $params = $this->makeParamRef($params);
        foreach ($setters as &$methodPrams) {
            $methodPrams = $this->makeParamRef($methodPrams);
        }
        $dependencyFactory = new DependencyFactory($instance, $params, $setters, $this);
        list(,,$definition) = $this->config->fetch($class);
        // @PostConstruct
        $postConstructMethod = $definition['PostConstruct'];
        $dependencyFactory->setPostConstruct($postConstructMethod);
        // aop ?
        if (isset($instance->rayAopBind)) {
            $interceptors = $this->buildInterceptor($instance);
            $dependencyFactory->setInterceptors($interceptors);
        }
        $this->add($dependencyFactory);
    }

    /**
     * @param $instance
     *
     * @return array
     */
    private function buildInterceptor($instance)
    {
        $boundInterceptors = (array)$instance->rayAopBind; // 'methodName' => methodInterceptors[]
        foreach ($boundInterceptors as $methodInterceptors) {
            foreach ($methodInterceptors as &$methodInterceptor) {
                /** @var $methodInterceptor \Ray\Aop\M*/
                $methodInterceptor = $this->getRef($methodInterceptor);
            }
        }

        return $boundInterceptors;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    private function makeParamRef(array $params)
    {
        foreach ($params as &$param) {
            if (is_object($param)) {
                $param = $this->getRef($param);
            }
            if (is_array($param)) {
                $param = $this->getArray($param);
            }
        }

        return $params;
    }

    /**
     * @param object $instance
     *
     * @return InstanceRef
     */
    private function getRef($instance)
    {
        $hash = spl_object_hash($instance);
        if (in_array($hash, $this->refs)) {
            return new InstanceRef($hash);
        }

        $this->refs[] = $hash;
        $this->ref++;

        return new InstanceRef($hash);
    }

    /**
     * @param array $array
     */
    private function getArray(array $array)
    {
        // @todo
        return $array;
    }

    /**
     * @param object $instance
     */
    private function add($instance)
    {
        $index = $instance->hash;
        $this->instanceContainer[$index] = $instance;
    }

    public function __toString()
    {
        return (string)$this->logger;
    }
}

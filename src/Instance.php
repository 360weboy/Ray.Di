<?php
/**
 * This file is part of the Ray package.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

final class Instance implements InjectInterface
{
    /**
     * @var mixed
     */
    public $value;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param Container $container
     *
     * @return mixed
     */
    public function inject(Container $container)
    {
        unset($container);
        return $this->value;
    }
}

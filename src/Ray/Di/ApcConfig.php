<?php
/**
 * Ray
 *
 * This file is taken from Aura.Di(https://github.com/auraphp/Aura.Di) and modified.
 *
 * @package Ray.Di
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * @see https://github.com/auraphp/Aura.Di
 *
 */
namespace Ray\Di;

use Aura\Di\ConfigInterface;
use ReflectionClass;

/**
 *
 * Retains and unifies class configurations.
 *
 * @package Ray.Di
 *
 */
class ApcConfig extends Config
{
    /**
     *
     * Fetches the unified constructor params and setter values for a class.
     *
     * @param string $class The class name to fetch values for.
     *
     * @return array An array with two elements; 0 is the constructor values
     * for the class, and 1 is the setter methods and values for the class.
     *
     */
    public function fetch($class)
    {
        $file = (new ReflectionClass($class))->getFileName();
        $key = __CLASS__ . $file . md5(serialize($this->setter));
        $config = apc_fetch($key, $success);
        $config = $config ?: parent::fetch($class);
        if ($success !== true) {
            apc_store($key, $config);
        }
//         if (isset($this->setter[$class])) {
//             $config[1] = (array)$this->setter[$class] + (array)$config[1];
//         }
        return $config;
    }
}

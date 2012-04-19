<?php
/**
 * Ray
 *
 * @package Ray.Di
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

use ArrayObject;

/**
 * Defines the interface for dependency injector logger.
 *
 * @package Ray.Di
 *
 */
interface LoggerInterface
{
    public function log($class, array $params, array $setter, $object);
}

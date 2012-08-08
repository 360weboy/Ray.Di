<?php
/**
 * Ray
 *
 * @package Ray.Di
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di\Exception;

use LogicException;

/**
 * Indicates that there was a runtime failure while providing an instance.
 *
 * @package Ray.Di
 */
class NotReadable extends LogicException implements Exception
{
}

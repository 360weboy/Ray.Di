<?php
/**
 * This file is part of the Ray package.
 *
 * @package Ray.Di
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di\Exception;

use RuntimeException;

/**
 * Read only.
 *
 * @package Ray.Di
 */
class ReadOnly extends RuntimeException implements Exception
{
}

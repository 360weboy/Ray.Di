<?php
/**
 * This file is part of the Ray package.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di\Di;

/**
 * Named
 *
 * @Annotation
 * @Target("METHOD")
 */
final class Named
{
    /**
     * @var string
     */
    public $value;
}

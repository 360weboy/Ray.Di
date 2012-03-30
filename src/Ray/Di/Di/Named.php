<?php
/**
 * Ray
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di\Di;

/**
 * Named
 *
 * @Annotation
 * @Target("METHOD")
 *
 * @package    Ray.Di
 * @subpackage Annotation
 */
final class Named implements Annotation
{
    public $value;
}
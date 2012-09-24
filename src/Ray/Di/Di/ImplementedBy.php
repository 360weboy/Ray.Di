<?php
/**
 * This file is part of the Ray package.
 *
 * @package Ray.Di
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di\Di;

/**
 * ImplememtedBy
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @package    Ray.Di
 * @subpackage Annotation
 */
final class ImplementedBy implements Annotation
{
    public $value;
}

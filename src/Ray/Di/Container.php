<?php
/**
 * This file is part of the Ray package.
 *
 * @package Ray.Di
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

use Aura\Di\Container as AuraContainer;
use Aura\Di\ContainerInterface;
use Aura\Di\ForgeInterface;
use Ray\Di\Di\Inject;

/**
 * Dependency injection container.
 *
 * @package Ray.Di
 */
class Container extends AuraContainer implements ContainerInterface
{
    /**
     * @param ForgeInterface $forge
     *
     * @Inject
     */
    public function __construct(ForgeInterface $forge)
    {
        parent::__construct($forge);
    }
}

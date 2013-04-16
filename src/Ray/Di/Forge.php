<?php
/**
 * This file is part of the Ray package.
 *
 * @package Ray.Di
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace Ray\Di;

use Aura\Di\ConfigInterface;
use Aura\Di\Forge as AuraForge;
use Aura\Di\ForgeInterface;
use Ray\Di\Di\Inject;

/**
 *
 * Creates objects using reflection and the specified configuration values.
 *
 * @package Ray.Di
 */
class Forge extends AuraForge implements ForgeInterface
{
    /**
     * @param ConfigInterface $forge
     *
     * @Inject
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
    }
}

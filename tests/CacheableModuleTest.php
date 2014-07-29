<?php

namespace Ray\Di;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Ray\Di\Modules\BasicModule;

class CacheableModuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Annotation
     */
    protected $cacheModule;

    protected function setUp()
    {
        parent::setUp();
        $moduleProvider = function() {return new BasicModule;};
        $this->cacheModule = new CacheableModule($moduleProvider, 'cache-key');
    }

    public function testNew()
    {
        $this->assertInstanceOf('Ray\Di\CacheableModule', $this->cacheModule);
    }

    public function testGet()
    {
        $injector = Injector::create([$this->cacheModule], new FilesystemCache($_ENV['TMP_DIR']));
        $db = $injector->getInstance('Ray\Di\Mock\DbInterface');
        $this->assertInstanceOf('Ray\Di\Mock\DbInterface', $db);
    }

    public function testGetCache()
    {
        $injector = Injector::create([$this->cacheModule], new FilesystemCache($_ENV['TMP_DIR']));
        $db = $injector->getInstance('Ray\Di\Mock\DbInterface');
        $this->assertInstanceOf('Ray\Di\Mock\DbInterface', $db);
    }
}

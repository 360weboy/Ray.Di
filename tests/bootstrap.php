<?php

error_reporting(E_ALL);

use Doctrine\Common\Annotations\AnnotationRegistry;

// Ensure that composer has installed all dependencies
if (!file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    die("Dependencies must be installed using composer:\n\n php composer.phar install --dev\n\n"
        . "See http://getcomposer.org for help with installing composer\n");
}

// vendor
$loader = require dirname(__DIR__) . '/vendor/autoload.php';
/** @var $loader \Composer\Autoload\ClassLoader */
$loader->add('Ray\Di', __DIR__);
/** @var $loader \Composer\Autoload\ClassLoader */
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

$rm = function ($dir) use (&$rm) {
    foreach (glob($dir . '/*') as $file) {
        is_dir($file) ? $rm($file) : unlink($file);
        @rmdir($file);
    }
};
// clear cache folder
$rm(__DIR__ . 'Ray/Di/scripts/aop_files');
$rm(__DIR__ . 'Ray/Di/scripts/object_files');
$_ENV['RAY_TMP'] = __DIR__ . '/tmp';
$rm($_ENV['RAY_TMP']);

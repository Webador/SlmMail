<?php

ini_set('error_reporting', E_ALL);

$files = array(__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php');

foreach ($files as $file) {
    if (file_exists($file)) {
        $loader = require $file;

        break;
    }
}

if (! isset($loader)) {
    throw new RuntimeException('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
}

/* @var $loader \Composer\Autoload\ClassLoader */
$loader->add('SlmMailTest\\', __DIR__);

if (!$config = @include __DIR__ . '/TestConfiguration.php') {
    $config = require __DIR__ . '/TestConfiguration.php.dist';
}

\SlmMailTest\Util\ServiceManagerFactory::setConfig($config);

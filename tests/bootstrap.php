<?php

error_reporting(-1);
date_default_timezone_set('UTC');

define('PATH_ROOT', realpath(dirname(__DIR__)));
define('PATH_SYSTEM', PATH_ROOT . '/system');

if (!file_exists(PATH_ROOT . '/composer.lock')) {
    die("Dependencies must be installed using composer:\n\nphp composer.phar install\n\n"
        . "See http://getcomposer.org for help with installing composer\n");
}

include PATH_ROOT . '/vendor/autoload.php';
require PATH_SYSTEM . '/context/ApplicationContext.php';

$loader = new ClassLoader();
$loader->addDirectory(PATH_SYSTEM . '/core/classes/');
$loader->addClassDirectory(PATH_ROOT . '/tests/');

<?php

$now = microtime(TRUE);

define('PATH_ROOT', realpath(dirname(__FILE__).'/'));
define('PATH_BUILD', PATH_ROOT.'/build');
define('PATH_APP', PATH_ROOT.'/app');
define('PATH_SYSTEM', PATH_ROOT .'/system');

require(PATH_SYSTEM.'/context/ApplicationContext.php');

$ApplicationContext = new ApplicationContext(
    $contextResources = array(
        PATH_SYSTEM.'/core',
        PATH_ROOT.'/crowdfusion',
        PATH_APP.'/plugins',
    ),
    $options = array(
        'determineContext' => true,
        'cacheDir' => PATH_BUILD.'/deploy/',
        'systemFile' => PATH_BUILD.'/system.xml',
        'environmentsFile' => PATH_BUILD.'/environments.xml',
        'systemContextDir' => PATH_SYSTEM.'/config/context'
    ));

$Dispatcher = $ApplicationContext->object('Dispatcher');
$Dispatcher->processRequest();

//error_log(((microtime(TRUE) - $now)*1000).'ms');

<?php

///////////////////////////////////////
// SYSTEM VARIABLES / DO NOT EDIT!!! //
// vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv //

$properties['cf.version'] = '3.2.14';

$properties['development.mode'] = $this->isHotDeploy();
$properties['one.off.redeploy'] = $this->isOneOffRedeploy();

$properties['rootPath'] = PATH_ROOT;
$properties['applicationPath'] = PATH_APP;
$properties['buildPath'] = PATH_BUILD;
$properties['systemPath'] = PATH_SYSTEM;

$properties['backupPath'] = PATH_BUILD .'/backups';
$properties['viewDirectory'] = PATH_APP .'/view';

$properties['deployPath'] = (!empty($_SERVER['DEPLOYMENT_BASE_PATH'])?(string)$_SERVER['DEPLOYMENT_BASE_PATH']:'');

$properties['systemXMLFile'] = PATH_BUILD.'/system.xml';
$properties['environmentsXMLFile'] = PATH_BUILD.'/environments.xml';

$properties['environment'] = (!empty($_SERVER['ENVIRONMENT'])?(string)$_SERVER['ENVIRONMENT']:'default');
$properties['context'] = (!empty($_SERVER['CONTEXT'])?(string)$_SERVER['CONTEXT']:'');
$properties['siteDomain'] = (!empty($_SERVER['DOMAIN'])?(string)$_SERVER['DOMAIN']:'');
$properties['deviceView'] = (!empty($_SERVER['DEVICE_VIEW'])?(string)$_SERVER['DEVICE_VIEW']:'main');
$properties['design'] = (!empty($_SERVER['DESIGN'])?(string)$_SERVER['DESIGN']:'default');
$properties['rewriteBase'] = (!empty($_SERVER['REWRITE_BASE'])?(string)$_SERVER['REWRITE_BASE']:'');
$properties['routerBase'] = (!empty($_SERVER['ROUTER_BASE'])?(string)$_SERVER['ROUTER_BASE']:'');
$properties['site'] = (!empty($_SERVER['SITE'])?$_SERVER['SITE']:array());

$properties['isCommandLine'] = (!empty($_SERVER['CLI_REQUEST']) && $_SERVER['CLI_REQUEST'] == true);
$properties['isAliasDomain'] = (!empty($_SERVER['MATCHED_ALIAS'])?$_SERVER['MATCHED_ALIAS']:false);

$properties['isSiteDeployment'] = ($properties['context'] == 'web');
$properties['nodeCache.keepLocal'] = ($properties['context'] != 'cli');
$properties['nodeSchemaVersion'] = null;
$properties['lock.system.changes'] = false;

$properties['response.outputBuffering'] = ($properties['context'] != 'cli');
$properties['response.vary'] = $properties['context'] == 'web' ? 'Accept-Encoding, User-Agent' : 'Accept-Encoding';
$properties['cft.constants'] = [];

$properties['configFileLocation'] = PATH_BUILD.DIRECTORY_SEPARATOR.'pluginconfig.php';

// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ //
// SYSTEM VARIABLES / DO NOT EDIT!!! //
///////////////////////////////////////


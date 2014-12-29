<?php
/**
 * console.php
 *
 * PHP version 5
 *
 * @package     CrowdFusion
 * @version     $Id: console.php 2013-05-22 15:41:27Z jbsmith $
 */

// -------------------------------------------
if ($argc < 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>

Crowd Fusion Console (command line interface)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is a command line PHP script with 6 options, the first 2 are mandatory.
Options can be included in any order.

  Usage:
  php <?php echo $argv[0]; ?> --env="<environment>" --uri="<request_uri>" --compiled="<compiled>" --device="<device_view>"
                  --design="<design>" --context="<custom_context>"

  --env="<environment>"         The environment to execute this script in, ex. dev, rc, fb1, fb2, uat, stage, prod
  --uri="<request_uri>"         The request URL with query string, ex. http://www.yourdomain.com/cli/controller/method-name/?x=1
  --compiled="<compiled>"       OPTIONAL, default to false
  --device="<device_view>"      OPTIONAL, defaults to "main"
  --design="<design>"           OPTIONAL, defaults to "default"
  --context="<custom_context>"  OPTIONAL, no default, specify full path to a custom XML context for this request

<?php
    exit;
}

ini_set('memory_limit', '1024M');

// Install our signal handler first --------
function __signal_handler($signo) {
    // Catches signals.
    echo "\n\nCaught signal: $signo. Performing clean exit.\n\n";
    exit;
}

// Register signal handling for CMDLINE requests
if (function_exists('pcntl_signal')) {
    declare(ticks = 1);

    pcntl_signal(SIGTERM, '__signal_handler');
    pcntl_signal(SIGINT,  '__signal_handler');
}

// PARSE Options
// NOT using short options due to ambiguous behavior when option values are similar to short option names
// Assign $options from previously assigned $cli_options var or from passed in args
$options = isset($cli_options) ? $cli_options : getopt('', array('env:','uri:','compiled::','device::','design::','context::'));

$env = isset($options['env']) ? $options['env'] : false;
$request_uri = isset($options['uri']) ? $options['uri'] : false;
$compiled = isset($options['compiled']) ? $options['compiled'] : false;
$device_view = isset($options['device']) ? $options['device'] : 'main';
$design = isset($options['design']) ? $options['design'] : 'default';
$custom_context = isset($options['context']) ? $options['context'] : null;

if (false === $env) {
    die("--env option is required.\n");
}

if (false === $request_uri) {
    die("--uri option is required.\n");
}

if (!is_bool($compiled)) {
    $compiled = in_array(strtolower((string)$compiled), array('1', 'true', 'on', 'yes', 'y'));
}

// Check for presence of context file
if (!empty($custom_context) && !file_exists($custom_context)) {
    $custom_context = null;
}

// Parse the request_uri
$parts = parse_url($request_uri);
if (empty($parts['path']) || empty($parts['host'])) {
    die('Cannot parse URL: '.$request_uri."\n");
}
if (isset($parts['query'])) {
    $request_uri = $parts['path'].'?'.$parts['query'];
} else {
    $request_uri = $parts['path'];
}
$scheme = $parts['scheme'];
if ($scheme == 'https') {
    $_SERVER['HTTPS'] = 'on';
}
$siteDomain = $parts['host'];

$_SESSION = array();

$_SERVER['ENVIRONMENT'] = $env;
$_SERVER['DESIGN'] = $design;
$_SERVER['DEVICE_VIEW'] = $device_view;
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['SERVER_NAME'] = $siteDomain;
$_SERVER['SERVER_ADDR'] = gethostbyname(gethostname());
$_SERVER['HTTP_USER_AGENT'] = 'crowdfusion-cli';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['CLI_REQUEST'] = true;

$_SERVER['REQUEST_URI'] = $request_uri;
$_GET = array();
$_POST = array();
if (strpos($request_uri, '?') !== false) {
    $_SERVER['QUERY_STRING'] = substr($request_uri, strpos($request_uri, '?')+1);
    parse_str($_SERVER['QUERY_STRING'], $_GET);
}

$now = microtime(true);

define('PATH_ROOT_SYM', dirname(__FILE__).'/');
define('PATH_ROOT', realpath(dirname(__FILE__).'/'));
define('PATH_BUILD', PATH_ROOT.'/build');
define('PATH_APP', PATH_ROOT.'/app');
define('PATH_SYSTEM', PATH_ROOT .'/system');

require(PATH_SYSTEM.'/context/ApplicationContext.php');

$hotDeploy = !$compiled;

//////////////////////
// SYSTEM BOOTSTRAP //
//////////////////////
$contextResources = array(
        PATH_SYSTEM.'/core',
        PATH_ROOT.'/crowdfusion',
        PATH_APP.'/plugins',
    );

$postContextResources = array();
if (!empty($custom_context)) {
    $postContextResources[] = $custom_context;
}

$ApplicationContext = new ApplicationContext(
    $contextResources,
    $options = array(
        'determineContext' => true,
        'hotDeploy' => $hotDeploy,
        'cacheDir' => PATH_BUILD.'/deploy/',
        'systemFile' => PATH_BUILD.'/system.xml',
        'environmentsFile' => PATH_BUILD.'/environments.xml',
        //'writeCache' => false,
        'systemContextDir' => PATH_SYSTEM.'/config/context'
    ),
    $postContextResources);


$Dispatcher = $ApplicationContext->object('Dispatcher');
$Dispatcher->processRequest();

//error_log(((microtime(TRUE) - $now)*1000).'ms, '.$ApplicationContext->getNumInstancesCreated().' instances, mem usage: '.FileSystemUtils::humanFilesize(memory_get_peak_usage(true), 4));
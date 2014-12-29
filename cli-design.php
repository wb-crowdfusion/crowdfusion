<?php
/**
 * cli-design.php
 *
 * PHP version 5
 *
 * @package     CrowdFusion
 * @version     $Id: cli-design.php 169 2013-05-23 03:15:27Z jbsmith $
 */

// -------------------------------------------
if ($argc < 4 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>

DEPRECATED CLI!!! Use console.php

Crowd Fusion CLI (command line interface)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is a command line PHP script with 5 options.

  Usage:
  php <?php echo $argv[0]; ?> <environment> <design> <request_uri> <custom_context>

  <environment>    The environment to execute this script in, ex.  dev, rc, fb1, fb2, uat, stage, prod
  <design>         The Design to execute this script against. Defaults to 'default'
  <request_uri>    The request URL with query string, ex. http://www.yourdomain.com/cli/controller/method-name/?x=1
  <custom_context> OPTIONAL, specify full path to a custom XML context for this request

<?php
    exit;
}

$cli_options = array(
    'env' => $argv[1],
    'design' => $argv[2],
    'uri' => $argv[3]
);

if (isset($argv[4])) {
    $cli_options['context'] = $argv[4];
}

include('console.php');
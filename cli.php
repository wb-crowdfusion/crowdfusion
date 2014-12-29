<?php
/**
 * cli.php
 *
 * PHP version 5
 *
 * @package     CrowdFusion
 * @version     $Id: cli.php 169 2013-05-23 03:15:27Z jbsmith $
 */

// -------------------------------------------
if ($argc < 3 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>

DEPRECATED CLI!!! Use console.php

Crowd Fusion CLI (command line interface)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is a command line PHP script with 3 options.

  Usage:
  php <?php echo $argv[0]; ?> <environment> <request_url> <custom_context>

  <environment> The environment to execute this script in, ex.  dev, rc, fb1, fb2, uat, stage, prod
  <request_url> The request URL with query string, ex. http://www.yourdomain.com/cli/controller/method-name/?x=1
  <custom_context> OPTIONAL, specify full path to a custom XML context for this request

<?php
    exit;
}

$cli_options = array(
    'env' => $argv[1],
    'uri' => $argv[2]
);

if (isset($argv[3])) {
    $cli_options['context'] = $argv[3];
}

include('console.php');
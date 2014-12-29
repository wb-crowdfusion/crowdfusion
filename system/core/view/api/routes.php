<?php
/**
 * Routing regular expressions to enable JSON Ajax calls.
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009-2011 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted.
 *
 * @package     CrowdFusion
 * @copyright   2009-2011 Crowd Fusion Inc.
 * @license     http://www.crowdfusion.com/licenses/enterprise CF Enterprise License
 * @version     $Id: routes.php 706 2011-10-05 15:42:32Z clayhinson $
 */


$routes = array (

    '/node/(add|quick-add|add-tag|update-tags|decrement-meta|delete|edit|increment-meta|remove-tag|replace|undelete|update-meta)\.(json|xml)/?' =>
        array (
            'action' => 'node-$1',
            'action_datasource' => 'node-$1',
            'action_form_view' => 'node/$1.cft',
            'action_success_view' => 'node/$1.cft',
            'view_handler' => '$2',
            'b_save' => true
        ),

    '/node/(find-all|get|exists|get-tags)\.(json|xml)/?' =>
        array (
            'action' => 'node-$1',
            'action_datasource' => 'node-$1',
            'action_form_view' => 'node/$1.cft',
            'action_success_view' => 'node/$1.cft',
            'view_handler' => '$2'
        ),

    '/system/(storagedate|localdate)\.(json|xml)/?' =>
        array (
            'action' => 'system-$1',
            'action_datasource' => 'system-$1',
            'action_form_view' => 'system/$1.cft',
            'action_success_view' => 'system/$1.cft',
            'view_handler' => '$2'
        ),
);

?>
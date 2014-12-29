<?php
/**
 * Routing regular expressions for URL style mapping of Controller and Method.
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @package   CrowdFusion
 * @copyright 2009 Crowd Fusion Inc.
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version    $Id: routes.php 1177 2009-10-02 20:24:05Z ryans $
 */

$routes = array (
	'/(?P<controller>[^/]+)/(?P<method>[^/]+)/?'
		=> array('action'=>'$controller-$method', 'b_save'=>true),
);


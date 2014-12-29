<?php
/**
 * RouterInterface
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009-2010 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @package     CrowdFusion
 * @copyright   2009-2010 Crowd Fusion Inc.
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version     $Id: RouterInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * RouterInterface
 *
 * @package     CrowdFusion
 */
interface RouterInterface {

   /**
    * Processes the request URI against the routing configuration and extracts the routing information.
    *
    * @return array Routing variables
    *
    * @throws Exception
    */
	public function route();

	public function checkRedirects();

}
?>

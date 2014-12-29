<?php
/**
 * RedirectService
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
 * @version     $Id: RedirectService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Redirect service implements the PHP Aggregator service to provide access
 * to all redirects active in the system.
 *
 * @package     CrowdFusion
 */
class RedirectService extends AbstractPHPAggregatorService
{
    /**
     * Sets the "subject" used in the Abstract class
     * that defines the type of data we're loading
     *
     * @var string
     */
    protected $subject = "redirects";

}
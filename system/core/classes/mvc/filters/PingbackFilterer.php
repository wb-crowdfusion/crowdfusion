<?php
/**
 * Display filters
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
 * @package     CrowdFusion
 * @copyright   2009 Crowd Fusion Inc.
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version     $Id$
 */

/**
 * Provides pingback utils
 *
 * @package    CrowdFusion-MVC
 * @subpackage Filters
 **/
class PingbackFilterer extends AbstractFilterer
{

    protected $HttpRequest;
    protected $VersionService;
    protected $systemEmailAddress;
    protected $pingbackEnabled;

    public function setPingbackEnabled($pingbackEnabled)
    {
        $this->pingbackEnabled = $pingbackEnabled;
    }

    public function setSystemEmailAddress($systemEmailAddress)
    {
        $this->systemEmailAddress = $systemEmailAddress;
    }

    public function setVersionService(VersionService $VersionService)
    {
        $this->VersionService = $VersionService;
    }

    public function setHttpRequest(HttpRequest $HttpRequest)
    {
        $this->HttpRequest = $HttpRequest;
    }

    protected function getDefaultMethod()
    {
        return "pingback";
    }


    /**
     * Pings a url and returns the contents.
     *
     * Expected Params:
     *  url        string The url to ping
     *  anonymous  string Set to true to make the request anonymous; defaults to false
     *
     * @return string
     */
    public function pingback()
    {
        return;
    }
}
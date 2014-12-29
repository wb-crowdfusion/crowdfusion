<?php
/**
 * Cookies
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
 * @version     $Id: Cookies.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Cookies
 *
 * @package     CrowdFusion
 */
class Cookies
{

    protected $Encryption;
    protected $Request;
    protected $Response;
    protected $RequestContext;
    protected $InputClean;

    public function __construct(EncryptionInterface $Encryption, Request $Request, Response $Response, RequestContext $RequestContext, InputCleanInterface $InputClean)
    {
        $this->Encryption = $Encryption;
        $this->Request = $Request;
        $this->Response = $Response;
        $this->RequestContext = $RequestContext;
        $this->InputClean = $InputClean;
    }


    public function getCookie($name)
    {
        return $this->Request->getCookie($name);
    }

    public function sendCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false)
    {
        $this->Response->sendCookie($name, $value, $expire, $path, $domain, $secure);
    }

    public function sendSecureCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false)
    {
        $this->Response->sendCookie($name, $this->Encryption->encryptSecureCookie($value, $expire), $expire, $path, $domain, $secure);
    }

    public function getSecureCookie($name)
    {
        $cookie = $this->getCookie($name);
        if(empty($cookie))
            return null;

        return $this->Encryption->decryptSecureCookie($cookie);
    }

    public function clearCookie($name, $path = '/', $domain = '', $secure = false)
    {
        $this->Response->clearCookie($name, $path, $domain, $secure);
    }


}

<?php
/**
 * Session
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
 * @version     $Id: Session.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Session
 *
 * @package     CrowdFusion
 */
class Session
{
    protected $Response;
    protected $Logger;

    protected $siteDomain;
    protected $sessionTTL;
    protected $sessionName;
    protected $sessionPersistSessions;

    protected $flash = array();

    public function __construct(Response $Response, LoggerInterface $Logger, $siteDomain, $sessionTtl, $sessionName, $sessionPersistSessions)
    {
        $this->Response               = $Response;
        $this->Logger                 = $Logger;

        $this->siteDomain               = $siteDomain;
        $this->sessionTTL             = $sessionTtl;
        $this->sessionName            = $sessionName;
        $this->sessionPersistSessions = $sessionPersistSessions;

        if(!empty($_SERVER['HTTP_HOST']) && !headers_sent())  {

            #We need to make sure session.gc_maxlifetime is close to the session TTL
            ini_set('session.gc_maxlifetime', $this->sessionTTL);

            session_name($this->sessionName);

            if(isset($_POST["PHPSESSID_FLASH"])) {
                session_id($_POST["PHPSESSID_FLASH"]);
            }

            session_set_cookie_params($this->sessionTTL, '/' , $_SERVER['SERVER_NAME']);
            session_cache_limiter( FALSE );
            //session_cache_limiter('nocache');
            //session_cache_expire(0);
            $this->session_start_nobadchars();

            #If the session is already started, send a cookie extending the lifetime.
            if(!empty($_COOKIE[$this->sessionName]) && $this->sessionPersistSessions){
                $this->Response->sendCookie($this->sessionName, session_id(), time()+60*60*24*6000, '/', $_SERVER['SERVER_NAME'], false);
            }

            $this->flash = $this->getSessionAttribute(null, 'flash');
            //$this->Logger->debug($this->flash);
            $this->removeSessionAttribute(null, 'flash');
            //$this->Logger->debug('Cleared flash');
        }
    }

    public function getID() {
        return session_id();
    }

    public function regenerateID() {
        session_regenerate_id(true);
    }

    public function getMaxInactiveInterval() {
        return session_cache_expire();
    }

    public function setMaxInactiveInterval($minutes) {
        session_cache_expire($minutes);
    }

    public function invalidate() {
        session_destroy();
        session_write_close();
        session_set_cookie_params(time()+10000, '/', $this->sessionPersistSessions, false);
        $this->session_start_nobadchars();
        setcookie($this->sessionName, '', time()+1, '/', $this->sessionPersistSessions, false);
    }


    public function getSessionAttribute($name = null, $namespace = 'default') {
        if(!isset($_SESSION)) return;

        if(isset($name))
            return isset($_SESSION[$namespace][$name]) ? $_SESSION[$namespace][$name] : null;
        else
            return isset($_SESSION[$namespace]) ? $_SESSION[$namespace] : null;
    }

    public function getSessionAttributeNames($namespace = 'default') {
        if(!isset($_SESSION)) return;

        if(isset($namespace) && $namespace == null)
            return array_keys($_SESSION);
        else
            return isset($_SESSION[$namespace])?array_keys($_SESSION[$namespace]):null;
    }

    public function removeSessionAttribute($name = null, $namespace = 'default') {
        if(!isset($_SESSION)) return;

        if(isset($name) && ($name !== null))
            unset($_SESSION[$namespace][$name]);
        else
            unset($_SESSION[$namespace]);
    }

    public function setSessionAttribute($name, $value, $namespace = 'default') {
        if(!isset($_SESSION)) return;

        if ($name == null) {
            $_SESSION[$namespace] = $value;
        } else {
            $_SESSION[$namespace][$name] = $value;
            $this->Logger->debug('Set session attribute ['.$namespace.']['.$name.']: '.$value);
        }
    }

    public function setFlashAttribute($name, $val) {
        $this->setSessionAttribute($name, $val, 'flash');
        $this->flash[$name] = $val;
    }

    public function getFlashAttributes() {
        return $this->flash;
    }

    public function getFlashAttribute($name) {
        if (isset($this->flash[$name])) {
            return $this->flash[$name];
        } else {
            return null;
        }
    }

    public function keepFlashAttribute($name = null) {
        if ($name != null) {
            $val = $this->getFlashAttribute($name);
            if($val !== null)
                $this->setFlashAttribute($name, $val);
        } else {
            $this->setSessionAttribute(null, $this->flash, 'flash');
        }
    }

    public function removeFlashAttribute($name) {
        $this->removeSessionAttribute($name, 'flash');
        unset($this->flash[$name]);
    }


    protected function session_start_nobadchars()
    {
        if (isset($_COOKIE['PHPSESSID'])) {
            $sessid = $_COOKIE['PHPSESSID'];
        } else if (isset($_GET['PHPSESSID'])) {
            $sessid = $_GET['PHPSESSID'];
        } else {
            @session_start();
            return false;
        }

        if (!preg_match('/^[a-z0-9]{32}$/', $sessid)) {
            // If the session gets regenerated each load, it's likely that your session.hash_function is set
            // incorrectly in your php.ini. Set it to '1' (for SHA-1) and ensure 5 bits per character.
            unset($_COOKIE['PHPSESSID']);
            unset($_GET['PHPSESSID']);
            session_regenerate_id(true);
        }
        @session_start();

        return true;
    }

}
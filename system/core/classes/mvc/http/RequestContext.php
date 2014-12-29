<?php
/**
 * RequestContext
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
 * @version     $Id: RequestContext.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * This class encapsulates the variables that exist in the scope of the request (request globals).  An example of a
 * variable that would be needed across the entire request is the User object.  This class also contains the request
 * control variables (@see Controls).
 *
 * @package     CrowdFusion
 */
class RequestContext
{
    protected $attributes = array();

    /**
     * [Autowired] Sets the Controls
     *
     * @param Controls $controls autowired
     *
     * @return void
     */
    public function setControls(Controls $controls)
    {
        $this->setAttribute('controls', $controls);
    }

    /**
     * Returns the controls object associated with this RequestContext
     *
     * @return Controls
     */
    public function getControls()
    {
        return $this->getAttribute('controls');
    }

    /**
     * Sets the user
     *
     * @param mixed $user autowired
     *
     * @return void
     */
    public function setUser($user)
    {
        $this->setAttribute('user', $user);
    }

    /**
     * Returns the user
     *
     * @return mixed
     */
    public function getUser()
    {
        return $this->getAttribute('user');
    }

    /**
     * Sets the user ref
     *
     * @param mixed $user
     *
     * @return void
     */
    public function setUserRef($user)
    {
        $this->setAttribute('userRef', $user);
    }

    /**
     * Returns the user ref
     *
     * @return mixed
     */
    public function getUserRef()
    {
        return $this->getAttribute('userRef');
    }

    public function isAuthenticatedUser()
    {
        return $this->getAttribute('isAuthenticatedUser') == true;
    }

    public function setIsAuthenticatedUser($boolean)
    {
        $this->setAttribute('isAuthenticatedUser', (bool)$boolean);
    }


    /**
     * [Autowired] Sets the Site
     *
     * @param Site $site autowired
     *
     * @return void
     */
    public function setSite(Site $site)
    {
        $this->setAttribute('site', $site);
    }

    /**
     * Returns the Site
     *
     * @return Site
     */
    public function getSite()
    {
        return $this->getAttribute('site');
    }

    public function getStorageFacilityInfo($for)
    {
        return $this->getAttribute('sf_'.$for);
    }

    public function setStorageFacilityInfo($for, StorageFacilityInfo $sfInfo)
    {
        $this->setAttribute('sf_'.$for, $sfInfo);
    }


    /**
     * Used to set attributes of the RequestContext
     *
     * @param string $key The name of the attribute
     * @param mixed  $val The value for the attribute
     *
     * @return void
     */
    public function setAttribute($key, $val)
    {
        if(is_null($val) && array_key_exists($key, $this->attributes))
            unset($this->attributes[$key]);
        else
            $this->attributes[$key] = $val;
    }

    /**
     * Returns the attribute value from the attribute identified by {@link $key}
     *
     * @param string $key The name of the attribute
     *
     * @return mixed The value of the attribute or null
     */
    public function getAttribute($key)
    {
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : null;
    }
}

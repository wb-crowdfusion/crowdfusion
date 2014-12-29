<?php
/**
 * CachedObject provides a standard way of documenting the stored cache items.
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
 * @version     $Id: CachedObject.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * CachedObject provides a standard way of documenting the stored cache items.
 * All cache stores need to be able to generate a cachedobject from their stored items
 *
 * @package    CrowdFusion
 */
class CachedObject
{

    protected $key;
    protected $value;
    protected $creationTime;
    protected $expirationTime;
    protected $duration;


    /**
     * Constructs a cached object.
     *
     * @param string $key            The key for the cached object
     * @param string $value          The stored value
     * @param Date   $creationTime   The time of creation
     * @param Date   $expirationTime The time of expiration
     * @param string $duration       The length to live in the cache
     *
     * @return void
     */
    public function __construct($key, $value, $creationTime = null, $expirationTime = null, $duration = null)
    {
        $this->key            = $key;
        $this->value          = $value;
        $this->creationTime   = $creationTime;
        $this->expirationTime = $expirationTime;
        $this->duration       = $duration;
    }

    /**
     * Returns the Key
     *
     * @return string The key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the value
     *
     * @return mixed value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns creation time
     *
     * @return Date Creation time
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * Returns Expiration Time
     *
     * @return Date expiration time
     */
    public function getExpirationTime()
    {
        return $this->expirationTime;
    }

    /**
     * Returns the initial duration that the cached object will live
     *
     * @return integer duration
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * String output
     *
     * @return string
     */
    public function __toString()
    {
        return "KEY:        {$this->getKey()}\n" .
               "CREATED_AT: " . $this->getCreationTime() . "\n" .
               "EXPIRES_AT: " . $this->getExpirationTime() . "\n" .
               "DURATION:   {$this->getDuration()}";
    }
}

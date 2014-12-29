<?php
/**
 * Cache sink
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
 * @version     $Id: NullCacheStore.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Cache sink
 *
 * Cache sink implementation for CacheStore. Provides a mock-caching object:
 * To the outside, it appears as if NullCacheStore is storing data, but nothing is ever saved.
 *
 * @package     CrowdFusion
 */
class NullCacheStore implements CacheStoreInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Returns false, because we never store anything.
     *
     * @param string $key The key to check
     *
     * @return boolean false
     */
    public function containsKey($key)
    {
        return false;
    }

    /**
     * Returns false, because nothing was stored.
     *
     * @param string $key The cache key to retrieve
     *
     * @return boolean false
     */
    public function get($key)
    {
        return false;
    }

    /**
     * Returns false, because nothing is stored.
     *
     * @param array $keys An array of keys to get
     *
     * @return boolean false
     */
    public function multiGet(array $keys)
    {
        return false;
    }

    /**
     * Returns false
     *
     * @param string $key The key to fetch
     *
     * @return boolean false
     */
    public function getCachedObject($key)
    {
        return false;
    }

    /**
     * Acts like it's storing data, but it doesn't do it.
     * The quest for the Holy Grail could continue.
     *
     * @param string $key  The key to store
     * @param string $data The data that would be stored, if we did anything with it.
     * @param string $ttl  The airspeed velocity of an unladen swallow.
     *
     * @return void
     */
    public function put($key, $data, $ttl)
    {
        return true;
    }

    /**
     * Does nothing
     *
     * @param string $key      Ignored
     * @param string $duration Ignored
     *
     * @return boolean true
     */
    public function updateDuration($key, $duration)
    {
        return true;
    }

    /**
     * Cut down a tree with a herring? It can't be done.
     *
     * @param string $key ignored
     *
     * @return boolean true
     */
    public function delete($key)
    {
        return true;
    }

    /**
     * Ignored.
     *
     * @return array empty array
     */
    public function getStats()
    {
        return array();
    }

    /**
     * Does nothing at all.
     *
     * @return boolean true
     */
    public function expireAll()
    {
        return true;
    }

    /**
     * Placebo function. This will induce happiness in the caller.
     *
     * @return boolean true.
     */
    public function flushExpired()
    {
        return true;
    }

}

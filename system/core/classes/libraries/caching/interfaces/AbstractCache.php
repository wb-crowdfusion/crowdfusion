<?php
/**
 * AbstractCache
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
 * @package   CrowdFusion
 * @copyright 2009-2010 Crowd Fusion Inc.
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   $Id: AbstractCache.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractCache
 *
 * @package   CrowdFusion
 */
abstract class AbstractCache
{

    protected $keyPrefix = null;
    protected $cacheStore = null;
    protected $localCache = array();

    protected $keepLocalCache;

    public function __construct(CacheStoreInterface $cacheStore, $keyPrefix, $keepLocalCache = true)
    {
        $this->cacheStore = $cacheStore;
        $this->keyPrefix = $keyPrefix;
        $this->keepLocalCache = $keepLocalCache;
    }

    protected function cacheKey($key)
    {
        return "{$this->keyPrefix}-{$key}";
    }

    /**
     * Returns an array of nodes from cache specified by key
     *
     * @param array $keys An array of keys to fetch
     * @param bool $localOnly
     *
     * @return array
     */
    public function multiGet(array $keys, $localOnly = false)
    {
        $keys = array_unique($keys);
        $results = array();
        $others = array();
        foreach ($keys as $key) {
            $cacheKey = $this->cacheKey($key);
            if ($this->keepLocalCache && array_key_exists($cacheKey, $this->localCache))
                $results[$cacheKey] = $this->localCache[$cacheKey];
            else
                $others[] = $cacheKey;
        }

        if(!$localOnly) {
            $results = array_merge($results, ($cached = $this->cacheStore->multiGet($others)) == false ? array() : $cached);
            if($this->keepLocalCache && !empty($cached))
		        foreach($cached as $key => $value)
			        $this->localCache[$key] = $value;

            unset($cached);
        }
        unset($keys);
        unset($others);


        return $results;
    }

    /**
     * Stores the value specified with the cacheKey given.
     *
     * @param string $cacheKey The cache key
     * @param mixed  $value    The value to store
     *
     * @access public
     * @return void
     */
    public function put($key, $value, $duration, $localOnly = false)
    {
        $cacheKey = $this->cacheKey($key);
        if($this->keepLocalCache)
            $this->localCache[$cacheKey] = $value;
        if(!$localOnly)
            $this->cacheStore->put($cacheKey, $value, $duration);
    }

    /**
     * undocumented function
     *
     * @param string $cacheKey The cacheKey to remove
     *
     * @access public
     * @return void
     */
    public function delete($key, $localOnly = false)
    {
        $cacheKey = $this->cacheKey($key);
        if($this->keepLocalCache)
            unset($this->localCache[$cacheKey]);
        if(!$localOnly)
            return $this->cacheStore->delete($cacheKey);

        return true;
    }

    /**
     * undocumented function
     *
     * @param string $cacheKey The cacheKey to retrieve
     *
     * @access public
     * @return void
     */
    public function get($key, $localOnly = false)
    {
        $cacheKey = $this->cacheKey($key);

        if($this->keepLocalCache && array_key_exists($cacheKey, $this->localCache))
            return $this->localCache[$cacheKey];

        if(!$localOnly){
            $value = $this->cacheStore->get($cacheKey);
            if($this->keepLocalCache && $value !== false)
                $this->localCache[$cacheKey] = $value;
            return $value;
        }

        return false;
    }

    /**
     * This function is primarily used to prevent old data from
     * persisting locally during long running processes (like gearman).
     */
    public function clearLocalCache(){
        if($this->keepLocalCache){
            $this->localCache = array();
        }
    }

}

<?php
/**
 * Local Cache store
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
 * @version     $Id: LocalCacheStore.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Local implementation for CacheStore
 *
 * @package     CrowdFusion
 */
class LocalCacheStore extends AbstractCacheStore implements CacheStoreInterface
{

    /**
     * Contains our cache prefix key
     *
     * @var string
     */
    protected $cachePrefixKey = '';

    /**
     * Holds our logger
     *
     * @var Logger
     */
    protected $Logger;

    /**
     * Holds the actual cache.
     *
     * @var array
     */
    protected $cache = array();

    /**
     * Determines if the key specified is expired. This functionality is pointless in this class
     * so it's been shortcutted to never mark any item expired.
     *
     * @param string $key The key used to lookup the data.
     *                    If doesn't exist, item is expired, so true is returned.
     *
     * @return boolean true if the data is expired, false otherwise
     */
    protected function isExpired($key)
    {
        return false;
    }


    /**
     * Performs a fetch of our data from the cache without logging in the logger.
     * That allows this method to be used in other functions.
     *
     * @param string $key The key to fetch
     *
     * @return mixed Data in this->storageFormat format or FALSE if data is invalid
     */
    protected function getData($key)
    {
        $cacheKey = $this->key($key);
        return array_key_exists($cacheKey, $this->cache)?$this->cache[$this->key($key)]:false;
    }


    /**
     * Constructs a file cache store object.
     *
     * @param Logger $Logger         our Logger
     * @param string $cachePrefixKey A prefix for the cache keys
     */
    public function __construct(LoggerInterface $Logger, $cachePrefixKey = '')
    {
        // Set our vars
        $this->cachePrefixKey = str_replace(' ', '', $cachePrefixKey);
        $this->Logger         = $Logger;
    }

    /**
     * Returns whether or not the key exists and is not expired in the cache,
     * i.e. there is a valid entry for this key
     *
     * @param string $key Cache key associated to the data
     *
     * @return boolean
     */
    public function containsKey($key)
    {
        if (!$this->enabled)
            return false;

        $has_key = array_key_exists($this->key($key), $this->cache);

        // $this->Logger->debug("Cache contains {$key}? " . (($has_key) ? 'TRUE' : 'FALSE'));

        return $has_key;
    }

    /**
     * Returns valid cache data or false if the key does not exist or is expired
     *
     * @param string $key Cache key associated to the data
     *
     * @return mixed Stored cache data, in the same format that the data was
     *  put into the cache as. Serialization of data should be transparent to
     *  the caller. Returns FALSE if the key was not found or the item has expired.
     */
    public function get($key)
    {
        $cacheKey = $this->key($key);
        return array_key_exists($cacheKey, $this->cache)?$this->cache[$cacheKey]:false;

        // $this->Logger->debug("Get Key: {$key}, Found: " . (($data === false) ? 'FALSE' : "TRUE"));
    }

    /**
     * Returns an associative array of keys to data for all valid keys
     *
     * @param array $keys An array of cache keys to retrieve
     *
     * @return array Associative array of keys to values.  If a key is not found
     *  or expired in the key store, that key will be missing from the
     *  returned array.  If no keys were found, this function will return FALSE.
     */

    public function multiGet(array $keys)
    {
        // $this->Logger->debug("Get Keys: ");
        // $this->Logger->debug($keys);

        $results = array();
        foreach ( $keys as $key ) {
            $cacheKey = $this->key($key);

            $value = array_key_exists($cacheKey, $this->cache)?$this->cache[$cacheKey]:false;
            if ($value !== false)
                $results[$key] = $value;
        }

        return $results;
    }

    /**
     * Returns a complete CachedObject object representing the stored cache data
     * or false if the key does not exist or is expired.
     *
     * A CachedObject object will report useful metadata about the cached value
     * including the key, the data, the creation time, the expire time, and
     * the time-to-live duration in seconds
     *
     * @param string $key Cache key associated to the data
     *
     * @return CachedObject Object representation of stored data and cache metadata.
     *  Returns FALSE if the key was not found or the item has expired.
     */
    public function getCachedObject($key)
    {
        $cacheKey = $this->key($key);
        $value = array_key_exists($cacheKey, $this->cache)?$this->cache[$cacheKey]:false;

        if ($value == false) {

             // $this->Logger->debug("Get CachedObjectKey: {$key}, Found: FALSE");
            return false;

        } else {
            // $now = $this->DateFactory->newStorageDate();

            // Creates a CachedObject and stores the serialized version of it.
            $obj = new CachedObject($key, $value);

             // $this->Logger->debug("Get CachedObjectKey: {$key}, Found: {$obj}");

            return $obj;
        }
    }

    /**
     * Stores data into the cache store by key, with a given timeout in seconds
     *
     * @param string $key  Cache key associated to the data
     * @param mixed  $data Data to be stored in the cache store.  Only serializable
     *                      data types or objects can be stored in the cache.
     * @param int    $ttl  Ignored in this implementation
     *
     * @return boolean True upon success, false on failure
     */
    public function put($key, $data, $ttl)
    {
        // Store the cache object
        $this->cache[$this->key($key)] = $data;

        // $this->Logger->debug("Successfully set record for key '{$key}'.\n");
        // $this->Logger->debug($data);

        return true;
    }

    /**
     * Does nothing in this implementation. Ignore it completely.
     *
     * @param string $key      Cache key associated to the data
     * @param int    $duration New time-to-live or duration in seconds for the
     *                          given key.  If 0, the key will never expire.
     *
     * @return boolean        True upon success, false on failure
     * @throws CacheException If duration < 0
     */
    public function updateDuration($key, $duration)
    {
        return true;
    }

    /**
     * Deletes the stored data associated to the key
     *
     * @param string $key Cache key associated to the data
     *
     * @return boolean True upon success, false on failure
     */
    public function delete($key)
    {
        // $this->Logger->debug("Delete key: {$key}");
        unset($this->cache[$this->key($key)]);
        return true;
    }

    /**
     * Implementation specific array of statistics
     *
     * @return array An associative array of statistics, determined by the
     *  implementation
     */
    public function getStats()
    {
        // throw new CacheException('getStats is not supported for localcache');
        return $this->cache;
    }

    /**
     * Immediately invalidates all keys in the cache store
     *
     * Whether or not the cache store's memory or resource allocation is freed
     * is determined by the implementation
     *
     * @return boolean True on success, false on failure
     */
    public function expireAll()
    {
        // $this->Logger->debug('Expiring all items.');

        $this->cache = array();

        return true;
    }

    /**
     * Removes all expired items from the cache store
     *
     * @return boolean true on success, false on failure
     **/
    public function flushExpired()
    {
        // $this->Logger->debug('Flush all expired called.');
        return true;
    }

}

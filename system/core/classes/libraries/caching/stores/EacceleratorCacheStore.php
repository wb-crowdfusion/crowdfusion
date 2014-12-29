<?php
/**
 * EacceleratorCacheStore
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
 * @version     $Id: EacceleratorCacheStore.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * EacceleratorCacheStore
 *
 * Eaccelerator implementation for CacheStore. Stores items to cache using eAccelerator.
 *
 * NOTE: eAccelerator provides a shared memory heap for an apache server,
 * and does not act as a distributed cache store!
 *
 * @package     CrowdFusion
 */
class EacceleratorCacheStore extends AbstractCacheStore implements CacheStoreInterface
{

    /**
     * Flag to determine if cache system is enabled
     *
     * @var boolean
     */
    protected $enabled;

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

    protected $Benchmark;

    /**
     * Constructs an eaccelerator cache store object.
     *
     * @param BenchmarkInterface $Benchmark                  our Benchmarker
     * @param LoggerInterface    $Logger                     our Logger
     * @param string             $eacceleratorCachePrefixKey A prefix for the cache keys. Default: ''
     * @param boolean            $eacceleratorEnabled        Flag to determine if caching is enabled. Default: false
     */
    public function __construct(BenchmarkInterface $Benchmark,
                                LoggerInterface $Logger,
                                $eacceleratorCachePrefixKey = '',
                                $eacceleratorEnabled = false)
    {
        if ($eacceleratorEnabled) {
            if (!function_exists('eaccelerator_put'))
                throw new CacheException('eAccelerator shared memory functions are not installed');

            // Set our vars
            $this->cachePrefixKey = str_replace(' ', '', $eacceleratorCachePrefixKey);
            $this->Benchmark      = $Benchmark;
            $this->Logger         = $Logger;
            $this->enabled        = true;

            $this->Logger->debug("Set eAccelerator options:\n" .
                                                "\tCachePrefix: {$eacceleratorCachePrefixKey}\n" .
                                                "\tEnabled: TRUE");
        } else {
            $this->enabled = false;
        }
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

        $has_key = (eaccelerator_get($this->key($key)) !== null);

        $this->Logger->debug("Cache contains {$key}? " . (($has_key) ? 'TRUE' : 'FALSE'));

        return $has_key;
    }

    /**
     * Returns valid cache data or false if the key does not exist or is expired
     *
     * @param string $key Cache key associated to the data
     *
     * @return mixed Stored cache data, in the same format that the data was
     *  put into the cache as.  Serialization of data should be transparent to
     *  the caller. Returns FALSE if the key was not found or the item has expired.
     */
    public function get($key)
    {
        if (!$this->enabled)
            return false;

//        $this->Benchmark->start('ea-get-'.substr($key, 0, 60));
        $data = eaccelerator_get($this->key($key));

        // eAccelerator doesn't serialize the object, so we need to handle that ourselves.
        $data = unserialize($data);

        if ($data === false)
            return false;

        // if going to expire in 10 seconds, extend the cache and let this request refresh it
        $duration = $data['duration'];
        $value    = $data['value'];
        if (!empty($value) && $duration > 0) {
            $expire   = $data['expires'];
            $now      = time();

            if ($now > ($expire - 10) ) {
                $this->put($key, $value, $duration);
                return false;
            }
        }
//        $this->Benchmark->end('ea-get-'.substr($key, 0, 60));

        return $value;
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
        if (!$this->enabled)
            return false;

        // $this->Logger->debug("Get Keys: ");
        // $this->Logger->debug($keys);

        $results = array();
        foreach ( $keys as $key ) {
            $data = $this->get($key);
            if ($data !== false)
                $results[$key] = $data;
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
        if (!$this->enabled)
            return false;

        $data = unserialize(eaccelerator_get($this->key($key)));

        if ($data == false) {

            // $this->Logger->debug("Get CachedObjectKey: {$key}, Found: FALSE");
            return false;

        } else {
            // Creates a CachedObject and stores the serialized version of it.
            $obj = new CachedObject($data['key'], $data['value'],
                                    $data['created'],
                                    $data['expires'],
                                    $data['duration']);

            // $this->Logger->debug("Get CachedObjectKey: {$key}, Found: TRUE");

            return $obj;
        }
    }

    /**
     * Stores data into the cache store by key, with a given timeout in seconds
     *
     * The implementation must support native serialization of objects and arrays
     * so that the caller does not need to serialize manually.
     *
     * @param string $key  Cache key associated to the data
     * @param mixed  $data Data to be stored in the cache store.  Only serializable
     *  data types or objects can be stored in the cache.
     * @param int    $ttl  Time-to-live or duration in seconds the value is
     *  retained in the cache store before expiring or becoming invalid. If
     *  set to 0, the value will not expire.
     *
     * @return boolean True upon success, false on failure
     */
    public function put($key, $data, $ttl)
    {
        if (!$this->enabled)
            return true;

        $has_set = false;
        try {
            $data = $this->storageFormat($key, $data, $ttl);

            // Store the cache object
            $has_set = eaccelerator_put($this->key($key), serialize($data), $ttl);

        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }

        $this->Logger->debug("". ($has_set ? "Successfully" : "Failed to") . " set record for key '{$key}'\n");
        // $this->Logger->debug($data);

        return $has_set;
    }

    /**
     * Extends the time-to-live or duration of the given cache key.
     *
     * This is useful for extending the expiration time of a given cache key
     * without the need to get and re-put the value.
     *
     * @param string $key      Cache key associated to the data
     * @param int    $duration New time-to-live or duration in seconds for the
     *  given key.  If 0, the key will never expire.
     *
     * @return boolean        True upon success, false on failure
     * @throws CacheException If duration < 0
     */
    public function updateDuration($key, $duration)
    {
        if (!$this->enabled)
            return true;

        if (!is_numeric($duration) || $duration < 0)
            throw new CacheException("Invalid Duration: {$duration}");

        $storedArray = unserialize(eaccelerator_get($key));

        if (!$storedArray)
            return false;

        $storedArray['expires'] += $duration;
        $storedArray['duration'] = $duration;

        $this->Logger->debug("Update duration for {$key} to {$duration}");
        return eaccelerator_put($this->key($key), serialize($storedArray), $duration);
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
        if (!$this->enabled)
            return true;

        $this->Logger->debug("Delete key: {$key}");
        return eaccelerator_rm($this->key($key));
    }

    /**
     * Implementation specific array of statistics
     *
     * @return array An associative array of statistics, determined by the
     *  implementation
     */
    public function getStats()
    {
        return array_merge(eaccelerator_info(), ini_get_all('eaccelerator'));
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
        if (!$this->enabled)
            return true;

        $this->Logger->debug('Expire all keys');

        $ns = ini_get('eaccelerator.name_space');
        foreach (eaccelerator_list_keys() as $key => $value) {
            eaccelerator_rm(StringUtils::strRight($value['name'], $ns.':'));
        }
    }


    /**
     * Removes all expired items from the cache store
     *
     * @return boolean true on success, false on failure
     **/
    public function flushExpired()
    {
        $this->Logger->debug('Flush all expired called.');
        eaccelerator_gc();
        return true;
    }

}

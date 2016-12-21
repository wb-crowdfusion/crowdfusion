<?php
/**
 * APCCacheStore
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
 * @version     $Id: APCCacheStore.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * APC Cache store
 *
 * The Alternative PHP Cache (APC) is a free and open opcode
 * cache for PHP. It was conceived of to provide a free, open,
 * and robust framework for caching and optimizing PHP intermediate code.
 *
 * @package     CrowdFusion
 */
class APCCacheStore extends AbstractCacheStore implements CacheStoreInterface
{
    /**
     * Flag to determine if cache system is enabled
     *
     * @var boolean
     */
    protected $enabled;

    /**
     * Flag to determine the prefix of an APC function
     *
     * @var boolean
     */
    protected $apcFuncPrefix = 'apc';

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
     * Constructs an APC cache store object.
     *
     * @param Logger  $Logger            our Logger
     * @param string  $apcCachePrefixKey A prefix for the cache keys. Default: ''
     * @param boolean $apcEnabled        Flag to determine if caching is enabled
     */
    public function __construct(LoggerInterface $Logger, $apcCachePrefixKey = '', $apcEnabled = false)
    {
        if ($apcEnabled) {
            if (!function_exists('apc_store') && !function_exists('apcu_store')) {
                throw new CacheException('APC shared memory functions are not installed');
            }

            // Set our vars
            $this->cachePrefixKey = str_replace(' ', '', $apcCachePrefixKey);
            $this->Logger         = $Logger;
            $this->enabled       = true;
            $this->apcFuncPrefix = function_exists('apcu_add') ? 'apcu' : 'apc';


//            $this->Logger->debug("Set APC options:\n" .
//                                                "\tCachePrefix: {$apcCachePrefixKey}\n" .
//                                                "\tEnabled: TRUE");
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

        $has_key = false;

        $fetchMethod = $this->apcFuncPrefix.'_fetch';
        $fetchMethod($this->key($key), $has_key);

        // $this->Logger->debug("Cache contains {$key}? " . (($has_key) ? 'TRUE' : 'FALSE'));

        return $has_key;
    }

    /**
     * Returns valid cache data or false if the key does not exist or is expired
     *
     * It should be noted that apc_store appears to only store one level deep.
     * So if you have an array of arrays, and you store it. When you pull it
     * back out with apc_fetch it will only have the top level row of keys
     * with nulls as the values of each key.
     *
     * Solution to this, is to serialize the data before storing it in the
     * cache and unserialize it while retrieving from the cache.
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

//        $this->Logger->debug("Get Key: ".$key);


        $key_fetched = false;
        $apcMethod   = $this->apcFuncPrefix.'_fetch';
        $data        = unserialize($apcMethod($this->key($key), $key_fetched));

//        $this->Logger->debug($data);

        if ($key_fetched === false)
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

        return $value;
    }

    /**
     * Returns an associative array of keys to data for all valid keys
     *
     * @param array $keys An array of cache keys to retrieve
     *
     * @return array|bool Associative array of keys to values.  If a key is not found
     *  or expired in the key store, that key will be missing from the
     *  returned array.  If no keys were found, this function will return FALSE.
     */

    public function multiGet(array $keys)
    {
        if (!$this->enabled)
            return false;

//         $this->Logger->debug("Get Keys: ");
//         $this->Logger->debug($keys);

        foreach ($keys as $key => $value) {
            $keys[$key] = (string) $this->key($value);
        }

        // Fetch the CachedObjects
        $apcMethod = $this->apcFuncPrefix.'_fetch';
        $data = $apcMethod($keys);

        $results = array();
        if ($data !== false) {

            // Translate them to abstract away the storageFormat stuff
            foreach ( (array)$data as $storedArray ) {
                $storedArray = unserialize($storedArray);
                $results[$storedArray['key']] = $storedArray['value'];
            }
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
     * @return CachedObject|bool Object representation of stored data and cache metadata.
     *  Returns FALSE if the key was not found or the item has expired.
     */
    public function getCachedObject($key)
    {
        if (!$this->enabled)
            return false;

        $key_fetched = false;
        $apcMethod = $this->apcFuncPrefix.'_fetch';
        $data = unserialize($apcMethod($this->key($key), $key_fetched));

        if ($key_fetched == false) {

            // $this->Logger->debug("Get CachedObjectKey: {$key}, Found: FALSE");
            return false;

        } else {
            // Creates a CachedObject and stores the serialized version of it.
            $obj = new CachedObject($data['key'], $data['value'],
                                    $data['created'],
                                    $data['expires'],
                                    $data['duration']);

//             $this->Logger->debug("Get CachedObjectKey: {$key}, Found: {$obj}");

            return $obj;
        }
    }

    /**
     * Stores data into the cache store by key, with a given timeout in seconds
     *
     * It should be noted that apc_store appears to only store one level deep.
     * So if you have an array of arrays, and you store it. When you pull it
     * back out with apc_fetch it will only have the top level row of keys
     * with nulls as the values of each key.
     *
     * Solution to this, is to serialize the data before storing it in the
     * cache and unserialize it while retrieving from the cache.
     *
     * @param string $key  Cache key associated to the data
     * @param mixed  $data Data to be stored in the cache store.  Only serializable
     *  data types or objects can be stored in the cache.
     * @param int    $ttl  Time-to-live or duration in seconds the value is
     *  retained in the cache store before expiring or becoming invalid. If
     *  set to 0, the value will not expire.
     *
     * @return array|bool True upon success, false on failure
     */
    public function put($key, $data, $ttl)
    {
        if (!$this->enabled)
            return true;

        $has_set = false;
        try {
            $data = $this->storageFormat($key, $data, $ttl);

            $serializedData =  serialize($data);

            // Store the cache object
            $apcMethod = $this->apcFuncPrefix.'_store';
            $has_set   = $apcMethod($this->key($key), $serializedData, $ttl);

        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }

        $this->Logger->debug("". ($has_set ? "Successfully" : "Failed to") . " set record for key '{$key}'\n");
//         $this->Logger->debug($data);

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

        $has_key     = false;
        $apcMethod   = $this->apcFuncPrefix.'_fetch';
        $storedArray = unserialize($apcMethod($this->key($key), $has_key));

        if (!$has_key)
            return false;

        $storedArray['expires'] += $duration;
        $storedArray['duration'] = $duration;

        $this->Logger->debug("Update duration for {$key} to {$duration}");

        $apcMethod = $this->apcFuncPrefix.'_store';
        return $apcMethod($this->key($key), serialize($storedArray), $duration);
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

        $apcMethod = $this->apcFuncPrefix.'_delete';
        return $apcMethod($this->key($key));
    }

    /**
     * Implementation specific array of statistics
     *
     * @return array An associative array of statistics, determined by the
     *  implementation
     */
    public function getStats()
    {
        $apcMethod = $this->apcFuncPrefix.'_cache_info';
        return array_merge($apcMethod(), ini_get_all('apc'));
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

        $apcMethod = $this->apcFuncPrefix.'_clear_cache';
        $result = $apcMethod();

        if ($this->apcFuncPrefix == 'apc') {
            return $apcMethod('user');
        }

        return $result;
    }

    /**
     * Removes all expired items from the cache store
     *
     * @return boolean true on success, false on failure
     **/
    public function flushExpired()
    {
        $this->Logger->debug('Flush all expired called.');
        return true; // Handled automatically.
    }
}

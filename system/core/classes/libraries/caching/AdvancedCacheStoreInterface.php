<?php

interface AdvancedCacheStoreInterface
{
    /**
     * Stores data into the cache store by key, with a given timeout in seconds
     * UNLESS an entry already exists under that key.
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
    public function add($key, $data, $ttl);

    /**
     * Increment a value in the cache store
     *
     * @abstract
     * @param string $key  Cache key associated to the data
     * @param int    $step Number to increment by
     * @return int New value on success or FALSE on failure
     *
     * @throws CacheException
     */
    public function increment($key, $step = 1);

    /**
     * Decrement a value in the cache store
     *
     * @abstract
     * @param string $key  Cache key associated to the data
     * @param int    $step Number to decrement by
     * @return int New value on success or FALSE on failure
     *
     * @throws CacheException
     */
    public function decrement($key, $step = 1);

    /**
     * Get incremented value in the cache store
     *
     * @abstract
     * @param string $key  Cache key associated to the data
     * @return int Stored value on success or FALSE on failure
     *
     * @throws CacheException
     */
    public function getIncrement($key);
}
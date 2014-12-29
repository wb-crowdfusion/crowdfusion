<?php

interface CacheStoreInterface
{
    /**
     * Returns whether or not the key exists and is not expired in the cache,
     * i.e. there is a valid entry for this key
     *
     * @param string $key Cache key associated to the data
     *
     * @return bool
     */
    public function containsKey($key);

    /**
     * Returns valid cache data or false if the key does not exist or is expired
     *
     * @param string $key Cache key associated to the data
     *
     * @return mixed Stored cache data, in the same format that the data was
     *  put into the cache as.  Serialization of data should be transparent to
     *  the caller. Returns FALSE if the key was not found or the item has expired.
     */
    public function get($key);

    /**
     * Returns an associative array of keys to data for all valid keys
     *
     * @param array $keys An array of cache keys to retrieve
     *
     * @return array Associative array of keys to values.  If a key is not found
     *  or expired in the key store, that key will be missing from the
     *  returned array.  If no keys were found, this function will return FALSE.
     */
    public function multiGet(array $keys);

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
     *  Returns false if the key was not found or the item has expired.
     */
    public function getCachedObject($key);

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
     * @return bool True upon success, false on failure
     */
    public function put($key, $data, $ttl);

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
     * @return bool           True upon success, false on failure
     * @throws CacheException If duration < 0
     */
    public function updateDuration($key, $duration);

    /**
     * Deletes the stored data associated to the key
     *
     * @param string $key Cache key associated to the data
     *
     * @return bool True upon success, false on failure
     */
    public function delete($key);

    /**
     * Implementation specific array of statistics
     *
     * @return array An associative array of statistics, determined by the
     *  implementation
     */
    public function getStats();

    /**
     * Immediately invalidates all keys in the cache store
     *
     * Whether or not the cache store's memory or resource allocation is freed
     * is determined by the implementation
     *
     * @return bool True on success, false on failure
     */
    public function expireAll();

    /**
     * Removes all expired items from the cache store
     *
     * @return bool true on success, false on failure
     **/
    public function flushExpired();
}
<?php
/**
 * MemcacheCacheStore
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
 * @version     $Id: MemcachedCacheStore.php 952 2012-06-05 14:59:09Z clayhinson $
 */

/**
 * MemCache implementation for CacheStore. Stores items to cache using memcached.
 *
 * @package     CrowdFusion
 */
class MemcacheCacheStore extends AbstractCacheStore implements CacheStoreInterface, AdvancedCacheStoreInterface
{
    /**
     * Flag to determine if memcache is enabled
     *
     * @var boolean
     */
    protected $enabled;

    /**
     * Holds our memcache instance
     *
     * @var Memcache
     */
    protected $memcache;

    /**
     * Contains our cache prefix key
     *
     * @var string
     */
    protected $cachePrefixKey = '';

    /**
     * Flags used in memcache setters
     *
     * @var boolean
     */
    protected $flags;

    /**
     * Holds our logger
     *
     * @var Logger
     */
    protected $Logger;

    /**
     * Builds a MemcacheCacheStore object that accesses the {@link $servers} specified.
     * */

    /**
     * Servers is a complex array like the following:
     * <code>
     *  $servers = array(
     *      array(
     *          'host'             => 'memcache.host', # Memcache server hostname
     *
     *          'port'             => '11211',         # (optional) Port to connect to. Default 11211 if not specified.
     *
     *          'persistent'       => true,            # (optional) Controls the use of persistent connection. Default to TRUE.
     *
     *          'weight'           => 1,               # (optional) Number of buckets to create for this server which
     *                                                 # in turn control its probability of it being selected.
     *                                                 # The probability is relative to the total weight of all servers.
     *
     *          'timeout'          => 1,               # (optional) Value in seconds which will be used for connecting to the daemon.
     *                                                 # Think twice before changing the default value of 1 second -
     *                                                 # you can lose all the advantages of caching if your connection is too slow.
     *
     *          'retry_interval'   => 15,              # (optional) Controls how often a failed server will be retried,
     *                                                 # the default value is 15 seconds. Setting this parameter to -1
     *                                                 # disables automatic retry.
     *
     *          'status'           => true,            # (optional) Controls if the server should be flagged as online.
     *                                                 # Setting this parameter to FALSE and retry_interval
     *                                                 # to -1 allows a failed server to be kept in the pool
     *                                                 # so as not to affect the key distribution algorithm.
     *                                                 # Requests for this server will then failover or fail
     *                                                 # immediately depending on the memcache.allow_failover
     *                                                 # setting. Default to TRUE, meaning the server should be considered online.
     *
     *          'failure_callback' => 'callback'       # (optional) Allows the user to specify a callback function to run
     *                                                 # upon encountering an error. The callback is run before
     *                                                 # failover is attempted. The function takes two parameters,
     *                                                 # the hostname and port of the failed server.
     *      )
     *  )
     * </code>
     *
     * @param BenchmarkInterface $Benchmark
     * @param LoggerInterface $Logger
     * @param array $memcacheServers
     * @param $memcacheCompression
     * @param $memcacheCachePrefixKey
     * @param bool $memcacheEnabled
     *
     * @throws CacheException
     */
    public function __construct(
            BenchmarkInterface $Benchmark,
            LoggerInterface $Logger,
            array $memcacheServers,
            $memcacheCompression,
            $memcacheCachePrefixKey,
            $memcacheEnabled = false
    ) {
        if ($memcacheEnabled) {
            if (!class_exists('Memcache')) {
                throw new CacheException('Memcache extension not installed');
            }

            $this->Benchmark      = $Benchmark;
            $this->Logger         = $Logger;
            $this->flags          = $memcacheCompression == true ? MEMCACHE_COMPRESSED : false;
            $this->cachePrefixKey = str_replace(' ', '', $memcacheCachePrefixKey);
            $this->enabled        = true;
            $this->memcachedServers = $memcacheServers;
        } else {
            $this->enabled = false;
        }
    }

    /**
     * Returns a connection to the memcached server
     *
     * @return Memcache an active Memcache object
     */
    protected function getConnection()
    {
        if (empty($this->memcache)) {

            //ini_set('memcache.chunk_size', 32768);
            //ini_set('memcache.allow_failover', false); // default is usually true
            //ini_set('memcache.hash_strategy', 'consistent');

            $this->memcache = new Memcache;

            // Add the servers
            if (!is_array($this->memcachedServers) || count($this->memcachedServers) < 1)
                throw new CacheException('At least one server must be specified.');

            foreach ( $this->memcachedServers as $server ) {
                $options = array_merge(array('port'             => 11211,
                                             'persistent'       => true,
                                             'weight'           => 1,
                                             'timeout'          => 1,
                                             'retry_interval'   => 15,
                                             'status'           => true,
                                             'failure_callback' => null), array_change_key_case($server, CASE_LOWER));

                $this->Logger->debug("Adding memcached server:\n");
                $this->Logger->debug($options);

//                if(!array_key_exists('failure_callback', $options))
//                    $options['failure_callback'] = array(__CLASS__, '_failureCallback');

                if ($this->memcache->addServer($options['host'], $options['port'],
                                            $options['persistent'], $options['weight'],
                                            $options['timeout'], $options['retry_interval'],
                                            $options['status'], $options['failure_callback']) === false) {
                    throw new CacheException("Unable to add memcache server: {$options['host']}");
                }
            }
        }

        return $this->memcache;
    }

//    public function _failureCallback($host, $tcpPort, $udpPort, $errorMsg, $errorNum)
//    {
//        $this->Logger->error("Memcache failure [$host][$tcpPort][$udpPort]: ($errorNum) - $errorMsg");
//    }

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

        $contains = ($this->get($key) !== false);

//        $this->Logger->debug("Cache contains {$key}? " . (($contains == true) ? 'TRUE' : 'FALSE'));

        return $contains;
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

//        $this->Benchmark->start('mc-get-'.$key);
        $data = @$this->getConnection()->get($this->key($key));

        if ($data !== false) {
            // if going to expire in 10 seconds, extend the cache and let this request refresh it
            $duration = $data['duration'];
            $value    = $data['value'];
            if(!empty($value) && $duration > 0)
            {
                $expire   = $data['expires'];
                $now      = time();

                if($now > ($expire - 10) ) {
                    $this->Logger->debug('Dogpile prevention on key ['.$key.']');
                    $this->put($key, $value, $duration);
                    return false;
                }
            }
            $data = $value;
        }

//        $this->Benchmark->end('mc-get-'.$key);
        return $data;
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

//        $this->Logger->debug("Get Keys: ");
//        $this->Logger->debug($keys);

//        $this->Benchmark->start('mc-multiget');
        foreach ( $keys as &$key )
            $key = $this->key($key);

        // Fetch the CachedObjects
        $data = @$this->getConnection()->get($keys);
//        $this->Benchmark->end('mc-multiget');

        if ($data !== false) {

            // Translate them to abstract away the CachedObject stuff
            $results = array();
            foreach ( (array)$data as $storedArray )
                $results[$storedArray['key']] = $storedArray['value'];

            unset($data);
            unset($keys);

            return $results;
        }

        return false;
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

        $data = @$this->getConnection()->get($this->key($key));

        if ($data == false) {

//            $this->Logger->debug("Get CachedObjectKey: {$key}, Found: FALSE");
            return false;

        } else {
            // Creates a CachedObject and stores the serialized version of it.
            $obj = new CachedObject($data['key'], $data['value'],
                                    $data['created'],
                                    $data['expires'],
                                    $data['duration']);

//            $this->Logger->debug("Get CachedObjectKey: {$key}, Found: TRUE");

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

        $has_replaced = $has_set = false;
        try {
            $data = $this->storageFormat($key, $data, $ttl);
            $nKey = $this->key($key);

            // Store the cache object
            $has_replaced = @$this->getConnection()->replace($nKey, $data, $this->flags, $ttl);
            if (!$has_replaced) {
                $has_set = @$this->getConnection()->set($nKey, $data, $this->flags, $ttl);
            }

        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }

        $this->Logger->debug("". (($has_replaced || $has_set) ? "Successful" : "Failed to") . " set '{$key}'. Hash: '{$nKey}'\n");
//        $this->Logger->debug($data);

        return $has_replaced || $has_set;
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

        $storedArray = $this->get($key);

        if (!$storedArray)
            return false;

        $storedArray['expires'] += $duration;
        $storedArray['duration'] = $duration;

        $this->Logger->debug("Update duration for {$key} to {$duration}");
        $ret = @$this->getConnection()->replace($this->key($key), $storedArray, $this->_flags, $duration);

        $this->Logger->debug("". (($ret) ? "Successful" : "Failed to") . " update duration on '{$key}'\n");

        return $ret;

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

        $ret = @$this->getConnection()->delete($this->key($key));

        $this->Logger->debug("". (($ret) ? "Successful" : "Failed to") . " delete '{$key}'\n");

        return $ret;
    }

    /**
     * Implementation specific array of statistics
     *
     * @return array An associative array of statistics, determined by the
     *  implementation
     */
    public function getStats()
    {
        if (!$this->enabled)
            return array();

        $stats = @$this->getConnection()->getStats();

        $this->Logger->debug('Retrieved Stats');
        $this->Logger->debug($stats);

        return $stats;
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
        if (@$this->getConnection()->flush()) {
            $time = time() + 1;
            while (time() < $time) {
                // Wait until the next second, so we can be assured our flush() won't carry icky side effects.
                // See: http://us3.php.net/manual/en/function.memcache-flush.php#81420
            }
            return true;
        } else {
            return false;
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
        return true; // Handled automatically.
    }


    /**
     * Increment a value in the cache store
     *
     * @param string $key  Cache key associated to the data
     * @param int    $step Number to increment by
     * @return int New value on success or FALSE on failure
     *
     * @throws CacheException
     */
    public function increment($key, $step = 1)
    {
        if (!$this->enabled)
            return false;

        $has_replaced = $has_set = false;
        try {
            // Store the cache object
            $nKey = $this->key($key);
            $has_replaced = @$this->getConnection()->add($nKey, $step, 0);
            if (!$has_replaced) {
                $has_set = @$this->getConnection()->increment($nKey, $step);
            }

        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }

        $this->Logger->debug("". (($has_replaced || $has_set) ? "Successfully" : "Failed to") . " increment key '{$key}'\n");
//        $this->Logger->debug($data);

        return $has_replaced ? $step : $has_set;
    }

    /**
     * Decrement a value in the cache store
     *
     * @param string $key  Cache key associated to the data
     * @param int    $step Number to decrement by
     * @return int New value on success or FALSE on failure
     *
     * @throws CacheException
     */
    public function decrement($key, $step = 1)
    {
        if (!$this->enabled)
            return false;

        $has_replaced = $has_set = false;
        try {
            // Store the cache object
            $nKey = $this->key($key);
            $has_set = @$this->getConnection()->decrement($nKey, $step);

        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }

        $this->Logger->debug("". (($has_replaced || $has_set) ? "Successfully" : "Failed to") . " decrement key '{$key}'\n");
//        $this->Logger->debug($data);

        return $has_set;

    }


    /**
     * Get incremented value in the cache store
     *
     * @param string $key  Cache key associated to the data
     * @return int Stored value on success or FALSE on failure
     *
     * @throws CacheException
     */
    public function getIncrement($key)
    {

        if (!$this->enabled)
            return false;

        return @$this->getConnection()->get($this->key($key));
    }

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
    public function add($key, $data, $ttl)
    {
        if (!$this->enabled)
            return true;

        try {
            $data = $this->storageFormat($key, $data, $ttl);
            $nKey = $this->key($key);

            // Store the cache object
            $ret = @$this->getConnection()->add($nKey, $data, $this->flags, $ttl);

            $this->Logger->debug("". (($ret) ? "Successful" : "Failed to") . " add '{$key}'\n");

            return $ret;

        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }
    }
}

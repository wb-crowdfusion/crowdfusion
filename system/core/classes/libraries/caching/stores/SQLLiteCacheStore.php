<?php
/**
 * SQLLiteCacheStore
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
 * @version     $Id: SQLLiteCacheStore.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SQLite: Stores items to cache using sqlite.
 *
 * Sqlite implementation for CacheStore. Stores items to cache using sqlite.
 *
 * @package     CrowdFusion
 */
class SQLLiteCacheStore extends AbstractCacheStore implements CacheStoreInterface
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


    /**
     * holds our db
     *
     * @var SQLiteDatabase
     */
    protected $db;

    /**
     * Determines if the specified table exists
     *
     * @param string $table The table name
     *
     * @return boolean true if exists
     */
    protected function sqliteTableExists($table)
    {
        $result = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
        return $result->numRows() > 0;
    }

    /**
     * Retrieves the data from the database
     *
     * @param string $key The key to retrieve
     *
     * @return array An array containing 'key', 'value', and 'expire' keys
     */
    protected function getData($key)
    {
        $query  = $this->db->query("SELECT value FROM cache WHERE key='" . $this->key($key) . "' AND expire >= datetime('now')");
        return $query->fetch();
    }

    /**
     * Writes the data to the database
     *
     * @param string  $key   The key to write
     * @param string  $value The value to store
     * @param integer $ttl   The length of time in seconds for the data to live.
     *
     * @return boolean true if successful
     */
    protected function writeData($key, $value, $ttl)
    {
        if (!is_numeric($ttl) || $ttl < 0)
            throw new CacheException('TTL invalid: '. $ttl);

        if ($ttl == 0)
            $expiration = "datetime('now', '+100 years')"; // This will make the cache expire after you die, so it's effectively forever.
        else
            $expiration = "datetime('now', '+{$ttl} seconds')";

        return $this->db->queryExec("INSERT OR REPLACE INTO cache (key, value, expire) VALUES ('" . $this->key($key) . "', '".
                                                                                        sqlite_escape_string($value) . "',".
                                                                                        "{$expiration})");
    }

    /**
     * Generates the key to store based on the cachePrefix and given key
     *
     * @param string $key The key we'll use to generate our key
     *
     * @return string A unique key with cacheprefix
     */
    protected function key($key)
    {
        return sqlite_escape_string(parent::key($key));
    }


    /**
     * Constructs a sqlite cache store object.
     *
     * @param Logger  $Logger         our Logger
     * @param string  $databaseFile   The filename that contains the database
     * @param string  $cachePrefixKey A prefix for the cache keys
     * @param boolean $enabled        Flag to determine if caching is enabled
     */
    public function __construct(LoggerInterface $Logger, $sqlliteDatabaseFile, $sqlliteCachePrefixKey, $sqlliteEnabled = false)
    {
        if ($sqlliteEnabled) {
            if (!class_exists('SQLiteDatabase'))
                throw new CacheException('SQLiteDatabase is not active.');

            // Set our vars
            $this->cachePrefixKey = str_replace(' ', '', $sqlliteCachePrefixKey);
            $this->Logger         = $Logger;
            $this->enabled       = $sqlliteEnabled;

            $error = '';
            $this->db = new SQLiteDatabase($sqlliteDatabaseFile, 0666, $error);
            if ($this->db == false) {
                throw new CacheException("Could not open db: " . $error);
            }

            $this->Logger->debug("Set sqlite options:\n" .
                                                "\tDatabase: {$sqlliteDatabaseFile}\n".
                                                "\tCachePrefix: {$sqlliteCachePrefixKey}\n" .
                                                "\tEnabled: TRUE");

            if (!$this->sqliteTableExists('cache')) {
                $this->db->queryExec('CREATE TABLE cache (key TEXT UNIQUE, value BLOB, expire TIMESTAMP)');
            }

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

        $result = $this->db->query("SELECT key FROM cache WHERE key='" . $this->key($key) . "' WHERE expire >= datetime('now')");
        $has_key = ($result->numRows() > 0);

        $this->Logger->debug("Cache contains {$key}? " . ($has_key) ? 'TRUE' : 'FALSE');

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

        $data = $this->getData($key);

        if ($data !== false) {
            $data = unserialize($data['value']);

            // if going to expire in 10 seconds, extend the cache and let this request refresh it
            $duration = $data['duration'];
            $value    = $data['value'];
            if(!empty($value) && $duration > 0)
            {
                $expire   = $data['expires'];
                $now      = time();

                if($now > ($expire - 10) ) {
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

        $result = $this->getData($key);

        if ($result == false) {

//            $this->Logger->debug("Get CachedObjectKey: {$key}, Found: FALSE");
            return false;

        } else {
            $data = unserialize($result['value']);

            // Creates a CachedObject and stores the serialized version of it.
            $obj = new CachedObject($data['key'], $data['value'],
                                    $data['created'],
                                    $data['expires'],
                                    $data['duration']);

//            $this->Logger->debug("Get CachedObjectKey: {$key}, Found: {$obj}");

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

        $data    = $this->storageFormat($key, $data, $ttl);
        $has_set = $this->writeData($key, serialize($data), $ttl);

        $this->Logger->debug(($has_set ? "Successfully" : "Failed to") . " set record.\n");
//        $this->Logger->debug($data);

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

        $result = $this->getData($key);

        if (!$result)
            return false;

        $storedArray = unserialize($result['value']);

        $storedArray['expires'] += $duration;
        $storedArray['duration'] = $duration;

        $this->Logger->debug("Update duration for {$key} to {$duration}");

        // $has_set = $this->db->queryExec("UPDATE cache SET ".
        //                                     "value = '" . sqlite_escape_string($storedArray) . "', " .
        //                                     "expire = datetime(expire, '+{$duration} seconds') ".
        //                                 "WHERE key='" . $this->key($key) . "'");

        return $this->writeData($this->key($key), serialize($storedArray), $duration);
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
        return $this->db->queryExec("DELETE FROM cache WHERE key='" . $this->key($key). "'");
    }

    /**
     * Implementation specific array of statistics
     *
     * @return array An associative array of statistics, determined by the
     *  implementation
     */
    public function getStats()
    {
        throw new CacheException('getStats is not supported for sqlite');
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
        $this->Logger->debug('Removing all items from sqlite cache.');
        return $this->db->queryExec("DELETE FROM cache");
    }


    /**
     * Removes all expired items from the cache store
     *
     * @return boolean true on success, false on failure
     **/
    public function flushExpired()
    {
        $this->Logger->debug('Flush all expired called.');
        return $this->db->queryExec("DELETE FROM cache WHERE expire < datetime('now')");
    }

}

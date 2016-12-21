<?php
/**
 * FileCacheStore
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
 * @version     $Id: FileCacheStore.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * File implementation for CacheStore. Stores items to cache using the filesystem. Each cache key represents a unique file.
 *
 * The file cache store stores items to files and may not provide
 * the same performance as the other cache stores.
 *
 * @package     CrowdFusion
 */
class FileCacheStore extends AbstractCacheStore implements CacheStoreInterface
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
     * The directory where files will be stored.
     *
     * @var string
     */
    protected $dir;

    /**
     * Holds our logger
     *
     * @var Logger
     */
    protected $Logger;

    /**
     * Holds our DateFactory
     *
     * @var DateFactory
     */
    protected $DateFactory;

    /**
     * The file extension for cache file
     *
     * @var string
     */
    protected $fileExtension;

    /**
     * Generates the filename to store based on the cachePrefix and given key
     *
     * @param string $key The key we'll use to generate our key
     *
     * @return string A unique filename
     */
    protected function file($key)
    {
        return $this->dir . DIRECTORY_SEPARATOR . $this->key($key) . "." . $this->fileExtension;
    }

    /**
     * Determines if the key or storage array specified is expired
     *
     * @param string $key          The key used to lookup the data.
     *                             If doesn't exist, item is expired, so true is returned.
     *                             This value may be null if a value is specified for $storageArray
     * @param array  $storageArray The stored value to check (in case it's already loaded)
     *
     * @return boolean true if the data is expired, false otherwise
     */
    protected function isExpired($key, $storageArray = null)
    {
        if (!empty($key)) {
            // Item is expired if key doesn't exist
            if (!$this->containsKey($key))
                return true;

            $storageArray = $this->readFile($this->file($key));
        }

        if (is_array($storageArray)) {
            if ($storageArray['expires'] >= time() && $storageArray['duration'] > 0)
                return true;
            else
                return false;
        } else {
            throw new CacheException("isExpired called incorrectly. key = '{$key}', storageArray = '{$storageArray}'");
        }
    }


    /**
     * Performs a fetch of our data from the filecache without logging in the logger.
     * That allows this method to be used in other locations.
     *
     * @param string $key The key to fetch
     *
     * @return mixed Data in this->storageFormat format or FALSE if data is invalid
     */
    protected function getData($key)
    {
        $filename = $this->file($key);
        if (!file_exists($filename))
            return false;
        else {
            $data = $this->readFile($filename);

            if ($this->isExpired(null, $data))
                return false;
            else {
                 // if going to expire in 10 seconds, extend the cache and let this request refresh it
                $expire   = $data['expires'];
                $value    = $data['value'];
                $duration = $data['duration'];
                $now      = $this->DateFactory->newStorageDate();
                if (!empty($value) && $duration > 0 && $now->toUnix() > ($expire - 10) ) {
                    $this->put($key, $value, $duration);
                    return false;
                }

                return $data;
            }
        }

    }


    /**
     * Performs a read from the filesystem.
     *
     * @param string $filename The filename to read
     *
     * @return mixed The data that was stored in $filename
     */
    protected function readFile($filename)
    {
        $data = file_get_contents($filename);
        $data = unserialize($data);
        return $data;
    }

    /**
     * Performs a write to the filesystem of our data.
     *
     * @param string $filename The filename to write to
     * @param string $value    The data to write.
     *
     * @return bool
     */
    protected function writeFile($filename, $value)
    {
        try {
            FileSystemUtils::safeFilePutContents($filename, serialize($value));
            return true;
        } catch (Exception $e)
        {
            return false;
        }
    }


    /**
     * Constructs a file cache store object.
     *
     * @param Logger      $Logger             our Logger
     * @param DateFactory $DateFactory        IoC Datefactory
     * @param string      $fileCacheDirectory The absolute path where our files will be stored without trailing '/'
     * @param string      $fileCachePrefixKey A prefix for the cache keys
     * @param boolean     $fileCacheEnabled   Flag to determine if caching is enabled
     * @param string      $fileCacheExtension The file extension to use for cache files.
     */
    public function __construct(LoggerInterface $Logger,
                                DateFactory $DateFactory,
                                $fileCacheDirectory,
                                $fileCacheCachePrefixKey = '',
                                $fileCacheEnabled = false,
                                $fileCacheExtension = 'cfcache')
    {
        if ($fileCacheEnabled) {

            // Verify the directory exists and is writable
            if(!is_writable($fileCacheDirectory) && !@FileSystemUtils::recursiveMkdir($fileCacheDirectory))
                throw new CacheException("Directory '{$fileCacheDirectory}' does not exist or is not writable.");

            // Remove any trailing DIRECTORY_SEPARATOR passed in
            // For example '/home/crowdfusion/filestore/' becomes '/home/crowdfusion/filestore'
            $fileCacheDirectory = rtrim($fileCacheDirectory, DIRECTORY_SEPARATOR);

            // Set our vars
            $this->cachePrefixKey = str_replace(' ', '', $fileCacheCachePrefixKey);
            $this->Logger         = $Logger;
            $this->DateFactory    = $DateFactory;
            $this->dir            = $fileCacheDirectory;
            $this->enabled        = $fileCacheEnabled;
            $this->fileExtension  = $fileCacheExtension;

            $this->Logger->debug("Set FileStore options:\n" .
                                                "\tCachePrefix: {$fileCacheCachePrefixKey}\n" .
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

        $has_key = file_exists($this->file($key));

//        $this->Logger->debug("Cache contains {$key}? " . ($has_key) ? 'TRUE' : 'FALSE');

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

        if ($data === false)
            return false;

        return $data['value'];
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

        $key_fetched = false;

        $data = $this->getData($key);

        if ($data == false) {

//            $this->Logger->debug("Get CachedObjectKey: {$key}, Found: FALSE");
            return false;

        } else {
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
            $has_set = $this->writeFile($this->file($key), $data);

        } catch (Exception $e) {
            throw new CacheException($e->getMessage(), $e->getCode());
        }

//        $this->Logger->debug(($has_set ? "Successfully" : "Failed to") . " set record.\n");
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

        $storedArray = $this->getData($key);

        if ($storedArray !== false) {
            $storedArray['expires'] += $duration;
            $storedArray['duration'] = $duration;

//            $this->Logger->debug("Update duration for {$key} to {$duration}");
            $has_set = $this->writeFile($this->file($key), $storedArray);

            return $has_set !== false;
        } else {
            return false;
        }


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

//        $this->Logger->debug("Delete key: {$key}");
        return unlink($this->file($key));
    }

    /**
     * Implementation specific array of statistics
     *
     * @return array An associative array of statistics, determined by the
     *  implementation
     */
    public function getStats()
    {
        throw new CacheException('getStats is not supported for filecache');
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
        $this->Logger->debug('Expiring all items.');

        try {
            foreach ( new DirectoryIterator($this->dir) as $item ) {
                $filename = $item->getPath() .DIRECTORY_SEPARATOR.$item->getFilename();
                $pathinfo = pathinfo($filename);

                // Find all files ending with .cfcache and destroy them!
                if ($pathinfo['extension'] == $this->fileExtension)
                    unlink($item->getPath() .DIRECTORY_SEPARATOR.$item->getFilename());
            }
        } catch (Exception $e) {
            $this->Logger->error("expireAll error: " . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Removes all expired items from the cache store
     *
     * @return boolean true on success, false on failure
     **/
    public function flushExpired()
    {
        $this->Logger->debug('Flush all expired called.');

        try {
            foreach ( new DirectoryIterator($this->dir) as $item ) {
                $filename = $item->getPath() .DIRECTORY_SEPARATOR.$item->getFilename();
                $pathinfo = pathinfo($filename);

                // Find all files ending with .cfcache and destroy them if expired!
                if ($pathinfo['extension'] == $this->fileExtension)
                    $data = $this->readFile($filename);
                    if ($this->isExpired(null, $data))
                        unlink($filename);
            }
        } catch (Exception $e) {
            $this->Logger->error("flushExpired error: " . $e->getMessage());
            return false;
        }

        return true;
    }

}

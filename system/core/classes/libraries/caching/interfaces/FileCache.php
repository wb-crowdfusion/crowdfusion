<?php
/**
 * File Cache: Stores files to cache.
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
 * @version     $Id: FileCache.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * File Cache: Stores files to cache.
 *
 * @package     CrowdFusion
 */
class FileCache extends AbstractCache implements FileCacheInterface
{

    /**
     * Constructs the FileCache object
     *
     * @param array  $cacheStores        An array of CacheStoreInterface objects that are used to store our data
     * @param string $fileCacheKeyPrefix The key-prefix used when storing the files. Default: 'fc'
     */
    public function __construct(CacheStoreInterface $PrimaryCacheStore, $fileCacheKeyPrefix = 'fc')
    {
        parent::__construct($PrimaryCacheStore, $fileCacheKeyPrefix);
    }

    /**
     * get cache using key, but check associated timestamps on cached file array
     *
     * @param string $key The key of the cache item to retrieve
     *
     * @return mixed the value of the stored cache item
     */
    public function getUsingFileTimestamps($key)
    {
        $value = $this->get($this->cacheKey($key));
        $timestamps = $this->get($this->cachetskey($key));

        if ($value !== false && $timestamps !== false) {

            foreach ($timestamps as $file => $timestamp) {

                if (filemtime($file) !== $timestamp)
                    return false;
            }

            return $value;
        }

        return false;
    }

    /**
     * put cache value into cache store using timestamps of supplied files as a
     * tie on the validity of the cache
     *
     * @param string $key       The key for the cache
     * @param string $value     The Value to store
     * @param array  $filenames An array of filenames to update
     * @param string $duration  The time-to-live for the cache data
     *
     * @return boolean TRUE if successful
     * @throws CacheException on error
     */
    public function putUsingFileTimestamps($key, $value, array $filenames, $duration = null)
    {
        if (empty($duration))
            $duration = 0;

        $files = array();
        foreach ($filenames as $file) {
            $files[$file] = filemtime($file);
        }

        $timestamps = md5($files);

        // Populate all caches.
        $this->put($this->cacheKey($key), array('v' => $value, 't' => $timestamps), $duration);
//        $this->put($this->cachetskey($key), $timestamps, $duration);

        return true;
    }

    /**
     * return the file contents, but cache the results indefinitely, break cache
     * if timestamp changes
     *
     * @param string $filename The filename to load
     *
     * @return string the contents of the file specified.
     */
    public function getFileContents($filename)
    {

        $cachecontents = $this->get($this->cachefilekey($filename));
//        $timestamp = $this->get($this->cachefiletskey($filename));

        if ($cachecontents !== false && is_array($cachecontents)) {

            $contents = array_key_exists('v', $cachecontents)?$cachecontents['v']:false;
            $timestamp = array_key_exists('t', $cachecontents)?$cachecontents['t']:false;

            if (filemtime($filename) === (int)$timestamp)
                return $contents;
        }

        $contents = file_get_contents($filename);
        $ts = filemtime($filename);

        // Populate all caches.
        $this->put($this->cachefilekey($filename), array('v' => $contents, 't'=> $ts), 0);
//        $this->put($this->cachefiletskey($filename), "{$filename};{$ts}", 0);

        return $contents;
    }

    /**
     * Used internally in this class to construct the timestamp cachekey that we'll use
     *
     * @param string $key The key to generate the cachekey from
     *
     * @return string
     */
    protected function cachetskey($key)
    {
        return 'ts-' . $key;
    }

    /**
     * Used internally in this class to construct the file cachekey that we'll use
     *
     * @param string $key The key to generate the cachekey from
     *
     * @return string
     */
    protected function cachefilekey($key)
    {
        return 'file-' . md5($key);
    }

    /**
     * Used internally in this class to construct the file timestamp cachekey that we'll use
     *
     * @param string $key The key to generate the cachekey from
     *
     * @return string
     */
    protected function cachefiletskey($key)
    {
        return 'filets-' . md5($key);
    }
}

<?php
/**
 * FileCacheInterface
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
 * @version     $Id: FileCacheInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * FileCacheInterface
 *
 * @package     CrowdFusion
 */
interface FileCacheInterface
{

    /**
     * get cache using key, but check associated timestamps on cached file array
     *
     * @param string $key The key of the cache item to retrieve
     *
     * @return mixed the value of the stored cache item
     */
    public function getUsingFileTimestamps($key);

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
    public function putUsingFileTimestamps($key, $value, array $filenames, $duration = null);

    /**
     * return the file contents, but cache the results indefinitely, break cache
     * if timestamp changes
     *
     * @param string $filename The filename to load
     *
     * @return string the contents of the file specified.
     */
    public function getFileContents($filename);

}
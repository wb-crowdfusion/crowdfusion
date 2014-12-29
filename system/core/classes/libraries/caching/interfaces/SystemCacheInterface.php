<?php
/**
 * SystemCacheInterface
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
 * @version     $Id: SystemCacheInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * System cache interface is used to represent the use case of system-level
 * settings, for example: network connection information, database information,
 * useragent strings, infrequently changed database tables (sites, pagetypes, etc.),
 * site variables, and other configuration data.
 *
 * It's important to note that System caches typically are keyed off a global
 * software version variable.  This variable might be an SVN revision number or
 * just a simple incremented value when any configuration data changes.  This is
 * to ensure that a single change to the system-level data will invalidate all
 * system-level cache.
 *
 * @package    CrowdFusion
 */
interface SystemCacheInterface
{

    /**
     * Retrieves the cache item specified by {@link $key} from our cache stores.
     *
     * If we need to dig-down past the top cache, we'll "bubble up" the value to the empty
     * cache stores for lightning fast access next time.
     *
     * @param string $key The key to fetch
     *
     * @return mixed The value stored in the key
     */
    public function get($key);

    /**
     * Writes the cache value to all of our cache stores
     *
     * @param string $key       The cache key to store
     * @param string $value     The value of the cache item
     * @param int    $duration  Ignored.
     * @param bool   $localOnly Ingnored.
     *
     * @return boolean always true, which is stupid.
     */
    public function put($key, $value, $duration, $localOnly = false);

    /**
     * Deletes the item specified by {@link $key} from all of our cache stores.
     *
     * @param string $key the cache key
     *
     * @return boolean true. Always, true.
     */
    public function delete($key);

}
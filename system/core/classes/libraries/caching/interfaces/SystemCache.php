<?php
/**
 * SystemCache
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
 * @version     $Id: SystemCache.php 2012 2010-02-17 13:34:44Z ryans $
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
class SystemCache extends AbstractCache implements SystemCacheInterface
{
    protected $cacheExpiration;
    protected $VersionService;

    /**
     * Creates a SystemCache instance
     *
     * @param CacheStoreInterface $PrimaryCacheStore    Primary cache store implementation
     * @param string         $systemCacheKeyPrefix  A prefix to use for the cache keys. This can be any string. Default: 'sc'
     * @param int            $systemCacheExpiration The duration for cache to live, in seconds. Default: 300
     */
    public function __construct(CacheStoreInterface $PrimaryCacheStore,
                                $systemCacheKeyPrefix = 'sc',
                                $systemCacheExpiration = 300)
    {
        parent::__construct($PrimaryCacheStore, $systemCacheKeyPrefix);

        $this->cacheExpiration = $systemCacheExpiration;
    }

    /**
     * Injects the VersionService
     *
     * @param VersionService $VersionService The VersionService to inject
     *
     * @return void
     */
    public function setVersionService($VersionService)
    {
        $this->VersionService = $VersionService;
    }

    /**
     * Used internally in this class to construct the full cachekey that we'll use
     *
     * @param string $key The key to generate the cachekey from
     *
     * @return string
     */
    protected function cacheKey($key)
    {
        return $this->keyPrefix. '-' . $this->VersionService->getSystemVersion() . '_' . $key;
    }

    /**
     * Writes the cache value to all of our cache stores
     *
     * @param string $key   The cache key to store
     * @param string $value The value of the cache item
     *
     * @return boolean True
     */
    public function put($key, $value, $duration, $localOnly = false)
    {
        parent::put($key, $value, $this->cacheExpiration);

        return true;
    }

}

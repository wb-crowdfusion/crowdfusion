<?php
/**
 * Service for System Versions
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
 * @version     $Id: VersionService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Service for System Versions
 *
 * @package     CrowdFusion
 */
class VersionService
{
    protected $versionDao      = null;
    protected $cache           = null;
    protected $version         = false;
    protected $cfVersion         = false;
    protected $incrementedOnce = false;
    protected $Events;

    protected $ApplicationContext;

    protected $internal = 1;

    /**
     * [IoC] Creates the VersionService
     *
     * @param DAOInterface        $VersionDAO
     * @param CacheStoreInterface $PrimaryCacheStore
     * @param Events              $Events
     */
    public function __construct(DAOInterface $VersionDAO, CacheStoreInterface $PrimaryCacheStore, Events $Events, ApplicationContext $ApplicationContext)
    {
        $this->versionDao = $VersionDAO;
        $this->cache = $PrimaryCacheStore;
        $this->Events = $Events;
        $this->ApplicationContext = $ApplicationContext;
    }

    /**
     * Returns the current system version
     *
     * @return integer
     */
    public function getDeploymentRevision()
    {
        if ($this->version === false) {

            $version = $this->cache->get('DeploymentRevision');

            if ($version === false) {
                $version = $this->versionDao->getSystemVersion();

                if ($version === false)
                    $version = $this->incrementDeploymentRevision('Initial system version');

                $this->cache->put('DeploymentRevision', $version, 0);
            }

            $this->version = $version;
        }

        return $this->version;
    }

    public function getSystemVersion()
    {
        return $this->ApplicationContext->getSystemVersionTimestamp();
    }

    public function getCrowdFusionVersion()
    {
        if ($this->cfVersion === false) {

            $version = $this->cache->get('CFVersion');

            if ($version === false) {
                $version = $this->versionDao->getCrowdFusionVersion();

                $this->cache->put('CFVersion', $version, 0);
            }

            $this->cfVersion = $version;
        }

        return $this->cfVersion;
    }

    /**
     * Increases the current SystemVersion by one
     *
     * should invalidate all cached object which use this version number in their key
     * details are stored in log; details describe circumstance of version update
     *
     * @param string $details A string explaining the reason for the new version
     *
     * @return integer The new version
     */
    public function incrementDeploymentRevision($details = null)
    {
        if (!$this->incrementedOnce) {

            $backtrace = null;//serialize(debug_backtrace());

            $version = $this->versionDao->insertSystemVersion($details, $backtrace);

            $this->Events->trigger('SystemVersion.increment', $version);

            $this->version = $version;
            $this->incrementedOnce = true;
        } else {
            $this->version = $this->version.'.'.$this->internal++;
        }
        $this->cache->delete('DeploymentRevision');
        $this->cache->delete('CFVersion');

        return $this->version;

    }
}

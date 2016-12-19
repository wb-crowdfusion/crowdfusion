<?php
/**
 * AssetService
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
 * @version     $Id: AssetService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AssetService
 *
 * @package     CrowdFusion
 */
class AssetService extends AbstractFileDeploymentService
{
    protected $subject = 'assets';

    protected $storageFacility;
    protected $storageFacilityParams;

    protected $StorageFacilityFactory;

    public function setStorageFacilityFactory(StorageFacilityFactory $StorageFacilityFactory)
    {
        $this->StorageFacilityFactory = $StorageFacilityFactory;
    }

    protected function getStorageFacility()
    {
        if(is_null($this->storageFacility))
        {
            $sfInfo = $this->RequestContext->getStorageFacilityInfo($this->subject);
            if(empty($sfInfo))
                return;

            $this->storageFacility = $this->StorageFacilityFactory->build($sfInfo);
            $this->storageFacilityParams = $this->StorageFacilityFactory->resolveParams($sfInfo);
        }

        return $this->storageFacility;
    }

    protected function getStorageFacilityParams()
    {
        if(is_null($this->storageFacilityParams))
            $this->getStorageFacility();

        return $this->storageFacilityParams;
    }

    /**
     * @param string $name The name of the file to retrieve
     *
     * @return string the full filename (with path) from the specified {@link $name}
     */
    public function resolveFile($name)
    {
        if($this->getStorageFacility() == null)
            return null;

        return $this->getStorageFacility()->resolveFile($this->getStorageFacilityParams(), $name);
    }

    public function fileExists($relpath)
    {
        if($this->getStorageFacility() == null)
            return false;

        return $this->getStorageFacility()->fileExists($this->getStorageFacilityParams(), $relpath);
    }

    /**
     * Moves the file from the themes to the public app directory
     *
     * NOTE: the following code will overwrite files;
     *       unused files are not deleted in the destination directory
     *
     * @param string $relpath  The relative path where the file lives
     * @param string $filename The file to move
     *
     * @return string the full path and filename of the moved file
     */
    public function putFile($relpath, $filename, $ts)
    {
        if($this->getStorageFacility() == null)
            return false;

        $timestamp = null;

        try {
            $headFile = $this->getStorageFacility()->headFile($this->getStorageFacilityParams(), $relpath);
            $timestamp = $headFile->getTimestamp();
        }catch(StorageFacilityException $sfe) {}

        if ($timestamp != $ts) {
            $file = new StorageFacilityFile($relpath, $filename, null, null, $ts);

            return $this->getStorageFacility()->putFile($this->getStorageFacilityParams(), $file);
        }

        return $this->getStorageFacility()->resolveFile($this->getStorageFacilityParams(), $relpath);
    }

    public function deleteFile($relpath)
    {
        if($this->getStorageFacility() == null)
            return;

        $this->getStorageFacility()->deleteFile($this->getStorageFacilityParams(), $relpath);
    }

}

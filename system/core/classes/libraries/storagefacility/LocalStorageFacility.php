<?php
/**
 * LocalStorageFacility
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
 * @version     $Id: LocalStorageFacility.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * This class provides facilities to store and manage files on the local filesystem.  The primary use of this storage
 * facility is for single web tier configuration where the media files are stored on the same server as the main
 * application.
 *
 * @package     CrowdFusion
 */
class LocalStorageFacility extends AbstractStorageFacility
{

    public function setRequest(Request $Request)
    {
        $this->Request = $Request;
    }

    /**
     * This function copies the file from the local path (specified in the {@link $file}) to the generated storage
     * path; creating any directories as needed.  The public URL will be set on the {@link $file} upon successful
     * persistence to the filesystem.
     *
     * @param Site                $site  Site used to determine storage location
     * @param StorageFacilityFile &$file File to store
     *
     * @return StorageFacilityFile Fully-resolved and stored file, has URL and id
     *                              set appropriately to reference the stored file later
     * @throws StorageFacilityException upon failure
     */
    public function putFile(StorageFacilityParams $params, StorageFacilityFile &$file)
    {
        $storagePath = $this->generateStoragePath($params, $file->getId());

//        $this->createDirectory($storagePath);

        if (is_file($file->getLocalPath())) {
            try {
                FileSystemUtils::safeCopy($file->getLocalPath(), $storagePath);
            } catch(Exception $e) {
                throw new StorageFacilityException($e->getMessage());
            }
        } else
            throw new StorageFacilityException("Local file not found '".$file->getLocalPath()."'");

        $file->setLocalPath($storagePath);
        $file->setUrl($this->generateUrl($params, $file->getId()));

        return $file;
    }

    /**
     * Returns a StorageFacilityFile object based on the id of the file. The
     * {@link $fileid} must be unique within the storage facility.
     *
     * @param Site    $site    Site used to determine storage location
     * @param string  $fileid  File identifier of stored file, ex. /path/to/file.ext
     * @param boolean $getData If true, fetches the contents of the file into the StorageFacilityFile
     *
     * @return StorageFacilityFile
     * @throws StorageFacilityException upon failure or missing file
     */
    public function getFile(StorageFacilityParams $params, $fileid, $getData = false)
    {
        //verify file exists
        $this->fileExists($params, $fileid);

        $storagePath = $this->generateStoragePath($params, $fileid);

        if (!is_file($storagePath)) {
            throw new StorageFacilityException("File not found '".$storagePath."'");
        }

        $f = new StorageFacilityFile($fileid, $storagePath, $this->generateUrl($params, $fileid), null, filemtime($storagePath), filesize($storagePath));

        if($getData === true)
            $f->setContents(file_get_contents($storagePath));

        return $f;
    }

    /**
     * Returns file timestamp, filesize, status, local path (if exists), etc
     * without retrieving the contents of the object. The {@link $fileid} must
     * be unique within the storage facility.
     *
     * @param Site   $site   Site used to determine storage location
     * @param string $fileid File identifier of stored file, ex. /path/to/file.ext
     *
     * @return StorageFacilityFile
     * @throws StorageFacilityException upon failure or missing file
     */
    public function headFile(StorageFacilityParams $params, $fileid)
    {
        return $this->getFile($params, $fileid, false);
    }

    /**
     * Returns true if the file exists in the storage facility or
     * false if it doesn't exist. Throws an exception is the operation failed.
     *
     * @param Site   $site   Site used to determine storage location
     * @param string $fileid File identifier of stored file, ex. /path/to/file.jpg
     *
     * @return boolean
     * @throws StorageFacilityException if operation fails
     */
    public function fileExists(StorageFacilityParams $params, $fileid)
    {
        $this->validateFileID($fileid);
        return file_exists($this->generateStoragePath($params, $fileid));
    }

    /**
     * Deletes a file in the storage facility.
     *
     * @param Site   $site   Site used to determine storage location
     * @param string $fileid File identifier of stored file, ex. /path/to/file.jpg
     *
     * @return boolean True upon success
     * @throws StorageFacilityException if operation fails
     */
    public function deleteFile(StorageFacilityParams $params, $fileid)
    {
        $this->validateFileID($fileid);

        $storagePath = $this->generateStoragePath($params, $fileid);

        if (is_file($storagePath)) {
            if (!@unlink($storagePath)) {
                throw new StorageFacilityException("Cannot delete file '".$storagePath."'");
            } else {
                return true;
            }
        }
    }

    /**
     * Renames a StorageFacilityFile in the storage facility.
     *
     * @param Site   $site      Site used to determine storage location
     * @param string &$file     File identifier of stored file, ex. /path/to/file.ext
     * @param string $newfileid File identifier to rename to
     *
     * @return StorageFacilityFile Fully-resolved and stored file, has URL and id
     *                              set appropriately to reference the stored file later
     * @throws StorageFacilityException if operation fails
     */
    public function renameFile(StorageFacilityParams $params, StorageFacilityFile &$file, $newfileid)
    {
        $this->validateFileID($newfileid);

        if ($file->getId() == null)
            throw new StorageFacilityException("Invalid fileid '".$file->getId()."'");

        if ($this->fileExists($params, $newfileid))
            throw new StorageFacilityException("New file already exists '".$newfileid."'");

        if (!$this->fileExists($params, $file->getId()))
            throw new StorageFacilityException("File does not exist '".$file->getId()."'");

        if (!@rename($this->generateStoragePath($params, $file->getId()),
                $this->generateStoragePath($params, $newfileid)))
            throw new StorageFacilityException("Could not rename file '".$file->getId()."'");

        $file->setId($newfileid);
        $file->setUrl($this->generateUrl($params, $newfileid));
    }

    /**
     * Creates the directory
     *
     * @param string $fullPath The directory to create
     *
     * @return void
     * @throws StorageFacilityException if directory cannot be created
     */
//    protected function createDirectory($fullPath)
//    {
//        $path_parts = pathinfo($fullPath);
//
//        if (!is_dir(dirname($fullPath))) {
//            @FileSystemUtils::recursiveMkdir($path_parts['dirname'], 0777);
//        }
//    }
}

?>

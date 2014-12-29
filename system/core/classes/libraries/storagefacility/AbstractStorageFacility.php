<?php
/**
 * AbstractStorageFacility
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
 * @version     $Id: AbstractStorageFacility.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Provides a minimal implementation for Storage Facilities. Please see the StorageFacilityInterface for more details.
 *
 * @package     CrowdFusion
 */
abstract class AbstractStorageFacility implements StorageFacilityInterface
{

    /**
     * Puts the StorageFacilityFile in the storage facility. This may involve
     * network transmission and will throw an exception if not successful. All
     * storage details are contained in the StorageFacilityFile object and the
     * facility configuration data.
     *
     * @param Site                $site  Site used to determine storage location
     * @param StorageFacilityFile &$file File to store
     *
     * @return StorageFacilityFile Fully-resolved and stored file, has URL and id
     *  set appropriately to reference the stored file later
     * @throws StorageFacilityException upon failure
     */
    public function putFile(StorageFacilityParams $params, StorageFacilityFile &$file)
    {
        throw new StorageFacilityException("putFile() not implemented");
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
        throw new StorageFacilityException("getFile() not implemented");
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
        throw new StorageFacilityException("headFile() not implemented");
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
        throw new StorageFacilityException("fileExists() not implemented");
    }

    /**
     * Returns an array of StorageFacilityFile objects which represent the
     * found files in the storage facility.
     *
     * @param Site   $site       Site used to determine storage location
     * @param string $startsWith Used as a file id search filter. Files are
     *                              returned that have an id that starts with the parameter. This effectively
     *                              lists files in a particular directory, since file id's contain the full path.
     *
     * @return void
     * @throws StorageFacilityException if operation fails
     */
    public function listFiles(StorageFacilityParams $params, $startsWith='')
    {
        throw new StorageFacilityException("listFiles() not implemented");
    }

    /**
     * Deletes a file in the storage facility.
     *
     * @param Site   $site   Site used to determine storage location
     * @param string $fileid File identifier of stored file, ex. /path/to/file.jpg
     *
     * @return void
     * @throws StorageFacilityException if operation fails
     */
    public function deleteFile(StorageFacilityParams $params, $fileid)
    {
        throw new StorageFacilityException("deleteFile() not implemented");
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
        throw new StorageFacilityException("renameFile() not implemented");
    }

    /**
     * Returns a unique file id, appending an integer to the
     * filename if a StorageFacilityFile exists in the storage
     * facility with the passed {@link $id}.  This will continue to append
     * an integer until a unique id is found, ex. /path/to/file-2.ext,
     * /path/to/file-3.ext
     *
     * @param Site   $site   Site used to determine storage location
     * @param string $fileid File identifier of stored file, ex. /path/to/file.ext
     *
     * @return string Unique file id
     * @throws StorageFacilityException if operation fails
     */
    public function findUniqueFileID(StorageFacilityParams $params, $fileid)
    {
        while ($this->fileExists($params, $fileid)) {
            $path = pathinfo($fileid);

            $filename = $path['filename'];

            //three scenarios: word, word-, word-another

            if (stripos($filename, '-') !== false) {

                $filetokens = explode("-", $filename);

                if (count($filetokens) > 0 && strlen($filetokens[count($filetokens)-1]) > 0) {
                    $lasttoken = $filetokens[count($filetokens)-1];

                    if (is_numeric($lasttoken))
                        $filetokens[count($filetokens)-1] = $lasttoken + 1;
                    else
                        $filetokens[] = '1';
                } elseif (count($filetokens) > 0 && strlen($filetokens[count($filetokens)-1]) === 0)
                    $filetokens[count($filetokens)-1] = '1';

                $filename = implode('-', $filetokens);
            } else {
                $filename .= '-1';
            }

            $extension = '';
            if(array_key_exists('extension', $path))
                $extension = '.'.$path['extension'];

            $fileid = ($path['dirname'] === '.' ?
                            ''
                       :    $path['dirname'] . '/') .
                      "{$filename}{$extension}";
        }

        return $fileid;
    }

    /**
     * Creates and returns an absolute filesystem path for storing a file. This uses the basepath config parameter and
     * appends the original site domain, and fileid to create an absolute file path.
     *
     * Ex: [basepath]/[site media domain]/[fileid]
     *
     * @param Site   $site   The OriginalDomain attribute of this object is used to build the storage path
     * @param string $fileid The unique fileid; used to build the storage path; must begin with /
     *
     * @return string the storage path for the specified file
     */
    protected function generateStoragePath(StorageFacilityParams $params, $fileid)
    {
        return preg_replace("/\/[\/]+/", "/", $params->BaseStoragePath.$fileid);
    }

    /**
     * This function generates a full URL for a particular site and file.  The URL returned should be fully-qualified
     * and publically-accessible URI pointing to the file.  This function is used interally by the Storage Facility
     * implementation to populate the URL field on the {@link StorageFacilityFile} object.
     *
     * @param Site   $site   The site
     * @param string $fileid The file name
     *
     * @return string the fully qualified URI
     */
    protected function generateUrl(StorageFacilityParams $params, $fileid)
    {
        return "http".($params->isSSL()?"s":"")."://{$params->Domain}".preg_replace("/\/[\/]+/", "/", "{$params->BaseURI}{$fileid}");
    }

    /**
     * Using storage facility params, generate a StorageFacilityFile object without querying storage
     *
     * @param StorageFacilityParams $params Parameters used to determine storage location and URL
     * @param string                $fileid File identifier of stored file, ex. /path/to/file.jpg
     * @return StorageFacilityFile
     */
    public function resolveFile(StorageFacilityParams $params, $fileid)
    {
        return new StorageFacilityFile($fileid, $this->generateStoragePath($params, $fileid), $this->generateUrl($params, $fileid));
    }

    /**
     * Does nothing if {@link $fileid} is valid. Otherwise, throws StorageFacilityException
     *
     * @param string $fileid the fileid to validate
     *
     * @return void
     * @throws StorageFacilityException if fileid is invalid
     */
    protected function validateFileID($id)
    {
        if($id == null || substr($id,0,1) !== '/')
            throw new StorageFacilityException('Invalid file id: '.$id);
    }

}

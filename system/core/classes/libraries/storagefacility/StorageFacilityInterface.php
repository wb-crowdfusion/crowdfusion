<?php
/**
 * StorageFacilityInterface
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
 * @version     $Id: StorageFacilityInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * This interface is used to implement an Adapter Pattern (http://en.wikipedia.org/wiki/Wrapper_pattern)
 * for media storage facilities.  The implementations of this interface wrap the specific API for each
 * storage facility. Storage facilites are used by services and controllers to read and write media StorageFacilityFiles
 * throughout the various use cases of the system.  In addition, the AssetAggregator uses a StorageFacility to read
 * the original asset files from wherever they are hosted and another StorageFacility to write the combined and compressed
 * new asset files to wherever they will be hosted on the web.  The ability of a StorageFacility to manage the physical
 * storage of a file and also manage the public URL of where that file is accessed on the web is crucial to powering
 * the asset aggregation scenario.
 *
 * @package     CrowdFusion
 */
interface StorageFacilityInterface
{

    /**
     * Puts the StorageFacilityFile in the storage facility. This may involve
     * network transmission and will throw an exception if not successful. All
     * storage details are contained in the StorageFacilityFile object and the
     * facility configuration data.
     *
     * @param StorageFacilityParams $params Parameters used to determine storage location and URL
     * @param StorageFacilityFile   &$file  File to store
     *
     * @return StorageFacilityFile Fully-resolved and stored file, has URL and id
     *  set appropriately to reference the stored file later
     * @throws StorageFacilityException upon failure
     */
    public function putFile(StorageFacilityParams $params, StorageFacilityFile &$file);

    /**
     * Downloads a StorageFacilityFile object based on the id of the file into
     * a local file. The {@link $fileid} must be unique within the storage facility.
     * If {@link $getData} is true, the contents of the file will be read into
     * the StorageFacilityFile for retrieval using
     * {@link StorageFacilityFile.getContents()}.
     *
     * @param StorageFacilityParams $params  Parameters used to determine storage location and URL
     * @param string                $fileid  File identifier of stored file, ex. /path/to/file.ext
     * @param boolean               $getData If true, fetches the contents of the file into the StorageFacilityFile
     *
     * @return StorageFacilityFile
     * @throws StorageFacilityException upon failure or missing file
     */
    public function getFile(StorageFacilityParams $params, $fileid, $getData = false);

    /**
     * Returns file timestamp, filesize, status, local path (if exists), etc
     * without retrieving the contents of the object. The {@link $fileid} must
     * be unique within the storage facility.
     *
     * @param StorageFacilityParams $params Parameters used to determine storage location and URL
     * @param string                $fileid File identifier of stored file, ex. /path/to/file.ext
     *
     * @return StorageFacilityFile
     * @throws StorageFacilityException upon failure or missing file
     */
    public function headFile(StorageFacilityParams $params, $fileid);

    /**
     * Returns true if the file exists in the storage facility or
     * false if it doesn't exist. Throws an exception is the operation failed.
     *
     * @param StorageFacilityParams $params Parameters used to determine storage location and URL
     * @param string                $fileid File identifier of stored file, ex. /path/to/file.jpg
     *
     * @return boolean
     * @throws StorageFacilityException if operation fails
     */
    public function fileExists(StorageFacilityParams $params, $fileid);

    /**
     * Returns a unique file id, appending an integer to the
     * filename if a StorageFacilityFile exists in the storage
     * facility with the passed {@link $id}.  This will continue to append
     * an integer until a unique id is found, ex. /path/to/file-2.ext,
     * /path/to/file-3.ext
     *
     * @param StorageFacilityParams $params Parameters used to determine storage location and URL
     * @param string                $id     File identifier of stored file, ex. /path/to/file.ext
     *
     * @return string Unique file id
     * @throws StorageFacilityException if operation fails
     */
    public function findUniqueFileID(StorageFacilityParams $params, $id);

    /**
     * Returns an array of StorageFacilityFile objects which represent the
     * found files in the storage facility.
     *
     * @param StorageFacilityParams $params Parameters used to determine storage location and URL
     * @param string                $startsWith Used as a file id search filter. Files are
     *                                          returned that have an id that starts with the parameter. This effectively
     *                                          lists files in a particular directory, since file id's contain the full path.
     *
     * @return StorageFacilityFile[]
     * @throws StorageFacilityException if operation fails
     */
    public function listFiles(StorageFacilityParams $params, $startsWith='');

    /**
     * Deletes a file in the storage facility.
     *
     * @param StorageFacilityParams $params Parameters used to determine storage location and URL
     * @param string $fileid File identifier of stored file, ex. /path/to/file.jpg
     *
     * @return boolean True upon success
     * @throws StorageFacilityException if operation fails
     */
    public function deleteFile(StorageFacilityParams $params, $fileid);

    /**
     * Renames a StorageFacilityFile in the storage facility.
     *
     * @param StorageFacilityParams $params    Parameters used to determine storage location and URL
     * @param string                &$file     File identifier of stored file, ex. /path/to/file.ext
     * @param string                $newfileid File identifier to rename to
     *
     * @return StorageFacilityFile Fully-resolved and stored file, has URL and id
     *  set appropriately to reference the stored file later
     * @throws StorageFacilityException if operation fails
     */
    public function renameFile(StorageFacilityParams $params, StorageFacilityFile &$file, $newfileid);

    /**
     * Using storage facility params, generate a StorageFacilityFile object without querying storage
     *
     * @param StorageFacilityParams $params Parameters used to determine storage location and URL
     * @param string                $fileid File identifier of stored file, ex. /path/to/file.jpg
     * @return StorageFacilityFile
     */
    public function resolveFile(StorageFacilityParams $params, $fileid);
}
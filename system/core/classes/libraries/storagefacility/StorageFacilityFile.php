<?php
/**
 * StorageFacilityFile
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
 * @version     $Id: StorageFacilityFile.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Represents a Storage Facility file: contains id (filename), the local path
 * of the local representation of the externally stored file, and the absolute
 * URL of the file as served by the storage facility
 *
 * @package     CrowdFusion
 */
class StorageFacilityFile
{

    /**
     * The id of a File contains the full path and filename as it exists
     * within a storage facility.  The storage facility may also have a base path
     * where the files are stored; this should not be included in the file id.
     * Each File within a storage facility must have a unique id.  Directories
     * can be simulated by using a path convention in the file id. For example:
     *      id = '/thumbnails/mobile-phone.jpg'
     *      id = '/computer.jpg'
     *      id = '/thumbnails/x-small/phone.gif'
     *
     * @var string
     */
    protected $id;

    /**
     * The localpath of a File contains the full path to a file on the local
     * system.  This represents the local counter-part to the file stored in
     * the storage facility.
     *
     * @var string
     */
    protected $localPath;

    /**
     * The url of a File represents the publicly accessible URL for the file.
     * If the storage facility supports public serving of the file, this attribute
     * will be set.
     *
     * @var string
     */
    protected $url;


    protected $timestamp;
    protected $fileSize;
    protected $contents;
    protected $status;
    protected $headers = array();

    /**
     * Creates the file object
     *
     * For more details on params, see the variable defs inside this function.
     *
     * @param string  $id        The file id
     * @param string  $localPath The local path
     * @param string  $url       The URL
     * @param string  $contents  The file contents
     * @param string  $timestamp The timestamp of the file (modified time)
     * @param integer $fileSize  The size of the file in bytes
     * @param string  $status    The status for the file
     */
    public function __construct($id = null, $localPath = null, $url = null, $contents = null, $timestamp = null, $fileSize = null, $status = null)
    {
        $this->setId($id);
        $this->setLocalPath($localPath);
        $this->setUrl($url);
        $this->setContents($contents);
        $this->setTimestamp($timestamp);
        $this->setFileSize($fileSize);
        $this->setStatus($status);
    }

    /**
     * Gets the file extension for this file
     *
     * @return string
     */
    public function getExtension()
    {
        $path = pathinfo($this->id);
        if(array_key_exists('extension', $path))
            return strtolower($path['extension']);
        return '';
    }

    /**
     * Returns the basename for this file. See php's basename() function docs for details.
     *
     * @return string
     */
    public function getBasename()
    {
        return basename($this->id, '.'.$this->getExtension());
    }

    /**
     * Set the ID for this file
     *
     * @param string $id The id to set
     *
     * @return void
     */
    public function setID($id)
    {
        if($id !== null && substr($id, 0, 1) !== '/')
            throw new Exception("invalid file id, must start with a /");
        $this->id = $id;
    }

    /**
     * Returns the id for the file
     *
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Returns the local path for the file
     *
     * @return string
     */
    public function getLocalPath()
    {
        return $this->localPath;
    }

    /**
     * Sets the local path
     *
     * @param string $localPath The new local path to use
     *
     * @return void
     */
    public function setLocalPath($localPath)
    {
        $this->localPath = $localPath;
    }

    /**
     * Sets the URL
     *
     * @param string $url The new url
     *
     * @return void
     */
    public function setURL($url)
    {
        $this->url = $url;
    }

    /**
     * Gets the URL
     *
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Determines if this is a valid file object or not.
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->getID() != null && $this->getURL() != null && $this->getLocalPath() != null;
    }

    /**
     * Returns the file's timestamp
     *
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Sets the file's timestamp
     *
     * @param string $timestamp The timestamp
     *
     * @return void
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Returns the file's contents
     *
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Sets the file contents
     *
     * @param string $contents The contents of the file
     *
     * @return void
     */
    public function setContents($contents)
    {
        $this->contents = $contents;
    }

    /**
     * Returns the filesize for the file
     *
     * @return integer
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * Sets the filesize
     *
     * @param integer $fileSize The filesize
     *
     * @return void
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
    }

    /**
     * Returns the status for the file
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the file status
     *
     * @param string $status The status
     *
     * @return void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }


    /**
     * Sets a header to use for storage (if the StorageFacility supports headers)
     *
     * @param  $name
     * @param  $value
     * @return void
     */
    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
    }

    /**
     * Returns true if the header is set (if the StorageFacility supports headers)
     *
     * @param  $name
     * @return bool
     */
    public function containsHeader($name) {
        return isset($this->headers[$name]);
    }

    /**
     * Removes the header by name (if the StorageFacility supports headers)
     *
     * @param  $name
     * @return void
     */
    public function removeHeader($name) {
        if($this->containsHeader($name))
            unset($this->headers[$name]);
    }

    /**
     * Returns the value for a header by name (if the StorageFacility supports headers)
     *
     * @param  $name
     * @return string
     */
    public function getHeader($name) {
        if($this->containsHeader($name))
            return $this->headers[$name];
    }

    /**
     * Returns the array of headers (if the StorageFacility supports headers)
     *
     * The format is:
     *
     *   array( 'HeaderName' => 'Value1', 'Header2' => ... )
     *
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }

}
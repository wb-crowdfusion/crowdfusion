<?php
/**
 * UploadedFile
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
 * @version     $Id: UploadedFile.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * UploadedFile
 *
 * @package     CrowdFusion
 */
class UploadedFile {

    private $name;
    private $size;
    private $tempName;
    private $mimetype;
    private $error;

    public function __construct($name, $tempName, $size, $mimetype, $error) {

        $this->name = FileSystemUtils::sanitizeFilename($name);
        $this->tempName = $tempName;
        $this->size = $size;
        $this->mimetype = $mimetype;
        $this->error = $error;

    }

    public function getName() {
        return $this->name;
    }

    public function getTemporaryName() {
        return $this->tempName;
    }

    public function getSize() {
        return $this->size;
    }

    public function getMimetype() {
        return $this->mimetype;
    }

    public function getError() {
        return $this->error;
    }

    public function getHumanError()
    {
        return $this->decodeUploadError($this->error);
    }

    public function isEmpty() {
        return !($this->error === UPLOAD_ERR_OK && $this->size > 0);
    }

    public function transferTo($filename) {
        if( !$this->isEmpty() && is_uploaded_file($this->tempName)) {
            if ( @FileSystemUtils::safeCopy($this->tempName, $filename)) return true;
            if ( @move_uploaded_file($this->tempName, $filename)) return true;
        }
        return FALSE;
    }

    /**
     * @param errorCode - http://us.php.net/manual/en/features.file-upload.errors.php
     *
     * @return string
     */
    protected static function decodeUploadError($errorCode) {
        switch($errorCode) {
            case 1: return 'File exceeds maximum size for PHP';
            case 2: return 'File exceeds maximum size for HTML form';
            case 3: return 'File partially uploaded';
            case 4: return 'No file was uploaded';
            case 6: return 'Missing temporary folder';
            case 7: return 'Failed to write file to disk';
            case 8: return 'File upload stopped by extension';
            default: return 'Unknown upload error code';
        }
    }
}
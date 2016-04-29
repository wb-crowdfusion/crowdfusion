<?php
/**
 * ContextUtils
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
 * @version     $Id: ContextUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ContextUtils
 *
 * @package     CrowdFusion
 */
class ContextUtils
{

    /**
     * Formats XML errors for exceptions
     *
     * @param $error libxml error objects
     * @param $xml array of xml lines
     * @return string a formatted error message
     */
    protected static function displayXmlError($error, $xml)
    {

        $return  = $xml[$error->line - 1] . "\n";
        $return .= str_repeat('-', $error->column) . "^\n";

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
             case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }

        $return .= trim($error->message) .
                   "\n  Line: $error->line" .
                   "\n  Column: $error->column";

        if ($error->file) {
            $return .= "\n  File: $error->file";
        }

        return "$return\n\n--------------------------------------------\n\n";
    }

    /**
     * Converts an XML file into a SimpleXMLElement object
     *
     * @param string $xmlFilename    File containing well-formed XML
     * @param string $simpleXMLClass Optional parameter to specify classname of
     *  the return object. That class should extend the SimpleXMLElement class.
     *
     * @return SimpleXMLElement Object of specified class
     * @throws SimpleXMLParserException upon missing file, parse error, or class
     *  not found for {@link $simpleXMLClass}
     */
    public static function parseXMLFile($xmlFilename)
    {
        libxml_use_internal_errors(true);

        if(defined('LIBXML_COMPACT'))
            $doc = simplexml_load_file($xmlFilename, 'SimpleXMLIterator', LIBXML_NOBLANKS | LIBXML_COMPACT);
        else
            $doc = simplexml_load_file($xmlFilename, 'SimpleXMLIterator', LIBXML_NOBLANKS);

        if (false === $doc) {
            $xml = explode("\n", file_get_contents($xmlFilename));

            $errors = libxml_get_errors();

            $errorString = '';
            foreach ($errors as $error) {
                $errorString .= self::displayXmlError($error, $xml);
            }

            libxml_clear_errors();

            throw new Exception("Unable to parse xml file [$xmlFilename]:\n\n".$errorString);
        }

        return $doc;
    }

    public static function recursiveMkdir($path, $mode = 0755)
    {
        if (is_dir($path)) {
            self::safeChown($path);
            return @chmod($path, $mode);
        }

        if(!is_dir(dirname($path)))
        {
            if($path == dirname($path))
                throw new Exception('Directory recursion error on [' . $path . ']' . (ini_get('safe_mode') == true?', SAFE MODE restrictions in effect':''));
            if(!self::recursiveMkdir(dirname($path), $mode))
                return false;

        }

        $return_value = @mkdir($path, $mode);
        @chmod($path, $mode);
        self::safeChown($path);
        return $return_value;
    }

    /**
     * Puts contents into the target file safely and chowns to the
     * current user.  If this is overwriting the target then this
     * first writes the contents to a temp file (same file name with
     * ".tmp.[randomChars]" added at the end) and then renames it to
     * the target.  This prevents the concurrency issue where other
     * requests are reading from a file that is currently being
     * written to.
     *
     * @param $filename
     * @param $contents
     * @param int $flags
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function safeFilePutContents($filename, $contents, $flags = LOCK_EX)
    {
        if (!self::recursiveMkdir(dirname($filename))) {
            throw new Exception('Unable to create directory: ' . dirname($filename));
        }

        // if we're appending go ahead and write directly to the target
        if ($flags >= FILE_APPEND) {
            if (!@file_put_contents($filename, $contents, $flags)) {
                throw new Exception('Unable to write file: ' . $filename);
            }
        } else {
            $tmpFilename = tempnam(dirname($filename), basename($filename) . '.tmp.');
            if (!@file_put_contents($tmpFilename, $contents, $flags)) {
                @unlink($tmpFilename);
                throw new Exception('Unable to write file: ' . $tmpFilename);
            }

            if (!@rename($tmpFilename, $filename)) {
                @unlink($tmpFilename);
                throw new Exception('Unable to write file: ' . $filename);
            }
        }

        self::safeChown($filename);
        return true;
    }

    public static function safeCopy($filename, $target)
    {
        if(!self::recursiveMkdir(dirname($target)))
            throw new Exception('Unable to create directory: '.dirname($target));

        if(!@copy($filename, $target))
            throw new Exception('Unable to copy file: '.$filename);

        self::safeChown($target);
        return true;
    }

    public static function safeChown($filename)
    {
        //$buildStat = stat(PATH_BUILD);
        //@chown($filename, intval($buildStat['uid']));
        //@chgrp($filename, intval($buildStat['gid']));
        return true;
    }
}

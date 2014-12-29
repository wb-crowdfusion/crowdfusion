<?php
/**
 * SimpleXMLParser
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
 * @version     $Id: SimpleXMLParser.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Provides a simple adapter for parsing xml files or strings into
 * SimpleXMLElement objects
 *
 * @package     CrowdFusion
 */
class SimpleXMLParser implements SimpleXMLParserInterface
{

    public function __construct()
    {


    }

    /**
     * Converts an XML string into a SimpleXMLElement object
     *
     * @param string $xmlString      String of well-formed XML
     * @param string $simpleXMLClass Optional parameter to specify classname of
     *  the return object. That class should extend the SimpleXMLElement class.
     *
     * @return SimpleXMLElement Object of specified class
     * @throws SimpleXMLParserException upon parse error, or class
     *  not found for {@link $simpleXMLClass}
     */
    public function parseXMLString($xmlString, $simpleXMLClass = 'SimpleXMLExtended')
    {
        libxml_use_internal_errors(true);

        $doc = simplexml_load_string($xmlString, $simpleXMLClass);

        if (!$doc) {
            $xml = explode("\n", $xmlString);

            $errors = libxml_get_errors();

            $errorString = '';
            foreach ($errors as $error) {
                $errorString .= $this->displayXmlError($error, $xml);
            }

            libxml_clear_errors();

            throw new SimpleXMLParserException("Unable to parse xml string:\n\n".$errorString);
        }
        return $doc;
    }

    /**
     * Formats XML errors for exceptions
     *
     * @param $error libxml error objects
     * @param $xml array of xml lines
     * @return string a formatted error message
     */
    protected function displayXmlError($error, $xml)
    {
        $return  = $xml[$error->line - 1 < 0 ? 0 : $error->line - 1] . "\n";
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
    public function parseXMLFile($xmlFilename, $simpleXMLClass = 'SimpleXMLExtended')
    {
        libxml_use_internal_errors(true);

        $doc = simplexml_load_file($xmlFilename, $simpleXMLClass);

        if (!$doc) {
            $xml = explode("\n", file_get_contents($xmlFilename));

            $errors = libxml_get_errors();

            $errorString = '';
            foreach ($errors as $error) {
                $errorString .= $this->displayXmlError($error, $xml);
            }

            libxml_clear_errors();

            throw new SimpleXMLParserException("Unable to parse xml file [$xmlFilename]:\n\n".$errorString);
        }

        return $doc;
    }


    /**
     * Converts an XML file into a SimpleXMLElement object, recursively
     * expanding XML <include/> tags to form one document
     *
     * The format for an include directive is:
     *
     * <code>
     *  <include href="/relative/path/to/file"/>
     * </code>
     *
     * @param string $xmlFilename    File containing well-formed XML
     * @param string $simpleXMLClass Optional parameter to specify classname of
     *  the return object. That class should extend the SimpleXMLElement class.
     *
     * @return SimpleXMLElement Object of specified class
     * @throws SimpleXMLParserException Upon missing file, parse error, or class
     *                                  not found for {@link $simpleXMLClass}
     */
    public function parseXMLFileWithIncludes($xmlFilename, $simpleXMLClass = 'SimpleXMLExtended')
    {
        if (empty($xmlString) && file_exists($xmlFilename))
            $xmlString = file_get_contents($xmlFilename);

        $xmlString = $this->loadIncludes(dirname($xmlFilename), $xmlString);

        return $this->parseXMLString($xmlString, $simpleXMLClass);
    }

    /**
     * Loads all the includes into the xml string.
     *
     * @param string $xmlPath   The absolute path to the directory where the include files are located
     * @param string $xmlString The full XML string (usually file contents from an xml doc)
     *
     * @return string the full xml string with any includes replaced by the included content
     */
    protected function loadIncludes($xmlPath, $xmlString)
    {
        $m = null;
        if (preg_match_all("/\<include\s*href\=\"([^\"]+)\".*?\/\>/si", $xmlString, $m, PREG_SET_ORDER) !== false) {
            foreach ($m as $match) {
                $includeFile = rtrim($xmlPath, '/').'/'.$match[1];
                if (file_exists($includeFile)) {
                    $includeString = file_get_contents($includeFile);
                    $includeString = $this->loadIncludes(dirname($includeFile), $includeString);
                    $xmlString = str_replace($match[0], $includeString, $xmlString);
                } else {
                    throw new Exception("XML include file not found: {$includeFile}");
                }
            }
        }

        return $xmlString;
    }
}

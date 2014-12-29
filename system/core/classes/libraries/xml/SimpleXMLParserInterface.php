<?php
/**
 * SimpleXMLParserInterface
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
 * @version     $Id: SimpleXMLParserInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Provides a simple adapter for parsing xml files or strings into
 * SimpleXMLElement objects
 *
 * @package     CrowdFusion
 */
interface SimpleXMLParserInterface
{

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
    public function parseXMLString($xmlString, $simpleXMLClass = 'SimpleXMLExtended');

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
    public function parseXMLFile($xmlFilename, $simpleXMLClass = 'SimpleXMLExtended');


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
     * @throws SimpleXMLParserException upon missing file, parse error, or class
     *  not found for {@link $simpleXMLClass}
     */
    public function parseXMLFileWithIncludes($xmlFilename, $simpleXMLClass = 'SimpleXMLExtended');
}
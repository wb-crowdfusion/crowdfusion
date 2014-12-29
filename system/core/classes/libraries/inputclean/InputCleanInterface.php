<?php
/**
 * InputCleanInterface
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
 * @version     $Id: InputCleanInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * InputCleanInterface: Provides input cleaning methods for cross-site scripting
 * and other filters
 *
 * @package     CrowdFusion
 */
interface InputCleanInterface
{

    /**
     * HTML encode characters, but don't double-encode if a string is already encoded.
     *
     * @param string $string The text to be encoded
     *
     * @return string The encoded version of {@link $string}
     */
    public function htmlEncode($string);

    /**
     * HTML decode characters
     *
     * @param string $string HTML String to decode
     *
     * @return string The decoded HTML string.
     */
    public function htmlDecode($string);

    /**
     * Cleans cross-site-scripting items from the input string.
     *
     * The term "cross-site scripting" originated from the fact that a
     * malicious web site could load another web site into another frame
     * or window, then use Javascript to read/write data on the other web
     * site. Over time the definition changed to mean the injection of
     * HTML/Javascript into a web page, which may be confusing because the name
     * is no longer an accurate description of the current definition.
     *
     * @param string $string The string to remove XSS data from
     * @param string $allowedTags A comma separated list of tags to allow in the result,
     *  passing null will use the default allowed tags
     *
     * @return string A XSS cleaned string
     */
    public function clean($string, $allowedTags = null);

    /**
     * Marks up a string with paragraphs and automatically links any urls.
     *
     * This function marks up the output with paragraph tags and auto-links any URLs that are found.
     * The resulting output is suitable for display in any web-browser, but must have
     * paragraph and extra html tags removed before it's ready for editing.
     *
     * Content is XSS cleaned and stripped of all but a few tags (specified by implementation.)
     *
     * @param string $string The HTML string to format
     *
     * @return string A nicely-formatted version of the input text, with automatic paragraphs and urls in place
     *
     * @see unAutoParagraph()
     */
    public function autoParagraph($string);

    /**
     * Removes the markup added by autoParagraph.
     *
     * This will restore the string to a version that's safe to display in a simple <textarea> editor.
     * All autolinked urls are unlinked and paragraph tags are removed.
     *
     * @param string $string The string that will be deconverted
     *
     * @return string A version of $string suitable for editing in a <textarea>
     *
     * @see autoParagraph()
     */
    public function unAutoParagraph($string);

}
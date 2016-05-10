<?php
/**
 * JSONUtils
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
 * @version     $Id: JSONUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Lets you encode/decode and format json
 *
 * @package     CrowdFusion
 */
class JSONUtils
{

    /**
     * Validates a JSON string for syntax errors
     *
     * @param string  $string       The json  string being decoded.
     *
     * @return bool
     */
    public static function isValid($string)
    {
        json_decode($string);

        //todo: pass back exact error message

        if (function_exists('json_last_error') ) {
            switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return true;
            default:
                return false;
            }
        }

        //todo: change this to true
        return false;
    }

    /**
     * Performs a JSON decode of the string
     *
     * @param string  $string       The json  string being decoded.
     * @param boolean $returnArrays When TRUE, returned objects will be converted into associative arrays.
     *
     * @throws Exception When a json error occurs
     * @return mixed
     */
    public static function decode($string, $returnArrays = false)
    {
        $result = json_decode($string, $returnArrays);

        if (function_exists('json_last_error') ) {

            switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                throw new JSONException('json_decode - Maximum stack depth exceeded');

            case JSON_ERROR_CTRL_CHAR:
                throw new JSONException('json_decode - Unexpected control character found');

            case JSON_ERROR_SYNTAX:
                throw new JSONException('json_decode - Syntax error, malformed JSON');

            }

        } else if ($result === null)
                throw new JSONException('Invalid JSON');

        return $result;
    }

    /**
     * Encodes the object in json
     *
     * @param mixed   $obj    The object to encode
     * @param boolean $pretty When TRUE, the output is "pretty" with proper indentation
     * @param boolean $html   If true, return the JSON in a format suitable for display in a web browser
     *                          Default: false
     *
     * @return string
     */
    public static function encode($obj, $pretty = false, $html = false)
    {
        $s = json_encode($obj);

        if($pretty)
            return self::format($s, $html);

        return $s;
    }

    public static function encodeFlat($obj, $pretty = false, $html = false)
    {
        return JSONUtils::encode(ArrayUtils::flattenObjects($obj), $pretty, $html);
    }

    /**
     * Indents a flat JSON string to make it more human-readable
     *
     * @param string  $json The original JSON string to process
     * @param boolean $html If true, return the JSON in a format suitable for display in a web browser
     *                          Default: false
     *
     * @see http://recurser.com/articles/2008/03/11/format-json-with-php/
     * @author recurser.com
     *
     * @return string Indented version of the original JSON string
     */
    public static function format($json, $html = false)
    {
        $result    = '';
        $pos       = 0;
        $strLen    = strlen($json);
        $indentStr = $html ? '&nbsp;&nbsp;' : '  ';
        $newLine   = $html ? "<br/>\n" : "\n";
        $inQuote   = false;
        $escape    = false;

        for ($i = 0; $i <= $strLen; $i++) {

            // Grab the next character in the string
            $char = substr($json, $i, 1);

            // intercept escape char
            // if next char is double qouts, escape it!
            if ($char == '\\') {
                $escape = true;
                $result .= $char;
                continue;
            }

            // if prev is escape char, and current char is double qoutes
            // consider it is just a string, not the interperter
            // other wise, consider it's a regular string. @see line 176
            if ($char == '"' && !$escape) {
                $inQuote = !$inQuote;
            }

            // If this character is the end of an element,
            // output a new line and indent the next line
            if ($char == '}'/* || $char == ']'*/) {
                $result .= $newLine;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line
            if (($char == ',' && !$inQuote) || $char == '{' /*|| $char == '['*/) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // reset $escape
            $escape = false;
        }

        return $result;
    }
}

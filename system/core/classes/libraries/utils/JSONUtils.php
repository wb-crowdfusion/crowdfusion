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
        try {
            self::decode($string);
        } catch (JSONException $e) {
            return false;
        }

        return true;
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

        if (function_exists('json_last_error')) {
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    return $result;

                case JSON_ERROR_DEPTH:
                    throw new JSONException('json_decode - Maximum stack depth exceeded');

                case JSON_ERROR_CTRL_CHAR:
                    throw new JSONException('json_decode - Unexpected control character found');

                case JSON_ERROR_SYNTAX:
                    throw new JSONException('json_decode - Syntax error, malformed JSON');

                default:
                    throw new JSONException('Invalid JSON');
            }
        } elseif ($result === null) {
            throw new JSONException('Invalid JSON');
        }

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

        if ($pretty) {
            return self::format($s, $html);
        }

        return $s;
    }

    public static function encodeFlat($obj, $pretty = false, $html = false)
    {
        return JSONUtils::encode(ArrayUtils::flattenObjects($obj), $pretty, $html);
    }

    /**
     * Indents a flat JSON string to make it more human-readable
     * Use php function JSON_PRETTY_PRINT. Require php >= 5.4
     *
     * @param string  $json The original JSON string to process
     * @param boolean $html If true, return the JSON in a format suitable for display in a web browser
     *                          Default: false
     *
     * @return string Indented version of the original JSON string
     */
    public static function format($json, $html = false)
    {
        $format = str_replace(
            [
                '    ',
                '\/',
            ], [
                '  ',
                '/',
            ], json_encode(
                json_decode(
                    str_replace(
                        [
                            "\n",
                            '  ',
                            ', }',
                            ', ]',
                            ',}',
                            ',]',
                        ],
                        [
                            '',
                            '',
                            '}',
                            '}',
                            '}',
                            ']',
                        ],
                        $json
                    )
                ),
                JSON_PRETTY_PRINT
            )
        );

        if ($html) {
            return str_replace("\n", '<br/>', $format);
        }

        return $format;
    }
}

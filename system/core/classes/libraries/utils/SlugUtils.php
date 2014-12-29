<?php
/**
 * SlugUtils
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
 * @version     $Id: SlugUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Class for Slug handling.
 * Provides:
 *  Slug creation from a string,
 *  Add/Remove dates from a slug,
 *  Slug verification,
 *  Slug => String translation
 *
 * A Slug is a unique, short, human-readable text attribute used to identify records in our tables.
 * Slugs are URL friendly (meaning, they can be used in a URL without encoding) and
 * SEO-friendly (meaning, they should contain the preferred search keywords in a readable way)
 *
 *
 * @package     CrowdFusion
 */
class SlugUtils
{
    const SLUG_MATCH = '^[a-z0-9-]+$';
    const SLUG_WITH_SLASH_MATCH = '^([a-z0-9-]|[a-z0-9-][a-z0-9-\/]*[a-z0-9-])$';
    public static $strictMode = true;

    /**
     * Creates a slug from the text given.
     *
     * @param string  $string       A bit of text that we will generate our slug from.
     * @param boolean $allowSlashes If set to false, any slashes will be removed from the slug.
     *                                 Default: false
     *
     * @return string Our sluggified version of the {@link $string} param
     **/
    public static function createSlug($string, $allowSlashes = false)
    {
        $slug = '';
        $string = html_entity_decode($string, ENT_QUOTES);

        $string = preg_replace("/https*:\/\//", '', $string);
        for ($i=0; $i<=strlen($string); $i++) {
            $c = ord(substr($string, $i, 1));
            if ($c < 128)
                $slug .= chr($c);

            if (( $c >= 224 && $c <= 229) || ($c >= 192 && $c <= 198) || ($c >= 281 && $c <= 286)) {
                $slug .= 'a';
            } else if (($c >= 232 && $c <= 235) || ($c >= 200 && $c <= 203)) {
                $slug .= 'e';
            } else if (($c >= 236 && $c <= 239) || ($c >= 204 && $c <= 207)) {
                $slug .= 'i';
            } else if (($c >= 242 && $c <= 248) || ($c >= 210 && $c <= 216)) {
                $slug .= 'o';
            } else if (($c >= 249 && $c <= 252) || ($c >= 217 && $c <= 220)) {
                $slug .= 'u';
            } else if ($c==253 || $c==255 || $c==221 || $c==376) {
                $slug .= 'y';
            } else if ($c==230 || $c==198) {
                $slug .= 'ae';
            } else if ($c==338 || $c==339) {
                $slug .= 'oe';
            } else if ($c==199 || $c==231 || $c == 162) {
                $slug .= 'c';
            } else if ($c==209 || $c==241) {
                $slug .= 'n';
            } else if ($c==352 || $c==353) {
                $slug .= 's';
            } else if ($c==208 || $c==240) {
                $slug .= 'eth';
            } else if ($c==223) {
                $slug .= 'sz';
            } else if (($c>=8219 && $c<=8223) || $c==8242 || $c==8243 || $c==8216 || $c==8217 || $c==168 || $c==180 || $c==729 || $c==733) {
                //all the strange curly single and double quotes
                // Ignore them
            } else if ($c == 188) {
                $slug .= '-one-quarter-';
            } else if ($c == 189) {
                $slug .= '-one-half-';
            } else if ($c == 190) {
                $slug .= '-three-quarters-';
            } else if ($c == 178) {
                $slug .= '-squared-';
            } else if($c == 179) {
                $slug .= '-cubed-';
            } else if ($c > 127) {
                $slug .= '-';
            }
        }

        $find = array(
            "'",
            "\"",
            "\\",
            "&",
            "%",
            "@",
        );
        $repl = array(
            '',
            '',
            '',
            '-and-',
            '-percent-',
            '-at-',
        );

        $slug = str_replace($find, $repl, $slug);

        if (!$allowSlashes) {
            $slug = preg_replace("/[^a-zA-Z0-9\-]+/i", '-', $slug);
        } else {
            $slug = preg_replace("/[^a-zA-Z0-9\-\/]+/i", '-', $slug);
        }

        // replace more than one dash in a row
        $slug = preg_replace("/\-+/i", '-', $slug);

        if ($allowSlashes) {
            if (self::$strictMode) {
                $slug = str_replace(array('-/', '/-'), '/', $slug);
            }
            // replace more than one slash in a row
            $slug = preg_replace("/\/+/i", '/', $slug);
        }

        // remove leading and trailing dash and slash
        $slug = trim($slug, '/-');
        $slug = strtolower($slug);

        return $slug;
    }

    /**
     * Adds a date to the {@link $slug} given.
     *
     * The date format is up to the implementation.
     *
     * @param string $slug Slug that will have date added
     * @param Date   $date The date to add to the slug
     *
     * @return string The slug with the date
     */
    public static function addDateToSlug($slug, Date $date)
    {
        return $date->format('Y/m/d/').SlugUtils::removeDateFromSlug($slug);
    }

    /**
     * Removes the date in the format YYYY/mm/dd from the slug if it is found.
     *
     * The slug passed may or may not contain a date (most times it will)
     *
     * @param string $slug The slug to have the date removed
     *
     * @return string A slug without the date
     */
    public static function removeDateFromSlug($slug)
    {
        $slug = trim($slug, '/');
        while(preg_match('/^\d{4}\/\d{2}\/\d{2}\/?(\S+)?/', $slug, $m))
            $slug = trim(isset($m[1])?$m[1]:'', '/');
        return $slug;
    }

    /**
     * Detemines if the slug contains a date in the format YYYY/mm/dd
     *
     * @param string $slug The slug to check for a date
     * @return boolean
     */
    public static function slugContainsDate($slug)
    {
        return preg_match('/^\d{4}\/\d{2}\/\d{2}\/?(\S+)?/', $slug);
    }

    /**
     * Translates, as best as possible, a slug back into a human readable format.
     *
     * @param string $slug The slug to render human readable
     *
     * @return string A human readable string from the slug specified.
     */
    public static function unsluggify($slug)
    {
        $str = str_replace("-", " ", $slug);
        $words  = explode(" ", $str);
        $words  = array_map('ucfirst', $words);

        return implode(" ", $words);
    }

    /**
     * Determines if the string given is a slug or not.
     *
     * @param string  $string     The string to test
     * @param boolean $with_slash If true, then slashes will be allowed in the slug
     *
     * @return boolean True if string is a slug
     */
    public static function isSlug($string, $with_slash=true)
    {
        if ($with_slash)
            $match = '/'.self::SLUG_WITH_SLASH_MATCH.'/';
        else
            $match = '/'.self::SLUG_MATCH.'/';

        return preg_match($match, $string) > 0;
    }


    /**
     * Creates a slug from the camel case text given. Adds space in between words starting with capital letters and acronyms.
     *
     * @param string  $string       A bit of camel case text that we will generate our slug from.
     * @param boolean $allowSlashes If set to false, any slashes will be removed from the slug.
     *                                 Default: false
     *
     * @return string Our sluggified version of the {@link $string} param
     **/
    public static function createSlugFromCamelCase($string, $allowSlashes = false)
    {
        $string = trim(preg_replace('/(([A-Z]|[0-9])[^A-Z])/',' $1',$string));

        return SlugUtils::createSlug($string,$allowSlashes);
    }

}

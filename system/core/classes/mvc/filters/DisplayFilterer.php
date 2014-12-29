<?php
/**
 * Display filters
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
 * @version     $Id: DisplayFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Provides miscellaneous utility methods
 *
 * @package     CrowdFusion
 */
class DisplayFilterer extends AbstractFilterer
{
    /**
     * @var $InputClean InputClean
     */
    protected $InputClean;
    public function setInputClean(InputCleanInterface $InputClean) {
        $this->InputClean = $InputClean;
    }

    protected function getDefaultMethod()
    {
        return "raw";
    }


    /**
     * Returns a default if the specified value is empty
     *
     * Expected Params:
     *  value    string The value to return
     *  default  string The value to return if "value" is empty
     *
     * @return string
     */
    public function fallback()
    {
        $val = $this->getParameter('value');

        if(empty($val)) {
            return $this->getRequiredParameter('default');
        }

        return $val;
    }

    /**
     * Returns an alternating value each time the function is called.
     *
     * Expected Params:
     *  even string The parameter to return on even executions
     *  odd  string The parameter to return on odd executions
     *
     * @return string
     */
    public function alternate ()
    {
        static $counter;

        $counter++;

        if ($counter % 2 == 0)
            return $this->getParameter('even');

        return $this->getParameter('odd');
    }

    /**
     * Converts the raw value of parameter 'status' to the human readable value
     *
     * Campfire July 1st 2009, 10:40AM
     * Ryan: No, comment it out completely
     * Ryan: I don't think it makes any sense anymore
     *
     * @return void
     */
    // public function statusName() {
    //         if ($this->getParameter('type') == null) {
    //             return 'emptytype';
    //         } else {
    //             try { return AppContext::Model($this->getParameter('type'))->status((int)$this->getParameter('status')); }
    //             catch(Exception $e) { return 'filtererror'; }
    //         }
    //     }

    /**
     *  Returns a substring from param 'value'. Returns the 1st letter if only 'value' is specified
     *
     *  Expected Params:
     *   value  string  the string to operate upon
     *   start  integer (optional) the starting point for our substring, default 0
     *   length integer (optional) the length of the substring. default 1
     *
     * @return string
     */
    public function substring ()
    {
        $start  = $this->getParameter('start');
        $length = $this->getParameter('length');

        if (empty($start))
            $start = 0;

        if (empty($length))
            $length = strlen($this->getParameter('value')) - (int)$start;

        $str = substr($this->getParameter('value'), (int)$start, (int)$length);

        return $str;
    }

    /**
     * Returns debuggging information about the current vars
     *
     * Expected Params:
     *  scope string (optional) If set to 'globals', then dump the globals. Otherwise dump the locals.
     *
     * @return string
     */
    public function showVars()
    {
        $what = $this->getParameter('scope');
        switch ( $what ) {
            case 'global':
            case 'globals':
            case 'system':
            case 'systems':
                $debug = $this->getGlobals();
                break;

            default:
                $debug = $this->getLocals();
                break;
        }

        return '<pre>' . JSONUtils::encode($debug, true, false) . '</pre>';
    }

    /**
     * Returns debugging information about all passed parameters
     *
     * @return string
     */
    public function debug ()
    {
        return '<pre>' . JSONUtils::encode($this->getParameters(), true, false) . '</pre>';
    }

    /**
     * Replaces spaces with &nbsp; in the passed param
     *
     * Expected Params:
     *  value string The string to non-breakify
     *
     * @return string
     */
    public function nonBreaking()
    {
        return str_replace(' ', '&nbsp;', $this->getParameter('value'));
    }

    /**
     * Returns the parameter passed in without any conversions applied.
     *
     * Expected Param:
     *  value mixed any value
     *
     * @return mixed
     */
    public function raw()
    {
        return $this->getParameter('value');
    }

    /**
     * Escapes the specified parameter in 'value' for use in javascript
     *
     * Expected Param:
     *  value string
     *
     * @return string
     */
    public function jsEscape()
    {
        if ($this->getParameter('value') == null)
            return;
        return StringUtils::jsEscape($this->getParameter('value'));
    }

    /**
     * HTML-Escapes the specified parameter.
     * Converts all html chars into their entities equivalent
     *
     * Expected Param:
     *  value string
     *
     * @return string
     */
    public function htmlEscape()
    {
        if ($this->getParameter('value') == null)
            return;

        return str_replace('%', '&#37;', htmlentities($this->getParameter('value'), ENT_QUOTES, 'UTF-8', false));
    }

    /**
     * Replaces HTML entities with their character equivalents
     *
     * Expected Param:
     *  value string
     *
     * @return string
     */
    public function htmlDecode()
    {
        if ($this->getParameter('value') == null)
            return;

        return html_entity_decode($this->getParameter('value'), ENT_COMPAT, 'UTF-8');
    }

    /**
     * Performs a string replace on the specified param
     *
     * Expected Params:
     *  needle   string The item to search for
     *  haystack string The string to look in
     *  replace  string The string to replace the found text with
     *
     * @return string
     */
    function strReplace()
    {
        if (($this->getParameter('needle') == null) || ($this->getParameter('haystack') == null))
            return $this->getParameter('haystack');

        $needle = $this->getParameter('needle');
        if($needle == '\n')
            $needle = "\n";

        return str_replace($needle, $this->getParameter('replace'), $this->getParameter('haystack'));
    }

    /**
     * Performs a regular expression search and replace
     *
     * Expected Params:
     *  pattern regex  The regular expression used to locate the text to replace
     *  subject string The string to look at
     *  replace string The text to replace the found items with
     *
     * @return string
     */
    public function pregReplace()
    {
        if ($this->getParameter('pattern') == null)
            return $this->getParameter('subject');
        return preg_replace($this->getParameter('pattern'), $this->getParameter('replace'), $this->getParameter('subject'));
    }

    /**
     * Return the first character from a string.
     *
     * Expected Params:
     *  string   string
     *  lower    boolean  return string in lower case.  defaults to false
     *
     * @return string
     */
    protected function firstLetter()
    {
        $string = trim((string) $this->getParameter('string'));
        $lower = (boolean) $this->getParameter('lower');
        return StringUtils::firstLetter($string, $lower);
    }

    /**
     * URL encodes a string
     *
     * Expected Params:
     *  value string The string to URL-encode
     *
     * @return string
     */
    public function urlencode()
    {
        if ($this->getParameter('value') == null)
            return;
        return urlencode($this->getParameter('value'));
    }

    /**
     * Trims a string to a specified length.
     * It will allow tags listed in 'KeepTags' to remain and will intelligently
     * append the trimmed text with '...' or '-' depending on if the trim ends on a word or a space.
     *
     * Expected Params:
     *  len      integer The length to trim the string to
     *  value    string  The string to trim
     *  KeepTags string  A list of tags (like '<p><a>') that are allowed in the trimmed text.
     *
     * @return string
     */
    public function trim()
    {
        return StringUtils::trim(
            $this->getParameter('value'),
            $this->getParameter('len'),
            $this->getParameter('keeptags'),
            $this->getParameter('decodehtml'),
            $this->getParameter('stripurls'));
    }

    /**
     * Converts param to lowercase
     *
     * Expected Params:
     *  value string
     *
     * @return string
     */
    public function toLowerCase()
    {
        return strtolower($this->getParameter('value'));
    }

    /**
     * Converts param to UPPERCASE
     *
     * Expected Params:
     *  value string
     *
     * @return STRING
     */
    public function toUpperCase()
    {
        return strtoupper($this->getParameter('value'));
    }

    /**
     * Converts param to Captialized case
     *
     * Expected Params:
     *  value string
     *
     * @return String
     */
    public function upperCaseFirst()
    {
        return ucfirst($this->getParameter('value'));
    }

    /**
     * Returns the total count of all words in all params passed
     *
     * @return integer
     */
    public function wordCount()
    {
        $count = 0;

        foreach ($this->getParameters() as $value)
            $count += str_word_count(strip_tags($value));

        return $count;
    }

    /**
     * Returns the total count of all characters in all params passed
     *
     * @return integer
     */
    public function charCount()
    {
        $count = 0;

        foreach ($this->getParameters() as $value)
            $count += strlen(strip_tags($value));

        return $count;
    }


    /**
     * Passthru/Helper for StringUtils::stripHtml
     *
     * Accepts the following paramaters
     *  value: Html/text to strip html from
     *  strip: Comma separated list of tags to strip (ie "<object>,<script>")
     *  (optional) leavecontent: Keep content within html tags (defaults to false)
     *
     * @return string
     */
    public function stripHtml()
    {
        $value = $this->getRequiredParameter('value');
        $tags = $this->getRequiredParameter('strip');
        $keepContent = StringUtils::strToBool($this->getParameter('leavecontent'));

        return StringUtils::stripHtml($value, $tags, $keepContent);
    }

    /**
     * Returns the result of the formula passed in parameter 'formula'
     *
     * Expected Params:
     *  formula string A mathematical formula to calculate
     *
     * @return numeric result
     */
    public function calc()
    {
        require_once PATH_SYSTEM.'/vendors/EvalMath.php';

        return EvalMath::getInstance()->e($this->getParameter('formula'));
    }

    /**
     * Returns the domain name for the specified url
     *
     * Expected Params:
     *  value string The URL to parse for the domain
     *
     * @return string something like http://www.crowdfusion.com/
     */
    public function baseHref()
    {
        $url = parse_url($this->getParameter('value'));

        return $url['scheme'].'://'.$url['host'].'/';
    }

    /**
     * Returns the plural version of the parameter in 'value'
     *
     * Expected Params:
     *  value          string the string to pluralize
     *  upperCaseFirst string if set to 'true' then the result string will be Capitalized
     *
     * @return string
     */
    public function pluralize()
    {
        if($this->getParameter('upperCaseFirst') == 'true')
            return ucfirst(StringUtils::pluralize($this->getParameter('value')));

        return StringUtils::pluralize($this->getParameter('value'));
    }


    // public function inTypeClass($params) {
    //     $typesService = AppContext::Service('Types');
    //
    //     return in_array($this->getParameter('value'),
    //                 $typesService->getTypesByClass(AppContext::Element($this->getParameter('Element')),
    //                 $this->getParameter('Class'), true));
    // }

    /**
     * Returns the supplied snippet repeated X times
     *
     * Expected Params:
     *  value          string the string to repeat
     *  multiplier     int    the number of times to repeat the value
     *
     * @return string
     */
    public function repeat()
    {
        if ($this->getParameter('value') == null)
            return;

        $value = $this->getParameter('value');
        $multiplier = $this->getParameter('multiplier');
        $offset = $this->getParameter('offset');
        return str_repeat($value, (int)$multiplier + (int)$offset);
    }

    public function formatTime()
    {
        $seconds = intval((string)$this->getParameter('seconds'));

        $min = floor($seconds/60);

        $seconds = $seconds - ($min * 60);

        return $min.':'.($seconds < 10 ? '0' : '').$seconds;
    }

    public function clean()
    {
        return $this->InputClean->clean($this->getParameter('value'), $this->getParameter('allowedTags'));
    }

    /**
     * Returns a human readable file-size
     *
     * Expected Params:
     *  value          int    the number to convert
     *
     * @return string
     */
    public function humanFilesize()
    {
        return FileSystemUtils::humanFilesize($this->getParameter('value').'');
    }

    /**
     * Implode Array to String
     *
     * @return string
     */
    public function implode()
    {
        if (!is_array($this->getParameter('value'))) {
            return $this->getParameter('value');
        }
        $glue = $this->getParameter('glue');

        if ($glue === null) {
            $glue = '&nbsp;';
        }

        return implode($glue, $this->getParameter('value'));

    }
}

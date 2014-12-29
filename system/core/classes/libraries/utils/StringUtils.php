<?php
/**
 * StringUtils
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
 * @version     $Id: StringUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Provides utilities for analyzing strings:
 *  Advanced search,
 *  smart splitting
 *  padding,
 *  and other utilities
 *
 * @package     CrowdFusion
 */
class StringUtils
{
    /**
     * Determines if {@link haystack} ends with {@link needle}
     *
     * @param string $haystack A phrase that might end with needle
     * @param string $needle   A phrase to locate in haystack
     *
     * @return boolean TRUE if {@link haystack} ends with {@link $needle}
     */
    public static function endsWith($haystack, $needle)
    {
        $hl = strlen($haystack);
        $nl = strlen($needle);
        if(substr($haystack, $hl-$nl, $hl) == $needle)
            return true;

        return false;
    }


    /**
     * Determines if {@link haystack} starts with {@link needle}
     *
     * @param string $haystack A phrase that might start with needle
     * @param string $needle   A phrase to locate in haystack
     *
     * @return boolean TRUE if {@link haystack} starts with {@link $needle}
     */
    public static function startsWith($haystack, $needle)
    {
        return strpos($haystack, $needle) === 0;
    }


    /**
     * Determines if {@link haystack} contains at least {@link $wordthreshold} words from {@link $needle}
     *
     * @param string  $needle        A phrase to locate in haystack
     * @param string  $haystack      A phrase that might contain needle
     * @param integer $wordthreshold Default: 4. The number of words that must be in haystack for true
     *
     * @return boolean TRUE if {@link haystack} contains at least {@link $wordthreshold} words from {@link $needle}
     */
    public static function smartStrcmp($needle, $haystack, $wordthreshold = 4)
    {

        $words1       = array_slice(explode(" ", strtolower($needle)), 0, 3);
        $totwords1    = count($words1);
        $words2       = array_slice(explode(" ", strtolower($haystack)), 0, $wordthreshold);
        $totwords2    = count($words2);

        $exactmatched = 0;
        foreach ($words1 as $word) {
            if (in_array(trim($word), $words2))
                ++ $exactmatched;
        }

        if ($exactmatched == $totwords1)
            return true;

        return false;

    }

    /**
     * Splits a string into an array, exploded by spaces.
     * However, it takes "quoted strings" into account,
     * will count them as a single item.
     *
     * @param string $string String to split
     *
     * @see    http://us2.php.net/manual/en/function.strtok.php#53244
     * @author brian dot cairns dot remove dot this at commerx dot com
     *
     * @return array An array containing the split words
     */
    public static function wordsTokenized($string)
    {
        for ($tokens = array(), $nextToken = strtok($string, ' '); $nextToken !== false; $nextToken = strtok(' ')) {
            if ($nextToken{0} == '"')
                $nextToken = $nextToken{strlen($nextToken)-1} == '"' ?
                                '"' . substr($nextToken, 1, -1) . '"'
                              : '"' . substr($nextToken, 1) . ' ' . strtok('"') . '"';

            $tokens[] = $nextToken;
        }
        return $tokens;
    }


    /**
     * Split a string around a delimiter, taking into account quoted substrings
     *
     * @param string $data      big string with data
     * @param string $delimiter Field delimiter
     * @param string $quote     quote field character
     * @param string $escape    escaped quote character
     * @param int    $limit     Return at most this many parts
     *
     * @return array of all splitted lines
     */
    public static function smartSplit($data, $delimiter = ';', $quote = "'", $escape = "\\'", $limit = null)
    {
        $results = array ();

        // Split like normal to start.
        $lines = explode($delimiter, $data);

        if (empty ($lines))
            return array ();

        $broke = false;
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // Now, add each fully quoted line
            if (!self::lineIsOpenQuoted($line, $quote, $escape))
                $results[] = $line;
            else {
                // Otherwise, add each new line until we find the first closed line
                do {
                    $line .= $delimiter . $lines[++ $i];
                } while (($i +1) < count($lines) && self::lineIsOpenQuoted($line, $quote, $escape));

                $results[] = $line;
            }
        }


        if (!is_null($limit) && count($results) > $limit) {
            $newresults = array();
            for ($i = 0; $i < ($limit-1); $i++) {
                $newresults[] = array_shift($results);
            }
            $newresults[] = implode($delimiter, $results);
            return $newresults;
        }

        return $results;
    }

    /**
     * Determines if a line is "open-quoted" and continues to the next line.
     *
     * Most useful for parsing CSV files.
     *
     * @param string $line   The line to examine
     * @param string $quote  The quote character (must be one character)
     * @param string $escape The escape character or character set
     *
     * @return void
     */
    public static function lineIsOpenQuoted($line, $quote, $escape)
    {
        // Returns TRUE if line is valid
        $pos        = 0;
        $quote_open = false;
        $esc_len    = strlen($escape);

        for (; $pos < strlen($line); ++ $pos) {
            $char = $line[$pos];

            // If the character is part of an escape squence, advance the string.
            if (substr($line, $pos, $esc_len) == $escape) {
                $pos += $esc_len - 1; // Subtract one because we advance on loop start.
                continue;
            }

            // Otherwise, toggle the quote flag if this is a quote character
            if ($char == $quote)
                $quote_open = !$quote_open;

            // Continue if the character is anything else.
        }

        if ($quote_open)
            return true;
        else
            return false;
    }

    /**
     * Returns all of string {@link $s1} up to the point where string {@link $s2} begins.
     *
     * @param string $s1 The string to return a partial result of
     * @param string $s2 The string to match
     *
     * @return string The partial string
     */
    public static function strLeft($s1, $s2)
    {
        return substr($s1, 0, strpos($s1, $s2));
    }

    /**
     * Returns all of string {@link $s1} beyond the point where string {@link $s2} ends.
     *
     * @param string $s1 The string to return a partial result of
     * @param string $s2 The string to match
     *
     * @return string The partial string
     */
    public static function strRight($s1, $s2)
    {
        return substr($s1, strpos($s1, $s2)+1);
    }

    /**
     * @static copied from DisplayFilterer
     * @param  $value
     * @param  $len
     * @param null $keeptags
     * @param null $decodehtml
     * @param null $stripurls
     * @return mixed|string
     */
    public static function trim($value,$len = null,$keeptags = null,$decodehtml = null,$stripurls = null)
    {
        if ($len == null)
            return;

        $trimamount = $len;
//        $trimstr = str_replace('>','> ',$value);
        $trimstr = $value;
        $trimstr = str_replace("\n",' ',$trimstr);
        $trimstr    = strip_tags($trimstr, ($keeptags != null) ? $keeptags :   '');
        $nontags = 0;
	    $returnstr = '';

        $trimstr = preg_replace("/\s+/",' ',$trimstr);
//        $trimstr = str_replace('> ','>',$trimstr);
        $tagarr = array();
        $notag = 0;

        for($i=0; $i<strlen($trimstr); $i++)
        {
            if((substr($trimstr,$i,1) == '<') && (strpos(substr($trimstr,$i + 1),'>') != false))
            {
        		$tagstr = '';
                do {
                    $returnstr .= substr($trimstr,$i,1);
                    $tagstr .= substr($trimstr,$i,1);
                } while (substr($trimstr,++$i,1) != '>');

                $tagstr .= substr($trimstr,$i,1);
                if(preg_match("/<\/(\w+)>/s",$tagstr,$m))
                {
                    $tag = array_shift($tagarr);
                    while(!empty($tag) && $tag != $m[1])
                    {
                        if(preg_match("/(.+<{$tag}[^>]+>)(.*)/s",$returnstr,$m2))
                        {
                            $returnstr = $m2[1]."</{$tag}>".$m2[2];
                        }
                        $tag = array_shift($tagarr);
                    }
                }
//                if(!preg_match("/<[^>]+\/>/s",$tagstr) && preg_match("/<(\w+)[^>]+>/s",$tagstr,$m))
                if(!preg_match("/<[^>]+\/>/s",$tagstr) && preg_match("/<(\w+)[^>]*>/s",$tagstr,$m))
                {
                    array_unshift($tagarr,"$m[1]");
                }
            } else {
                if(++$notag>$trimamount) break;
            }
            $returnstr .= substr($trimstr,$i,1);
        }
        if(strlen($returnstr)<strlen($trimstr) && preg_match("/(.+)\s/",$returnstr,$m))
            $returnstr = $m[1].'&hellip;';
        while($tag = array_shift($tagarr))
            $returnstr .= "</$tag>";

        if ($decodehtml != null)
            $returnstr = html_entity_decode($returnstr, ENT_COMPAT, 'UTF-8');

/*        if (strlen($trimstr) >= $trimamount) {
            if (preg_match("/(.{1,".$trimamount."})\s/s", $trimstr, $m))
                $trimstr = $m[1]."&#8230;";
            elseif (preg_match("/(.{1,".$trimamount."})/s", $trimstr, $m))
                $trimstr = $m[1].'-';
        } */

        if ($keeptags == null) {
            if($stripurls === null)
                $returnstr = preg_replace("/(https*:\/\/((\S{1,50})\S*))/", "<a title=\"$1\" href=\"$1\">$3...</a>", $returnstr);
            else if(StringUtils::strToBool($stripurls) == true)
                $returnstr = preg_replace("/(".URLUtils::URL_MATCH.")/ix", "", $returnstr);
        }

        return $returnstr;
    }

    /**
     * Trims the character {@link $char} from the beginning and/or end
     * of the string {@link $string}.
     *
     * Takes at most one character from the front and at most
     * one character from the end.
     *
     * @param string $string The string to process
     * @param string $char   The character to strip from beginning and end
     *
     * @return string the stripped string
     */
    public static function trimOnce($string, $char)
    {

        if (substr($string, 0, 1) == $char)
            $string = substr($string, 1);
        if (substr($string, -1) == $char)
            $string = substr($string, 0, -1);

        return $string;
    }

    /**
     * When given a string {@link $str} this function will return the same
     * string with a newline character appended to it.
     *
     * @param string $str The string that can has newline at the end
     *
     * @return string String + newline at end
     */
    public static function l($str = '')
    {
        return "{$str}\n";
    }


    /**
     * Explodes a string into an array split upon ';' or ',' characters
     *
     * @param string $string The string to *EXPLODE*
     *
     * @return array
     **/
    public static function smartExplode($string)
    {
        if (empty($string))
            return array();

        if (is_array($string))
            return $string;

        if (!is_array($string)) {
            if (strpos($string, ';') !== false)
                return explode(';', trim($string, ';'));

            if (strpos($string, ',') !== false)
                return explode(',', trim($string, ','));
        }

        return (array)$string;
    }

    /**
     * Escapes the specified string for use in javascript
     *
     * @param string $str The string to escape
     *
     * @return string
     */
    public static function jsEscape($str)
    {
        $javascript = preg_replace('/\r\n|\n|\r/', "\\n", $str);
        $javascript = preg_replace('/(["\'])/', '\\\\\1', $javascript);
        return $javascript;
    }

    protected static $tempFV;

    /**
     * Replaces template variables in the {@link $string}
     *
     * @param string $string      The string containing template variables
     * @param array  $arrayOfVars A key => value array of template vars to replace
     *
     * @return string
     */
    public static function replaceTemplateVariables($string, $arrayOfVars)
    {
        self::$tempFV = $arrayOfVars;

        $ret = preg_replace_callback("/\%([A-Za-z]{1}[\w\-\_\:\.\[\]\=\#\~]+?)\%/",
                                     array('StringUtils', '_fvCallback'),
                                     $string);

        return $ret;
    }

    /**
     * Internal use only.
     *
     * @param string $m do not use
     *
     * @ignore
     * @return string
     */
    private static function _fvCallback($m)
    {
        if (array_key_exists($m[1], self::$tempFV))
            return self::$tempFV[$m[1]];

        return $m[0];
    }

    /**
     * Converts strings to underscore format.
     *
     * Example:
     *  'ThisIsSomeText'    => 'this_is_some_text'
     *  'IDONTKnow'         => 'idont_know'
     *  'What::IsGoing_ON?' => 'what/is_going_on_'
     *
     * @param string $word The word to underscore
     *
     * @return string
     */
    public static function underscore($word)
    {
        return strtolower(preg_replace('/[^A-Z^a-z^0-9^\/]+/',
                                        '_',
                                        preg_replace('/([a-z\d])([A-Z])/',
                                            '\1_\2',
                                            preg_replace('/([A-Z]+)([A-Z][a-z])/',
                                                '\1_\2',
                                                preg_replace('/::/',
                                                    '/',
                                                    $word)))));
    }

    /**
     * Converts strings to "human" format.
     *
     * Example:
     *  'i_am_not_a_robot' => 'I am not a robot'
     *
     * @param string $word      The word to convert to human form
     * @param string $uppercase If set to 'all', then all the words will be Capitalized
     *
     * @return string
     */
    public static function humanize($word, $uppercase = '')
    {
        $uppercase = $uppercase == 'all' ? 'ucwords' : 'ucfirst';
        return $uppercase(str_replace('_', ' ', preg_replace('/_id$/', '', $word)));
    }

    /**
     * Strips accent characters from the input text
     *
     * @param string $text The input text
     *
     * @return string
     */
    public static function unaccent($text)
    {
        return strtr($text, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ',
                            'AAAAAAACEEEEIIIIDNOOOOOOUUUUYTsaaaaaaaceeeeiiiienoooooouuuuyty');
    }

    /**
     * Converts the {@link $word} into CamelCase
     *
     * Example:
     *  'error/i_dont_know_robot' => 'Error::IDontKnowRobot'
     *
     * @param string $word The word to convert
     *
     * @return string
     */
    public static function camelize($word)
    {
        if (preg_match_all('/\/(.?)/', $word, $got)) {
            foreach ($got[1] as $k => $v)
                $got[1][$k] = '::'.strtoupper($v);

            $word = str_replace($got[0], $got[1], $word);
        }
        return str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9^:]+/', ' ', $word)));
    }

    /**
     * Converts the {@link $word} into a Titlized format.
     *
     * @param string $word      The word to convert
     * @param string $uppercase If set to 'all', then all words will be Capitalized, not just the first.
     *
     * @return string
     */
    public static function titleize($word, $uppercase = '')
    {
        $uppercase = $uppercase == 'first' ? 'ucfirst' : 'ucwords';
        return $uppercase(self::humanize(self::underscore($word)));
    }

    /**
     * Takes a word, and makes it plural.
     *
     * @param string $word The word to pluralize
     *
     * @return string
     */
    public static function pluralize($word)
    {
        $plural = array(
        '/(quiz)$/i' => '\1zes',
        '/^(ox)$/i' => '\1en',
        '/([m|l])ouse$/i' => '\1ice',
        '/(matr|vert|ind)ix|ex$/i' => '\1ices',
        '/(x|ch|ss|sh)$/i' => '\1es',
        '/([^aeiouy]|qu)ies$/i' => '\1y',
        '/([^aeiouy]|qu)y$/i' => '\1ies',
        '/(hive)$/i' => '\1s',
        '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
        '/sis$/i' => 'ses',
        '/([ti])um$/i' => '\1a',
        '/(buffal|tomat)o$/i' => '\1oes',
        '/(bu)s$/i' => '\1ses',
        '/(alias|status)/i'=> '\1es',
        '/(octop|vir)us$/i'=> '\1i',
        '/(ax|test)is$/i'=> '\1es',
        '/s$/i'=> 's',
        '/$/'=> 's');

        $uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

        $irregular = array(
        'person' => 'people',
        'man' => 'men',
        'child' => 'children',
        'sex' => 'sexes',
        'move' => 'moves');

        $lowercased_word = strtolower($word);

        foreach ($uncountable as $_uncountable) {
            if (substr($lowercased_word, (-1 * strlen($_uncountable))) == $_uncountable)
                return $word;
        }

        foreach ($irregular as $_plural=> $_singular) {
            if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
                return preg_replace('/('.$_plural.')$/i', substr($arr[0], 0, 1) . substr($_singular, 1), $word);
            }
        }

        foreach ($plural as $rule => $replacement) {
            if (preg_match($rule, $word)) {
                return preg_replace($rule, $replacement, $word);
            }
        }
        return false;

    }

    /**
     * Translates the given string into a boolean value if it's in the proper format
     *
     * @param string $string The string to convert
     *
     * @return boolean true or false, translated
     * @throws Exception if string cannot be converted
     */
    public static function strToBool($string)
    {
        if(is_bool($string))
            return $string;

        if (in_array(strtolower((string)$string), array('1', 'true', 'on', '+', 'yes', 'y')))
            return true;
        elseif (in_array(strtolower((string)$string), array('', '0','false', 'off', '-', 'no', 'n')))
            return false;

        return false;
    }

    /**
     * Recursively strips slashes from all values in the array
     *
     * @param mixed $value If an array, all values will be stripslashed()
     *                     If a string, then it will be stripslashed()
     *
     * @return array The stripped array
     */
    public static function stripslashesDeep($value)
    {
        $value = is_array($value) ?
                    array_map(array('StringUtils', 'stripslashesDeep'), $value) :
                    stripslashes($value);

        return $value;
    }

    public static function utf8Strlen($value)
    {
        return strlen(utf8_decode($value));
    }

    public static function utf8Substr($value, $index, $length) {
       return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $index .'}'.'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $length .'}).*#s','$1', $value);
    }

    /*
     * Truncates a string to $len bytes stripping any UTF-8 invalid byte
     * sequences that may result
     *
     * @param string $s   - The string to be truncated
     * @param int    $len - The output string's maximum byte length
     * @return string
     */
    public static function utf8SafeTruncate($s, $len)
    {
        if (strlen($s) <= $len) { return $s; }
        return preg_replace('/ ( [\xC0-\xFF]               |
                                 [\xE0-\xFF][\x80-\xBF]    |
                                 [\xF0-\xFF][\x80-\xBF]{2}   ) $ /x',
                            '',
                            substr($s, 0, $len));
    }

    public static function wordSubstr($text, $maxLength, $extra = '...')
    {
       if (strlen($text) > $maxLength)
       {
           $sString = wordwrap($text, ($maxLength-strlen($extra)), '[cut]', 1);
           $asExplodedString = explode('[cut]', $sString);

           $sCutText = $asExplodedString[0];

           $sReturn = $sCutText.$extra;
       }
       else
       {
           $sReturn = $text;
       }

       return $sReturn;
    }

    public static function extractFloat($text)
    {
        return preg_replace('/([\d,]+\.?\d*)/', '$1', str_replace('$', '', $text));
    }

    public static function charCount($text)
    {
        if(function_exists('mb_strlen'))
            return mb_strlen($text, 'UTF-8');
        else
            return strlen(utf8_decode($text));
    }

    public static function byteCount($text)
    {
        if(function_exists('mb_strlen'))
            return mb_strlen($text, 'latin1');
        else
            return strlen($text);
    }

    public static function substring($text, $start, $length)
    {
        if(function_exists('mb_substr'))
            return mb_substr($text, $start, $length, 'UTF-8');
        else
            return substr($text, $start, $length);
    }

    /**
     * Return the first letter (alpha or numeric) from a string.
     *
     * @param string $str
     * @param bool $lower   convert result to lowercase
     *
     * @return string
     */
    public static function firstLetter($str, $lower = false)
    {
        $string = trim($str);
        if (empty($str)) {
            return '';
        }

        $string = preg_replace('/[^a-zA-Z0-9]/', '', $string);
        if (empty($string)) {
            return '';
        }

        if ($lower) {
            $string = strtolower($string);
        }

        return $string[0];
    }

    /**
     * Strips a string of HTML tags specified, can strip content or just remove tags
     *
     * @param string $value       Html/text to strip html from
     * @param string $tags        Comma separated list of tags to strip (ie "<object>,<script>")
     * @param bool   $keepContent Keep content within html tags (defaults to false)
     *
     * @return string
     * @static
     * @see http://us.php.net/manual/en/function.strip-tags.php#96483
     */
    public static function stripHtml($value, $tags = null, $keepContent = false)
    {
        $content = '';
        if(!is_array($tags)) {
            $tags = (strpos($value, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
            if(end($tags) == '') array_pop($tags);
        }
        foreach($tags as $tag) {
            if (!$keepContent)
                 $content = '(.+</'.$tag.'[^>]*>|)';
             $value = preg_replace('#</?'.$tag.'[^>]*>'.$content.'#is', '', $value);
        }
        return $value;
    }

}

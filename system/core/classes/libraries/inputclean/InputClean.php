<?php
/**
 * InputClean
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
 * @version     $Id: InputClean.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Provides a bunch of helpful methods used to clean up strings.
 * Provides the ability to:
 *  Strip html (with options),
 *  Encode html, decode html,
 *  Purify html (strip all but a few trusted strings),
 *  Automatically paragraph and autolink urls in text,
 *  Undo the previous,
 *  And produce a "safe" URL stripped of any XSS or invalid characters
 *
 * @package     CrowdFusion
 */
class InputClean implements InputCleanInterface
{
    protected $vendorCacheDirectory;
    protected $charset;
    protected $defaultAllowedTags;

    protected $purifier;

    /**
     * Creates the input clean object. This is used to clean up strings
     *
     * @param string $vendorCacheDirectory         The cache directory used by HTMLPurifier
     * @param string $charset                      Defines character set used in conversion.
     *                                              See http://us.php.net/manual/en/function.htmlspecialchars.php for full list
     * @param string $inputCleanDefaultAllowedTags A comma-separated list of default allowed tags
     */
    public function __construct($vendorCacheDirectory, $charset, $inputCleanDefaultAllowedTags = "b,i,em,strong,a[href],p,br")
    {
        $this->charset              = $charset;
        $this->vendorCacheDirectory = $vendorCacheDirectory;
        $this->defaultAllowedTags   = $inputCleanDefaultAllowedTags;
    }

    /**
     * The set of injectors to use with HTMLPurifier
     *
     * @var array
     **/
    protected $injectors;

    /**
     * Convert special characters to HTML entities
     *
     * @param string $text to encode
     *
     * @return string encoded string
     */
    public function htmlEncode($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, $this->charset, false);
    }

    /**
     * Decodes entities
     *
     * @param string $text String to decode
     *
     * @return string decoded string
     */
    public function htmlDecode($text)
    {
        return html_entity_decode($text, ENT_COMPAT, $this->charset);
    }


    /**
     * Cleans XSS by removing all tags
     *
     * @param string $string to clean
     *
     * @return string cleaned
     */
    public function clean($string, $allowedTags = null)
    {
        if (is_array($string)) {
            $new_array = array();
            foreach ($string as $key => $val) {
                $new_array[$key] = $this->clean($val, $allowedTags);
            }
            return $new_array;
        }

        $string = $this->cleanHTMLPurify($string, $allowedTags);

        return str_replace(array("\r\n", "\r"), "\n", $string);
    }

//    public function cleanTitles($string)
//    {
//        //Odd Characters
//        $search = array (
//            "\xe2\x80\xa6", // ellipsis
//            "\xe2\x80\x93", // long dash
//            "\xe2\x80\x94", // long dash
//            "\xe2\x80\x98", // single quote opening
//            "\xe2\x80\x99", // single quote closing
//            "\xe2\x80\x9c", // double quote opening
//            "\xe2\x80\x9d", // double quote closing
//            "\xe2\x80\xa2" // dot used for bullet points
//        );
//
//        $replace = array (
//            '...',
//            '-',
//            '-',
//            "'",
//            "'",
//            '"',
//            '"',
//            '*'
//        );
//
//        $text = trim($text);
//        $text = str_replace($search, $replace, $text);
//        $text = preg_replace('/&#x?([a-f0-9]+);/mei', "", $text); //No Numeric codes
//        $text = strip_tags($text);
//
////        if (!in_array('slashes', $allow) && get_magic_quotes_gpc())
////            $text = StringUtils::stripslashesDeep($text);
//
////        if (!in_array('entities', $allow))
////            $text = html_entity_decode($text); //Decode entities
//
//
//        return $text;
//    }

    /**
     * Produces a nicely-formatted HTML string with all non-matching tags stripped.
     *
     * @param string $string      The HTML string to format
     * @param string $allowedTags A comma separated list of tags to allow in the result
     *
     * @return string A nicely-formatted version of the input text, containing only the tags specified.
     */
    protected function cleanHTMLPurify($string, $allowedTags = null)
    {
        if(strlen($string)==0)
            return '';

        if($allowedTags === null)
            $allowedTags = $this->defaultAllowedTags;

        if($allowedTags == '*')
            return $string;

        if(is_null($this->purifier))
        {
            require_once PATH_SYSTEM . '/vendors/HTMLPurifier.php';

            $this->purifier = HTMLPurifier::instance();

            FileSystemUtils::recursiveMkdir($this->vendorCacheDirectory.'/purifier/');
        }


        $string = $this->purifier->purify($string, array(
                'Core.Encoding'            => $this->charset,
                'HTML.TidyLevel'           =>'none',
                'HTML.Allowed'             => $allowedTags,
                'AutoFormat.AutoParagraph' => false,
                //'HTML.Doctype'           => 'XHTML 1.0 Transitional',
                'Cache.SerializerPath'     => $this->vendorCacheDirectory.'/purifier/'
                ));

//        $string = html_entity_decode($string, ENT_COMPAT, $this->charset);

        return $string;
    }


    /**
     * Marks up a string with paragraphs and automatically links any urls.
     *
     * This function marks up the output with paragraph tags and auto-links any URLs that are found.
     * The resulting output is suitable for display in any web-browser, but must have
     * paragraph and extra html tags removed before it's ready for editing.
     *
     * Content is XSS cleaned and stripped of all but a few tags (specified by implementation.)
     *
     * @param string $string      The HTML string to format
     * @param string $allowedTags (optional) A comma-separated list of allowed tags.
     *
     * @return string A nicely-formatted version of the input text, with automatic paragraphs and urls in place
     *
     * @see unAutoParagraph()
     */
    public function autoParagraph($string, $allowedTags = null, $linkUrls = true)
    {
        if(is_null($allowedTags))
            $allowedTags = $this->defaultAllowedTags;

        if(is_null($this->purifier))
        {
            require_once PATH_SYSTEM . '/vendors/HTMLPurifier.php';

            $this->purifier = HTMLPurifier::instance();

            FileSystemUtils::recursiveMkdir($this->vendorCacheDirectory.'/purifier/');
        }

        if ($this->injectors == null && $linkUrls)
            $this->injectors = array(new CF_HTMLPurifier_Injector_Linkify());

        $purifierConfig = array(
                'Core.Encoding'            => $this->charset,
                'AutoFormat.AutoParagraph' => true,
                'HTML.TidyLevel'           =>'none',
                'HTML.Allowed'             => $allowedTags,
                'Cache.SerializerPath'     => $this->vendorCacheDirectory
                );

        if(!is_null($this->injectors))
            $purifierConfig['AutoFormat.Custom'] = $this->injectors;

        $string = $this->purifier->purify($string, $purifierConfig);

        $string = str_replace("\n\n", '[DBLBR]', $string);
        $string = str_replace("\n", '<br/>', $string);
        $string = str_replace('[DBLBR]', "\n\n", $string);

        // trim links
        $string = preg_replace_callback("/\<a\s+href\=\"(".URLUtils::URL_MATCH.")\"\>\\1<\/a\>/Uix",
                    array($this, 'trimCallback'), $string);


        // trim all words longer than 60 chars that aren't URLs, ignoring tags
        if (preg_match_all("/\S60/", strip_tags(preg_replace('/(\<(\/?[^\>]+)\>)/', ' $1', $string)), $m)) {
            foreach ($m[0] as $n) {
                if( !preg_match("/".URLUtils::URL_MATCH."/", $n) )
                    $string = str_replace($n, trim(substr($n, 0, (60 - 3)), '.') . '...', $string);
            }
        }


        return $string;
    }

    /**
     * A callback used internally to trim long urls
     *
     * NOTE: This function is intended to be used from preg_replace_callback.
     * See: http://php.net/preg_replace_callback for full details on how this function
     * is expected to be called.
     *
     * @param array $m An array of matches constructed from preg_replace_callback
     *
     * @return string the replace value
     */
    protected function trimCallback($m)
    {
        if (strlen($m[1]) > 60)
            return '<a href="'.$m[1].'">'.substr($m[1], 0, (60 - 3)).'...</a>';

        return $m[0];
    }

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
    public function unAutoParagraph($string)
    {
        $string = str_replace(array("<p>","</p>","<br/>", "<br />"), array("", "\n\n", "\n", "\n"), $string);

        $string = preg_replace("/\<a href\=\"(".URLUtils::URL_MATCH.")\"\>\\1\<\/a\>/i", '$1', $string);
        $string = preg_replace_callback("/\<a href\=\"(".URLUtils::URL_MATCH.")\"\>(.+)\.\.\.\<\/a\>/iU",
            array($this, 'linkMatches'), $string);

        return $string;
    }

    /**
     * Internal array used in unAutoParagraph
     *
     * @param array $matches regex matches
     *
     * @return string matched item
     */
    protected function linkMatches($matches)
    {
        if ($matches[2] == substr($matches[1], 0, (60 - 3))) {
            return $matches[1];
        } else {
            return $matches[0];
        }
    }


} // END class InputClean implements InputCleanInterface
?>

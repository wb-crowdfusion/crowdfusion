<?php
/**
 * LinkFilterer
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
 * @version     $Id: LinkFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * LinkFilterer
 *
 * @package     CrowdFusion
 */
class LinkFilterer extends AbstractFilterer
{

//    static $autolinkedCache;

    /**
     * Automatically links profile pages to keywords matching TagLinkDisplay
     *
     * Expected Locals:
     *  SerializedOutTags array   An array of tags to auto link
     *  id                integer (Optional) The record ID
     *
     * Expected Params:
     *  value  string The content that will be analyzed for keywords
     *  rel    string (optional) The REL parameter for the created <A> tags
     *  target string (optional) The TARGET parameter for the created <A> tags
     *
     * @return string Content that has keywords that match tags linked to the tags they match
     */
//    public function autoLink ()
//    {
//        // Array of tags to auto link
//        $tags     = $this->getLocal('SerializedOutTags');
//        $recordid = $this->getLocal('id');
//
//        if ($recordid == null)
//            $recordid = '0';
//
//        $content = $this->getParameter('value');
//
//        foreach ( $tags as $tag ) {
//            // Need to match TagLinkDisplay, but only outside of linked text.
//            $keyword = $tag['TagLinkDisplay'];
//            $link = $tag['TagLinkURL'];
//
//            if (empty($link) || isset($autolinkedCache[$recordid][$keyword]) || $tag['TagElement'] != 'pages')
//                continue;
//
//            $count = 1;
//
//            $rel    = $this->getParameter('rel');
//            $target = $this->getParameter('target');
//
//            $newlink = '<a href="' . $link .'" title="View '.htmlentities($tag['TagLinkDisplay'], ENT_QUOTE, 'UTF-8', true).'"'.
//                            (!empty($rel)?" rel=\"{$rel}\"":""). (!empty($target)?" target=\"{$target}\"":"").'>' .
//                        $keyword . '</a>';
//
//            $contentbefore = '';
//            $contentafter  = $content;
//
//            $offset = 0;
//            while ($count > 0) {
//
//                // Replace the first occurence of keyword with hyperlinked version
//                $contentafter = preg_replace('{\b' . $keyword . '\b}i', $newlink, $contentafter, 1);
//
//                $autolinkedCache[$recordid][$keyword] = 1;
//
//                $content = $contentbefore.$contentafter;
//
//                // Remove inner hyperlinks created by step1 above.
//                $content = preg_replace('{(<a[^<]*)'.$newlink.'([^<]*</a>)}i', '$1' . $keyword . '$2', $content, -1, &$count);
//
//                // find the next occurence if the previous one was inside a link
//                if ($count > 0) {
//                    $found = strpos($content, $keyword, $offset);
//                    $contentbefore = substr($content, 0, $found+strlen($keyword));
//                    $contentafter = substr($content, $found+strlen($keyword));
//
//                    $offset = $found+strlen($keyword);
//
//                    unset($autolinkedCache[$recordid][$keyword]);
//                }
//            }
//        }
//
//        return $content;
//    }

    /**
     * Returns a url safe from referers
     *
     * Expected Params:
     *  url string A url to be filtered through hiderefer.com
     *
     * @return string  The specified url filtered through hiderefer.com
     */
    public function noReferer()
    {
        if ($this->getParameter('url') == null)
            return;

        if ($this->getParameter('service') == null)
            return "http://hiderefer.com/?" . $this->getParameter('url');

        return $this->getParameter('service') . $this->getParameter('url');
    }

    /**
     * Returns a "safe" url, stripped of xss, autolinked on domain, and stripped of invalid chars
     *
     * Expected Params:
     *  val string the URL that will be autolinked and cleaned
     *
     * @return string a "safe" url, stripped of xss, autolinked on domain, and stripped of invalid chars
     */
    public function safeURL()
    {
        return URLUtils::safeURL($this->getParameter('val'));
    }


    /**
     * Constructs a URL with query string arguments from the given base url and parameters
     *
     * Expected Params:
     *  url string the URL to build a query string from
     *  [params] mixed any additional parameters serve as query string arguments
     *
     * @return string a complete url
     */
    public function appendQueryString()
    {
        $url = $this->getParameter('url');
        $params = $this->getParameters();
        unset($params['url']);

        return URLUtils::appendQueryString($url, $params);
    }

    /**
     * Takes the content specified by parameter 'value' and converts all urls
     * contained within into links
     *
     * Expected Params:
     *  value string The content
     *
     * @return string the specified content with all urls contained within turned into links
     */
    public function autoLinkURLs()
    {
        return URLUtils::autoLinkUrls($this->getParameter('value'));
    }


}
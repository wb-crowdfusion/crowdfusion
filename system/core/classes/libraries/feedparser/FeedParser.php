<?php
/**
 * FeedParser
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
 * @version     $Id: FeedParser.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * RSS feed parser implementation utilizing the SimplePie library. (http://simplepie.org/wiki/)
 *
 * @package     CrowdFusion
 */
class FeedParser implements FeedParserInterface
{

    protected $cacheDuration = null;
    protected $cacheDirectory = null;
    protected $timeout = null;
    protected $stripHtmlTags = null;


    /**
     * Default constructor.
     *
     * @param int    $feedParserCacheDuration A duration (in seconds) for the feeds contents to be cached.
     * @param string $vendorCacheDirectory    Directory where the cached feed contents are stored. Should be an absolute path to
     *                                          a directory on the filesystem.
     * @param int    $feedParserTimout        A duration (in seconds) for underlying curl timeout when downloading a feed
     * @param array  $feedParserStripHtmlTags An array of tag names (as strings) to pass to the feed parsing library to strip out
     */
    public function __construct($feedParserCacheDuration, $vendorCacheDirectory, $feedParserTimeout = 60, $feedParserStripHtmlTags = null)
    {
        $this->cacheDuration  = $feedParserCacheDuration;
        $this->cacheDirectory = $vendorCacheDirectory;
        $this->timeout = $feedParserTimeout;
        $this->stripHtmlTags = $feedParserStripHtmlTags;
    }

    /**
     * Opens an RSS feed, parses and loads the contents.
     *
     * @param string $url         The URL of the RSS feed
     * @param bool   $nativeOrder If true, disable order by date to preserve native ordering
     * @param bool   $force       Force SimplePie to parse the feed despite errors
     *
     * @return object An object that encapsulates the feed contents and operations on those contents.
     * @throws FeedParserException If opening or parsing feed fails
     **/
    public function parseFeed($url, $nativeOrder = false, $force = false)
    {
        require_once PATH_SYSTEM.'/vendors/SimplePie.php';

        $feed = new SimplePie();
        $feed->set_timeout($this->timeout);
        $feed->set_feed_url($url);
        $feed->enable_order_by_date(!$nativeOrder);
        $feed->force_feed($force);

        if($this->cacheDuration != null)
            $feed->set_cache_duration(intval($this->cacheDuration));

        if($this->cacheDirectory != null)
            $feed->set_cache_location($this->cacheDirectory);

        if($this->stripHtmlTags != null)
        	$feed->strip_htmltags($this->stripHtmlTags);

        @$feed->init();

        if($err = $feed->error())
            throw new FeedParserException($err);

        return $feed;
    }
}

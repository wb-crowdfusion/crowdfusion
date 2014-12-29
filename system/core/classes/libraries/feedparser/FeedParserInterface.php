<?php
/**
 * Interface for parsing and consuming RSS feeds.
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
 * @version     $Id: FeedParserInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Interface for parsing and consuming RSS feeds.
 *
 * @package     CrowdFusion
 */
interface FeedParserInterface
{

    /**
     * Opens an RSS feed, parses and loads the contents.
     *
     * @param string $url The URL of the RSS feed
     *
     * @return object An object that encapsulates the feed contents and operations on those contents.
     * @throws FeedParserException If opening or parsing feed fails
     **/
    public function parseFeed($url);

}
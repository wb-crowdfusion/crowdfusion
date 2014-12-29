<?php
/**
 * ZeroConfigParser
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
 * @version     $Id: ZeroConfigParser.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ZeroConfigParser
 *
 * @package     CrowdFusion
 */
class ZeroConfigParser implements ZeroConfigParserInterface
{

    public function parseTitle($rawContents)
    {
        $title = '';

        // Get the Title
        if(preg_match("/<title>([^<]+?)<\/title/im",$rawContents, $m)) {
            $title = trim($m[1]);
        }

        if ($title == '') {
            $title = "Unknown Title";
        }

        return $title;
    }
}
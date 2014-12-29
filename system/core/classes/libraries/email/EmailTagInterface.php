<?php
/**
 * Interface for Email Tagging
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
 * @version     $Id: EmailInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Interface for Email Tagging
 *
 * @package     CrowdFusion
 */
interface EmailTagInterface
{

    /**
     * For vendors that support 'tagging' (ie Postmark)
     * this allows for a tag to be added to later sort by
     *
     * @abstract
     * @param string $tag
     */
    public function tag($tag);
}
<?php
/**
 * Image utilities to get image dimensions
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
 * @version     $Id: ImageUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Image utilities to get image dimensions
 *
 * @package     CrowdFusion
 */
class ImageUtils
{
    /**
     * Returns an array like [width, height] in pixels for the file specified at {@link $path}
     *
     * @param string $path the absolute path to the image file.
     *
     * @return array An array like [width, height] for the dimensions in pixels for the image
     */
    public static function getImageDimensions($path)
    {

        list($width, $height) = @getimagesize($path);

        if (empty ($width) || empty ($height))
            return array(0, 0);

        return array(
            intval($width),
            intval($height));

    }

}
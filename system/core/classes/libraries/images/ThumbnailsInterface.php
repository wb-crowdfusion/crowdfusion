<?php
/**
 * Interface for Thumbnail creation. Provides support for cropping and resizing images.
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
 * @version     $Id: ThumbnailsInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Interface for Thumbnail creation. Provides support for cropping and resizing images.
 *
 * @package     CrowdFusion
 */
interface ThumbnailsInterface
{
    /**
     * Resizes or crops the specified filename according to the $format specified.
     *
     * {@link $format} can take the following forms: AAAxBBB, CCCh, DDDw, EEE
     *
     * AAAxBBB: A crop will be performed. The resulting thumbnail will
     *          be AAA pixels wide by BBB pixels high.
     *
     *          It is assumed the implementation will determine the
     *          gravity (location) of the crop, but we recommend "north" gravity by default.
     *
     * CCCh: A resize will be performed to make the height of the
     *      image to be no larger than CCC pixels, maintaining aspect ratio.
     *
     * DDDw: A resize will be performed to make the width of the
     *       image to be no larger than DDD pixels, maintaining aspect ratio.
     *
     * EEE: Resizes the image so the largest side is
     *      EEE pixels (wide or high). Maintains aspect ratio.
     *
     * @param string $filename       The absolute path to the source image
     * @param string $format         A string describing the type of image to create.
     * @param string $outputFilename (Optional) The name of the output filename to write to.
     *                               If not specified, this will be chosen automagically.
     *
     * @return The new filename of the thumbnail image (in the tmp directory)
     * @throws ThumbnailException
     **/
    public function createThumbnail($filename, $format, $outputFilename = null);

} // END interface ThumbnailsInterface
?>
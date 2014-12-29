<?php
/**
 * Thumbnails
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
 * @version     $Id: Thumbnails.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Generates a thumbnail image from a source image using
 * ImageMagick (through a PHP extension, or command execution), or GD (with php support)
 * Provides resizing and cropping.
 *
 * @package     CrowdFusion
 */
class Thumbnails implements ThumbnailsInterface
{
    protected $pathToImageMagickConvert = null;
    protected $supportedExtensions      = null;
    protected $cropGravity              = null;
    protected $autoNameSuffix           = null;
    protected $imageMagickCompatMode    = false;
    protected $convertMode              = null;
    protected $jpegQuality              = null;
    protected $pngQuality               = null;


    /**
     * Create the thumbnail object.
     *
     * @param string $thumbnailsSoftwareMode        Either 'pecl_imagick', 'exec_imagick' or 'gd'. Determines what software will do the resizes
     * @param array  $thumbnailsSupportedExtensions An array containing supported filetype extensions
     * @param string $thumbnailsCropGravity         A direction describing the "gravity" to use for cropping images
     * @param string $thumbnailsAutoNameSuffix      The suffix appended to the converted file
     */
    public function __construct($thumbnailsSoftwareMode = 'exec_imagick',
                                array $thumbnailsSupportedExtensions = array('jpg','gif','png','jpeg','bmp'),
                                $thumbnailsCropGravity = 'North',
                                $thumbnailsAutoNameSuffix = '-thumb')
    {

        if (in_array($thumbnailsSoftwareMode, array('gd', 'exec_imagick', 'pecl_imagick')))
            $this->convertMode = $thumbnailsSoftwareMode;
        else
            throw new ThumbnailsException('Software mode must be "gd", "pecl_imagick", or "exec_imagick". Given:' . print_r($thumbnailsSoftwareMode, true));

        if($this->convertMode == 'gd' && !function_exists('imagecreatefromJPEG'))
            throw new ThumbnailsException('Software mode "gd" unsupported on your installation');

        if($this->convertMode == 'pecl_imagick' && !class_exists('Imagick'))
            throw new ThumbnailsException('Software mode "pecl_imagick" unsupported on your installation');

        $this->supportedExtensions = $thumbnailsSupportedExtensions;

        $this->cropGravity = $thumbnailsCropGravity;

        $this->autoNameSuffix = $thumbnailsAutoNameSuffix;

    }

    /**
     * Sets the path to imagemagick's convert tool
     *
     * @param string $thumbnailsPathToImageMagickConvert The name of an Image Magick command-line tool
     *
     * @return void
     */
    public function setThumbnailsPathToImageMagickConvert($thumbnailsPathToImageMagickConvert)
    {
        $this->pathToImageMagickConvert = $thumbnailsPathToImageMagickConvert;
    }

    /**
     * Sets compatibility mode for image_magick below version 6.3.8-3.
     *
     * As of IM v6.3.8-3 the special resize option flag '^' was added
     * to make cutting the image to fit easier.
     *
     * Support for this has been programmed into the thumbnails lib,
     * enable compatibility mode to use it.
     *
     * @param boolean $thumbnailsImageMagickCompatMode If true, we will use compatibility mode to crop images with image_magick
     *
     * @return void
     */
    public function setThumbnailsImageMagickCompatMode($thumbnailsImageMagickCompatMode )
    {
        $this->imageMagickCompatMode = $thumbnailsImageMagickCompatMode;
    }

    /**
     * Set the quality for JPEG images
     *
     * @param integer $thumbnailsJpegQuality The quality for jpeg images
     *
     * @return void
     */
    public function setThumbnailsJpegQuality($thumbnailsJpegQuality)
    {
        $this->jpegQuality = $thumbnailsJpegQuality;
    }

    /**
     * Set the quality for PNG images
     *
     * @param integer $thumbnailsPngQuality The quality for png images
     *
     * @return void
     */
    public function setThumbnailsPngQuality($thumbnailsPngQuality)
    {
        $this->pngQuality = $thumbnailsPngQuality;
    }

    /**
     * Inject Logger object
     *
     * @param LoggerInterface $Logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

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
     * @param string $filename        The absolute path to the source image
     * @param string $format          A string describing the type of image to create.
     * @param string $outputFilename  (Optional) The name of the output filename to write to.
     *                                  If not specified, this will be chosen automagically.
     * @param string $outputDirectory (Optional) The name of the output directory that will hold our thumbnail
     *                                  If not specified, this will be chosen automagically.
     * @param string $fileFormat      (Optional) The format of the output file (jpg, png, &c.).
     *                                  If not specified, this will be determined based on the filename.
     *
     * @return The new filename of the thumbnail image (in the tmp directory)
     * @throws ThumbnailsException
     **/
    public function createThumbnail($filename, $format, $outputFilename = null, $outputDirectory = null, $fileFormat = null)
    {
        // determine file format (jpg, png...)
        switch (true) {
            case !empty($fileFormat):
                break;
            case !empty($outputFilename):
                $fileFormat = pathinfo($outputFilename, PATHINFO_EXTENSION);
                if (!empty($fileFormat)) {
                    break;
                }
                // FALLTHROUGH if extension was empty
            default:
                $fileFormat = pathinfo($filename, PATHINFO_EXTENSION);
        }

        if (empty($fileFormat)) {
            throw new Exception("No file format was given and it could not be inferred from [$filename]");
        }

        $fileFormat = strtolower($fileFormat);

        $quality = $this->getDesiredImageQuality($fileFormat, $format);

        $format = trim($format);
        $this->Logger->debug("Create thumbnail with {$this->convertMode} format [$format], file format [$fileFormat], quality [$quality]");

        if (is_numeric($format)) {
            $thumbFilename = $this->generateThumbnail($filename, $outputFilename, $outputDirectory, intval($format), intval($format), true, false, $fileFormat, $quality);
        } else if (substr($format, strlen($format)-1) === 'w') {

            $w = substr($format, 0, strlen($format)-1);
            $thumbFilename = $this->generateThumbnail($filename, $outputFilename, $outputDirectory, intval($w), 0, false, false, $fileFormat, $quality);
        } else if (substr($format, strlen($format)-1) === 'h') {

            $h = substr($format, 0, strlen($format)-1);
            $thumbFilename = $this->generateThumbnail($filename, $outputFilename, $outputDirectory, 0, intval($h), false, false, $fileFormat, $quality);
        } else if (strpos($format, 'x') !== false) {

            $p = explode('x', $format);
            $thumbFilename = $this->generateThumbnail($filename, $outputFilename, $outputDirectory, intval($p[0]), intval($p[1]), false, true, $fileFormat, $quality);
        } else {
            throw new ThumbnailsException("Unsupported thumbnail size '".$format."'");
        }

        return $thumbFilename;
    }

    /**
     * Figure out image quality.
     *
     * @param string $format
     *
     * @returns mixed - string/integer quality or null if no config value is set
     */
    protected function getDesiredImageQuality($ext, $format)
    {
        switch ($ext) {
            case 'jpeg':
            case 'jpg':
                $qualityConfig = $this->jpegQuality;
                break;

            case 'png':
                $qualityConfig = $this->pngQuality;
                break;

            default:
                $qualityConfig = null;
        }

        if (empty($qualityConfig)) {
            return null;
        }
        if (is_array($qualityConfig)) {
            if (isset($qualityConfig[$format])) {
                return $qualityConfig[$format];
            }
            if (isset($qualityConfig['default'])) {
                return $qualityConfig['default'];
            }
        }
        return $qualityConfig;
    }

    /*
     *
     */
    protected function getThumbnailEncoding($ext)
    {
        if ($ext == 'png') {
        }
    }

    /**
     * The filename must have an extension and it must be one of the following: jpg, gif, png, jpeg, or bmp.
     * If a thumbnail already exists in the source files' directory, this function automatically appends an
     * incremental numeric suffix.
     *
     * If the output filename already exists on disk, this function will revert to auto-creating a thumbnail filename.
     *
     * @param string  $filename        The filename to process
     * @param string  $outputFilename  The name of the file to write to
     * @param string  $outputDirectory The name of the directory that holds our outputfile
     * @param integer $width           The width in pixels of the created thumbnail
     * @param integer $height          The height in pixels of the created thumbnail
     * @param boolean $max             If set to true then the image aspect ratio is preserved
     *                                   with the max length of either width or height being {@link $width} or {@link $height}
     * @param boolean $crop            If set to true, the image will be cropped to the dimensions specified.
     *                                 If set to false, the image will be resized.
     *
     * @return string the name of the file where the thumbnail can be found
     * @throws ThumbnailsException If any of the parameters are invalid.
     *
     * @see generateThumbnail(...)
     */
    protected function generateThumbnail($filename, $outputFilename, $outputDirectory, $width = 0, $height = 0, $max = false, $crop = false, $ext = null, $quality = null)
    {

        if (!is_file($filename))
            throw new ThumbnailsException("Source file does not exist '".$filename."'");

        $path = pathinfo($filename);

        if (empty($path['extension']))
            throw new ThumbnailsException("Source file does not have an extension '".$filename."'");

        $originalExt = strtolower(trim($path['extension']));

        if (empty($ext)) {
            $ext = $originalExt;
        }

        if (!in_array($ext, $this->supportedExtensions))
            throw new ThumbnailsException("Thumbnail file extension [$ext] is not supported");

        if ($outputDirectory != null && !is_dir($outputDirectory))
            throw new ThumbnailsException("Output directory does not exist or is not a valid directory '{$outputDirectory}'");

        //parameter validation
        if ($width === 0 && $height === 0)
            throw new ThumbnailsException("Width and/or height must be specified");

        if (!is_int($width))
            throw new ThumbnailsException("Width [$width] is not a valid integer");

        if (!is_int($height))
            throw new ThumbnailsException("Height [$height] is not a valid integer");

        if ($width < 0)
            throw new ThumbnailsException("Width cannot be negative");

        if ($height < 0)
            throw new ThumbnailsException("Height cannot be negative");

        if ($max === true && ($width === 0 || $height === 0))
            throw new ThumbnailsException("If max is true then width and height must be positive");

        if ($crop === true && ($width === 0 || $height === 0 || $max === true))
            throw new ThumbnailsException("If crop is true then width and height must be positive and max must be false");

        if ($outputDirectory == null)
            $outputDirectory = $path['dirname'];
        else
            $outputDirectory = rtrim($outputDirectory, '/');

        //check for existing thumbnail in same directory, increment filename
        $outfile = $outputFilename == null ? "{$path['filename']}{$this->autoNameSuffix}.$ext" : $outputFilename;
        $inc = 1;
        while (is_file("{$outputDirectory}/{$outfile}")) {
            $outfile = "{$path['filename']}{$this->autoNameSuffix}-{$inc}.$ext";
            $inc++;
        }

        //list($origwidth, $origheight) = getimagesize($filename);

        //build ImageMagick operation
        if ($this->convertMode == 'exec_imagick') {
            if ($max === true)
                $op = "-resize {$width}x{$height}\\>";
            else if ($crop === true) {
                if ($this->imageMagickCompatMode == false) {
                    // As of IM v6.3.8-3 the special resize option flag '^' was added
                    // to make cutting the image to fit easier.
                    $op = "-resize {$width}x{$height}\\>^ -gravity {$this->cropGravity} -crop {$width}x{$height}+0+0 +repage";
                } else {
                    // Complex trickiness to perform the cut to fit resize

                    // Calculate the thumbnail aspect ratio.
                    // > 1 is a wide thumb
                    // < 1 is a tall thumb
                    // 1 is a square thumb
                    $thumb_aspect_ratio = $width / $height;

                    // Get the dimensions of the image
                    $dimensions = getimagesize($filename);

                    $image_aspect_ratio = $dimensions[0] / $dimensions[1];

                    // Definitions:
                    //  width-crop = Resize the image to the full width of the thumbnail and trim the top and bottom
                    //  height-crop = Resize the image to the full height of the thumbnail and trip the sides

                    // Behavior:
                    // If image_aspect_ratio < thumb_aspect_ratio perform a width-crop
                    // If image_aspect_ratio >= thumb_aspect_ratio perform a height-crop

                    if ($image_aspect_ratio < $thumb_aspect_ratio) {
                        $op = "-resize {$width}x\\> -gravity {$this->cropGravity} -crop {$width}x{$height}+0+0 +repage";
                    } else {
                        $op = "-resize x{$height}\\> -gravity {$this->cropGravity} -crop {$width}x{$height}+0+0 +repage";
                    }
                }
            } else if ($height === 0)
                $op = "-resize {$width}x\\>";
            else if ($width === 0)
                $op = "-resize x{$height}\\>";
            else
                $op = "-resize {$width}x{$height}!\\>";

            $qualityArg = $quality ? '-quality ' . escapeshellarg($quality)
                                   : $qualityArg = '';

            $outPath = escapeshellarg("{$outputDirectory}/{$outfile}");

            //full ImageMagick command; redirect STDERR to STDOUT
            $filename = escapeshellarg($filename);

            $cmd = "{$this->pathToImageMagickConvert} {$filename} {$op} {$qualityArg} {$outPath} 2>&1";

            $retval = 1;
            $output = array();

            $this->Logger->debug("Excecuting [$cmd]");
            exec($cmd, $output, $retval);

            if ($retval > 0) {
                throw new ThumbnailsException("Generation failed '".$cmd."\n".implode("\n", $output)."'");
            }
        } elseif ($this->convertMode == 'pecl_imagick') {
            $image = new Imagick($filename);

            if ($max == true) {
                $image->scaleImage($width, $height, true);
            } elseif ($crop === true) {
                // Because Imagick::cropThumbnailImage() doesn't support different gravities,
                // we need to expand the functionality out here. PITA!

                if ($this->cropGravity == 'center')
                    $image->cropThumbnailImage($width, $height);
                else {
                    // Resize full image by default so the smallest edge is
                    // the max width/height
                    if ($image->getImageWidth() > $image->getImageHeight())
                        $image->scaleImage(0, $height);
                    else
                        $image->scaleImage($width, 0);

                    // Then crop out the needed section.
                    $image_width  = $image->getImageWidth();
                    $image_height = $image->getImageHeight();

                    switch ( strtolower($this->cropGravity) ) {
                    case 'northwest':
                        $x = $image_width - $width;
                        $y = 0;
                        break;

                    case 'north':
                        $x = ($image_width / 2) - ($width / 2);
                        $y = 0;
                        break;

                    case 'northeast':
                        $x = 0;
                        $y = 0;
                        break;

                    case 'west':
                        $x = 0;
                        $y = ($image_height / 2) - ($height / 2);
                        break;

                    case 'east':
                        $x = $image_width - $width;
                        $y = ($image_height / 2) - ($height / 2);
                        break;

                    case 'southwest':
                        $x = 0;
                        $y = $image_height - $height;
                        break;

                    case 'south':
                        $x = ($image_width / 2) - ($width / 2);
                        $y = $image_height - $height;
                        break;

                    case 'southeast':
                        $x = $image_width - $width;
                        $y = $image_height - $height;
                        break;

                    default:
                        throw new ThumbnailsException("Unsupported crop gravity: {$this->cropGravity}");
                    }

                    $x = floor($x);
                    $y = floor($y);

                    $image->cropImage($width, $height, $x, $y);
                }
            } elseif ($height === 0) {
                $image->scaleImage($width, $height);
            } elseif ($width === 0) {
                $image->scaleImage($width, $height);
            } else {
                $image->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
            }

            if ($quality) {
                $image->setImageCompressionQuality($quality);
            }

            // Imagick will infer the file format from the filename extension
            $image->writeImage("{$outputDirectory}/{$outfile}");
            $image->clear();
            $image->destroy();

        } elseif ($this->convertMode == 'gd') {
            $origImage = null;
            switch ( $originalExt ) {
            case 'jpg':
            case 'jpeg':
                $origImage = imagecreatefromJPEG($filename);
                break;

            case 'gif':
                $origImage = imagecreatefromGIF($filename);
                break;

            case 'png':
                $origImage = imagecreatefromPNG($filename);
                break;

            case 'bmp':
                $origImage = imagecreatefromWBMP($filename);
                break;

            default:
                throw new ThumbnailsException('GD does not know how to handle .' . $originalExt . ' files.');
            }

            if (function_exists('imageantialias'))
                imageantialias($origImage, true);

            $image_attr   = getimagesize($filename);
            $image_width  = $image_attr[0];
            $image_height = $image_attr[1];

            $dst_x = 0;
            $dst_y = 0;
            $src_x = null;
            $src_y = null;
            $dst_w = null;
            $dst_h = null;
            $src_w = null;
            $src_h = null;

            if ($max === true) {
                // resize to dimensions, preserving aspect ratio

                $src_x = 0;
                $src_y = 0;
                $src_w = $image_width;
                $src_h = $image_height;

                if ($image_width > $image_height) {
                    $dst_w = $width;
                    $dst_h = (int)floor($image_height * ($width / $image_width));
                } else {
                    $dst_h = $height;
                    $dst_w = (int)floor($image_width * ($height / $image_height));
                }

            } else if ($crop === true) {
                // crop the image with cropGravity

                $dst_w = $width;
                $dst_h = $height;

                // By default, resize the whole image
                $src_w = $image_attr[0];
                $src_h = $image_attr[1];

                $thumb_aspect_ratio = $width / $height;
                $image_aspect_ratio = $image_attr[0] / $image_attr[1];

                if ($image_aspect_ratio < $thumb_aspect_ratio) {
                    // width-crop
                    $src_w        = $image_attr[0]; // original-width
                    $resize_ratio = $image_attr[0] / $width; // original-width / thumbnail-width
                    $src_h        = floor($height * $resize_ratio); // thumbnail-height * original-width / thumbnail-width
                } else {
                    // height-crop
                    $src_h        = $image_attr[1]; // original-height
                    $resize_ratio = $image_attr[1] / $height; // original-height / thumbnail-height
                    $src_w        = floor($width * $resize_ratio); // thumbnail-width * original-height / thumbnail-height
                }

                $dst_x = 0;
                $dst_y = 0;
                $dst_w = $width;
                $dst_h = $height;

                switch ( strtolower($this->cropGravity) ) {
                case 'center':
                    $src_x = floor(($image_attr[0]-$src_w)/2);
                    $src_y = floor(($image_attr[1]-$src_h)/2);
                    break;
                case 'northeast':
                    $src_x = 0;
                    $src_y = 0;
                    break;
                case 'north':
                    $src_x = floor(($image_attr[0]-$src_w)/2);
                    $src_y = 0;
                    break;
                case 'south':
                    $src_x = floor($image_attr[0]-$image_width);
                    $src_y = floor($image_attr[1]-$image_height);
                    break;

                default:
                    throw new ThumbnailsException("Unsupported cropGravity for GD: {$this->cropGravity}");
                }
            } else if ($height === 0) {
                // resize to max width, preserving aspect ratio
                $src_x = 0;
                $src_y = 0;
                $src_w = $image_width;
                $src_h = $image_height;

                $dst_w = $width;
                $dst_h = $image_height * ($width / $image_width);



            } else if ($width === 0) {
                // resize to max height, preserving aspect ratio

                $src_x = 0;
                $src_y = 0;
                $src_w = $image_width;
                $src_h = $image_height;

                $dst_h = $height;
                $dst_w = $image_width * ($height / $image_height);

            } else {
                // resize, ignoring aspect ratio
                $src_x = 0;
                $src_y = 0;
                $src_w = $image_width;
                $src_h = $image_height;

                $dst_w = $width;
                $dst_h = $height;
            }

            $newImage  = imagecreateTrueColor($dst_w, $dst_h);

            //preserve transparency
            $transindex = -1;
            if($ext == 'gif') {
                imagealphablending($newImage, false);
                $transindex = imagecolortransparent($origImage);
                if($transindex >= 0) {
                    $transcol = imagecolorsforindex($origImage, $transindex);
                    $transindex = imagecolorallocatealpha($newImage, $transcol['red'], $transcol['green'], $transcol['blue'], 127);
                    imagefill($newImage, 0, 0, $transindex);
                }
            }

            @imagecopyResampled($newImage, $origImage, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

            //preserve transparency
            if($ext == 'gif') {
                if($transindex >= 0) {
                    imagecolortransparent($newImage, $transindex);
                    for($y=0; $y<$dst_h; ++$y)
                        for($x=0; $x<$dst_w; ++$x)
                            if(((imagecolorat($newImage, $x, $y)>>24) & 0x7F) >= 100)
                                imagesetpixel($newImage, $x, $y, $transindex);

                    imagetruecolortopalette($newImage, true, 255);
                    imagesavealpha($newImage, false);
                }
            }

            // echo "<pre>"; var_dump($dst_h); die("</pre>");

            $outfilepath = "{$outputDirectory}/{$outfile}";

            switch ( $ext ) {
            case 'jpg':
            case 'jpeg':
                imageJPEG($newImage, $outfilepath, $quality);
                break;

            case 'gif':
                imageGIF($newImage, $outfilepath);
                break;

            case 'png':
                imagePNG($newImage, $outfilepath, $quality);
                break;

            case 'bmp':
                imageWBMP($newImage, $outfilepath);
                break;

            }

            imagedestroy($newImage);
            imagedestroy($origImage);
        }

        return $outfile;
    }
}

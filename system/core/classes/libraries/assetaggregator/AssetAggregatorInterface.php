<?php
/**
 * AssetAggregatorInterface
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
 * @version     $Id: AssetAggregatorInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AssetAggregatorInterface
 *
 * @package     CrowdFusion
 */
interface AssetAggregatorInterface
{

    /**
     * Combines, minifies, and returns HTML <style> tags for CSS files
     *
     * @param array   $assets  An array of relative paths to CSS files (used as a key to a storage facility)
     * @param boolean $combine Whether or not to combine the files into 1 file
     * @param boolean $minify  Whether or not to minify the CSS files
     * @param string  $media   <style> media attribute, ex. "screen,print"
     * @param string  $iecond  If present, wraps the <style> in an IE conditional
     * 	statement, ex. "IE", "IE 7", "lt IE 7", see {@link http://msdn.microsoft.com/en-us/library/ms537512(VS.85).aspx About Conditional Comments}
     *
     * @return string String of HTML CSS <style> tags
     */
    public function renderCSS(array $assets, $combine = false, $minify = false, $media = 'screen', $iecond = '');

    /**
     * Combines, packs or minifies, and returns HTML <script> tags for
     * JavaScript files
     *
     * @param array   $assets   An array of relative paths to JS files (used as a key to a storage facility)
     * @param boolean $combine  Whether or not to combine the files into 1 file
     * @param boolean $packMode Available options are: pack, min, none.  "pack"
     *  will utilize Dean Edwards' JavaScript packer.  "min" will utilize
     * Douglas Crockford's JSMin tool.
     * @param string  $iecond   If present, wraps the <script> in an IE conditional
     * 	statement, ex. "IE", "IE 7", "lt IE 7", see {@link http://msdn.microsoft.com/en-us/library/ms537512(VS.85).aspx About Conditional Comments}
     *
     * @return string String of HTML JS <script> tags
     */
    public function renderJS(array $assets, $combine = false, $packMode = 'none', $iecond = '');

    /**
     * Given a relative filename, returns a unique URL for the file based on
     * its timestamp.  For example, given "/path/to/flash.swf/", returns
     * "http://assets.example.com/art/cache/flash.v2009_05_18_151330.swf"
     * Filname is used as a key to a storage facility.
     *
     * @param string $filename Absolute path to asset file
     *
     * @return string Absolute URL for versioned file
     */
    public function renderVersionedFile($filename);

}
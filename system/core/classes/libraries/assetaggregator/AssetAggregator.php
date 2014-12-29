<?php
/**
 * AssetAggregator
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
 * @version     $Id: AssetAggregator.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AssetAggregator
 *
 * @package     CrowdFusion
 */
class AssetAggregator implements AssetAggregatorInterface
{
    const DATE_FORMAT = 'Y_m_d_His';

    protected $AssetService;
    protected $RequestContext;
    protected $Request;
    protected $DateFactory;

    protected $cache;
    protected $cacheBasePath = null;

    protected $vendorCacheDirectory;

    /**
     * Creates an AssetAggregator
     *
     * @param AssetService        $AssetService                        The AssetService is used to load and store asset files.
     * @param RequestContext      $RequestContext                      The request context; used to get a fully
     *                                                                   configured site object (used for SF operations).
     * @param Request             $Request                             The request; store all input params,
     *                                                                   server params, cookies, etc. All HTTP
     *                                                                   request data is stored in this.
     * @param CacheStoreInterface $TemplateCacheStore                  Caching object, used to cache results
     *                                                                   of an expensive aggregation operation.
     * @param VersionService      $VersionService                      Service for accessing the system version.
     * @param DateFactory         $DateFactory                         Date factory to generate dates.
     * @param boolean             $assetAggregatorCombine              If true, then assets will be combined into a single file. Default: true
     * @param boolean             $assetAggregatorCompress             If true, then assets will be compressed. Default: true
     * @param string              $assetAggregatorCacheBasePath        The base directory (relative to url-root) where
     *                                                                   cache documents are stored
     * @param string              $assetAggregatorAssetBasePath        The base directory (relative to url-root) where
     *                                                                      the asset documents are stored.
     */
     public function __construct(AssetService $AssetService,
                                RequestContext $RequestContext,
                                Request $Request,
                                TemplateCacheInterface $TemplateCache,
                                DateFactory $DateFactory,
                                $assetAggregatorCacheBasePath,
                                $vendorCacheDirectory)
    {
        $this->AssetService    = $AssetService;
        $this->RequestContext  = $RequestContext;
        $this->Request         = $Request;
        $this->TemplateCache           = $TemplateCache;
        $this->DateFactory     = $DateFactory;
        $this->cacheBasePath   = $assetAggregatorCacheBasePath;
        $this->vendorCacheDirectory = $vendorCacheDirectory;
    }

    /**
     * Combines, minifies, and returns HTML <style> tags for CSS files
     *
     * @param array   $assets  An array of relative paths to CSS files
     * @param boolean $combine Whether or not to combine the files into 1 file, caching only works if this is true
     * @param boolean $minify  Whether or not to minify the CSS files
     * @param string  $media   <style> media attribute, ex. "screen,print"
     * @param string  $iecond  If present, wraps the <style> in an IE conditional
     *                          statement, ex. "IE", "IE 7", "lt IE 7", see
     *                          {@link http://msdn.microsoft.com/en-us/library/ms537512(VS.85).aspx About Conditional Comments}
     *
     * @return string String of HTML CSS <style> tags
     */
    public function renderCSS(array $assets, $combine = false, $minify = false, $media = 'screen', $iecond = '')
    {

        if(empty($assets))
            return "";

        $cacheKey = $this->buildCacheKey($assets, array($media, $iecond));

        if($html = $this->TemplateCache->get($cacheKey))
            return $html;

        $optionsKey = "media={$media}|iecond={$iecond}|";

        $html = '';
        foreach ($assets as $asset) {
            $aggregateFileUrl = $this->processAsset($asset, $optionsKey, 'css');
            $html .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"{$media}\" href=\"{$aggregateFileUrl}\" />\n";
        }

        if($iecond != '')
            $html = "<!--[if {$iecond}]>{$html}<![endif]-->";

        $this->TemplateCache->put($cacheKey, $html, 0);

        return $html;
    }

    /**
     * Combines, packs or minifies, and returns HTML <script> tags for
     * JavaScript files
     *
     * @param array   $assets   An array of relative paths to JS files
     * @param boolean $combine  Whether or not to combine the files into 1 file
     * @param boolean $packMode Available options are: pack, min, none.  "pack"
     *  will utilize Dean Edwards' JavaScript packer.  "min" will utilize
     * Douglas Crockford's JSMin tool.
     * @param string  $iecond   If present, wraps the <script> in an IE conditional
     *  statement, ex. "IE", "IE 7", "lt IE 7", see {@link http://msdn.microsoft.com/en-us/library/ms537512(VS.85).aspx About Conditional Comments}
     *
     * @return string String of HTML JS <script> tags
     */
    public function renderJS(array $assets, $combine = false, $packMode = 'none', $iecond = '')
    {
        if(empty($assets))
            return "";

        $cacheKey = $this->buildCacheKey($assets, array($iecond));

        if($html = $this->TemplateCache->get($cacheKey))
            return $html;

        $optionsKey = "iecond={$iecond}|";

        $html = '';
        foreach ($assets as $asset) {
            $aggregateFileUrl = $this->processAsset($asset, $optionsKey, 'js');
            $html .= "<script language=\"JavaScript\" type=\"text/javascript\" src=\"{$aggregateFileUrl}\"></script>\n";
        }

        if($iecond != '')
            $html = "<!--[if {$iecond}]>{$html}<![endif]-->";
        $this->TemplateCache->put($cacheKey, $html, 0);

        return $html;
    }

    /**
     * Given a relative filename, returns a unique URL for the file based on
     * its timestamp.  For example, given "/path/to/flash.swf/", returns
     * "http://assets.example.com/art/cache/flash.v2009_05_18_151330.swf"
     *
     * @param string $filename Absolute path to asset file
     *
     * @return string Absolute URL for versioned file
     */
    public function renderVersionedFile($filename)
    {
        if(empty($filename))
            return "";

        $cacheKey = $this->buildCacheKey(array($filename), array('version'));

        if($html = $this->TemplateCache->get($cacheKey))
            return $html;

        $ts = $this->AssetService->getOriginTimestamp($filename);
        $deployedfile = $this->AssetService->getOriginPath($filename);

        $pathParts = pathinfo($filename);

        $relPath = $pathParts['dirname'];

        if($relPath == '/')
            $relPath = '';

        $cacheFileId = '/'.$this->cacheBasePath.$relPath.'/'.$pathParts['filename'].".v".date(AssetAggregator::DATE_FORMAT, $ts).".".$pathParts['extension'];

        $versionedFile = $this->AssetService->putFile($cacheFileId, $deployedfile, $ts);

        $url = $versionedFile->getURL();

        $this->TemplateCache->put($cacheKey, $url, 0);

        return $url;
    }

    public function renderResolvedFile($filename)
    {
        if(empty($filename))
            return "";

        $file = $this->AssetService->resolveFile($filename);

        if(empty($file))
            return "";

        return $file->getURL();
    }

    /**
     * Processes an array (or single) of assets.
     *
     * @param array    $assets           Array of asset file names, or a single asset file name
     * @param string   $optionsKey       String representation of the options; used to create aggregated file name
     * @param string   $extension        File extension, 'js' or 'css'
     * @param callback $compressCallback Callback to execute to compress the contents of the asset.  If null, the
     *                                   contents won't be compressed.
     *
     * @return StorageFacilityFile Aggregated file stored in the aggregate storage facility (with url populated)
     */
    protected function processAsset($asset, $optionsKey, $extension)
    {
        $ts = $this->AssetService->getOriginTimestamp($asset);

        $prefix = '';

        $p = basename($asset);
        $prefix = rtrim($p, '.'.$extension).'.';

        $cacheFileId = '/'.$this->cacheBasePath.'/'.$prefix.md5(serialize(array(
                $asset,
                $optionsKey,
                $ts))).
            ".v".$this->DateFactory->newStorageDate($ts)->format(AssetAggregator::DATE_FORMAT).".".$extension;

        if (!$this->AssetService->fileExists($cacheFileId)) {

            $deployedfile = $this->AssetService->getOriginPath($asset);

            $contents = @file_get_contents($deployedfile);
            if($contents === false)
                throw new Exception("Reading contents of file '{$deployedfile}' for asset '{$asset}' failed (empty file contents will cause failure).");

            // strip non-ASCII characters
            $contents = preg_replace('/[^(\x20-\x7F)\x0A]*/','', $contents);

            $tmpFile = $this->vendorCacheDirectory.'/assetCache'.$cacheFileId;

            if(!is_writable($this->vendorCacheDirectory))
                throw new Exception('Vendor cache directory is not writable: '.$this->vendorCacheDirectory);

            FileSystemUtils::safeFilePutContents($tmpFile, $contents);

            $this->AssetService->putFile($cacheFileId, $tmpFile, $ts);
        }

        $aggregateFile = $this->AssetService->resolveFile($cacheFileId);

        return $aggregateFile->getURL();
    }

    /**
     * Builds a unique cache key based on the asset array and options
     *
     * @param array $assets  Array of asset file names
     * @param array $options Array of scalar option values
     *
     * @return string Unique cache key
     */
    protected function buildCacheKey(array $assets, array $options)
    {
        $cachekey = $this->Request->getServerName();
        $cachekey .= "(asset(".implode('|', $assets).")(".implode('|', $options)."))";

        return $cachekey;
    }

}

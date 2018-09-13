<?php
/**
 * Template cache stores and retrieves templates from one or more cache stores
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
 * @version     $Id: TemplateCache.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Template cache stores and retrieves templates from one or more cache stores
 *
 * @package     CrowdFusion
 */
class TemplateCache extends AbstractCache implements TemplateCacheInterface
{

    protected $defaultCacheTime;

    protected $VersionService;
    protected $Request;
    protected $Logger;

    protected $context;
    protected $siteDomain;
    protected $deviceView = 'main';
    protected $design     = 'default';
    protected $viewerCountry = '';


    /**
     * Sets up our Template Cache.
     *
     * @param array  $cacheStores            An array of CacheStoreInterface objects used to store the templates
     * @param string $templateCacheKeyPrefix The cacheKey to use. Default: 'tc'
     * @param int    $defaultCacheTime       The default cache time, in seconds. Default: 300
     */
    public function __construct(CacheStoreInterface $PrimaryCacheStore, $templateCacheKeyPrefix = 'tc', $defaultCacheTime = 300)
    {
        parent::__construct($PrimaryCacheStore, $templateCacheKeyPrefix);

        $this->defaultCacheTime = $defaultCacheTime;

    }

    /**
     * Injects the VersionService
     *
     * @param VersionService $VersionService The VersionService to inject
     *
     * @return void
     */
    public function setVersionService($VersionService)
    {
        $this->VersionService = $VersionService;
    }

    /**
     * Injects the Request
     *
     * @param Request $Request the Request
     *
     * @return void
     */
    public function setRequest($Request)
    {
        $this->Request = $Request;
    }

    /**
     * [IoC] Sets the current context, from config.
     *
     * @param string $context The current context
     *
     * @return void
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * [IoC] Sets the siteDomain
     *
     * @param string $siteDomain The siteDomain
     *
     * @return void
     */
    public function setSiteDomain($siteDomain)
    {
        $this->siteDomain = $siteDomain;
    }

    /**
     * [IoC] Sets the design name from config
     *
     * @param string $design The design name
     *
     * @return void
     */
    public function setDesign($design)
    {
        $this->design = $design;
    }

    /**
     * [IoC] Sets the device view from config
     *
     * @param string $deviceView The device view
     *
     * @return void
     */
    public function setDeviceView($deviceView)
    {
        $this->deviceView = $deviceView;
    }

    /**
     * @param string $viewerCountry
     *
     * @return void
     */
    public function setViewerCountry($viewerCountry)
    {
        $this->viewerCountry = $viewerCountry;
    }

    public function setRouterBase($routerBase)
    {
        $this->routerBase = $routerBase;
    }

    /**
     * Injects the Logger
     *
     * @param LoggerInterface $Logger The logger
     *
     * @return void
     */
    public function setLogger($Logger)
    {
        $this->Logger = $Logger;
    }

    protected function cacheKey($key)
    {
        $https = isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
        return "{$this->keyPrefix}-{$this->VersionService->getSystemVersion()}-{$this->VersionService->getDeploymentRevision()}-{$this->context}-{$this->siteDomain}-{$this->routerBase}-{$this->deviceView}-{$this->design}-{$this->viewerCountry}-{$https}-{$key}";
    }

    /**
     * Stores the specified template to cache using the cacheKey specified.
     *
     * @param string   $cacheKey The cacheKey for the Template
     * @param Template $template The template to store in the cache
     *
     * @return true
     */
    public function putTemplate($cacheKey, Template $template)
    {
        if ($template->getCacheTime() == null && !is_numeric($template->getCacheTime()))
            $template->setCacheTime($this->defaultCacheTime);

        $this->put($cacheKey, $template, (int)$template->getCacheTime());

        return true;
    }

    /**
     * Returns a cacheKey for the specified Template
     *
     * @param Template $template The template to generate the cacheKey for
     * @param array    $globals  An array of globals set on this template
     *
     * @return string The cache key
     */
    public function getTemplateCacheKey(Template $template, $globals)
    {
        $locals = $template->getLocals();

        $cachekey = '';

        if($template->isTopTemplate())
            $cachekey .= $this->Request->getAdjustedRequestURI();
        else
            $cachekey .= $template->getContentType().$template->getName();

        $cacheParams = array();

        // any locals that aren't globals
        foreach ($locals as $name => $value) {
            if (!is_array($value) && !array_key_exists($name, $globals) &&
             !preg_match("/^[A-Z\-\_0-9]+$/", $name) &&
             !preg_match("/\-\d+$/", $name)) {
                $cacheParams[$name] = $value;
            }
        }

        if(isset($cacheParams['UseQueryStringInCacheKey']) && StringUtils::strToBool($cacheParams['UseQueryStringInCacheKey']) == true)
        {
            foreach ($_GET as $name => $value) {
                if (!is_array($value))
                    $cacheParams['q_'.$name] = $value;
            }
        }

        $cachekey .= '?';
        if (!empty($cacheParams)) {
            ksort($cacheParams);
            foreach ($cacheParams as $n => $v) {
                $cachekey .= $n.'='.substr($v,0,255).'&';
            }
        }

        $cachekey = rtrim($cachekey, '&');
        $this->Logger->debug('Cache Key ['.$cachekey.']');

        return $cachekey;
    }
}

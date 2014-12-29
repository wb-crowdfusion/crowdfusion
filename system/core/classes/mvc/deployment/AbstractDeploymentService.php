<?php
/**
 * AbstractDeploymentService
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
 * @version     $Id: AbstractDeploymentService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractDeploymentService
 *
 * @package     CrowdFusion
 */
abstract class AbstractDeploymentService
{
    protected $ApplicationContext;
    protected $isDevelopmentMode = false;
    protected $isOneOffRedeploy  = false;
    protected $deployPath        = null;
    protected $context           = null;
    protected $isSiteDeployment  = true;
    protected $siteDomain        = null;
    protected $deviceView        = 'main';
    protected $design            = 'default';
    protected $subject           = null; //must override

    // protected $VersionService;
    protected $Logger;
    protected $viewDirectory;
    protected $RequestContext;
    protected $Request;
    protected $Session;
    protected $rootPath = null;

    /**
     * [IoC] Injected RequestContext
     *
     * @param RequestContext $RequestContext RequestContext
     *
     * @return void
     */
    public function setRequestContext(RequestContext $RequestContext)
    {
        $this->RequestContext = $RequestContext;
    }

    public function setRequest(Request $Request)
    {
        $this->Request = $Request;
    }

    public function setSession(Session $Session)
    {
        $this->Session = $Session;
    }

    /**
     * [IoC] Injected ApplicationContext
     *
     * @param ApplicationContext $ApplicationContext ApplicationContext
     *
     * @return void
     */
    public function setApplicationContext($ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    /**
     * Determines if we're in development mode. Can be injected (IoC) or set by hand
     *
     * @param boolean $isDevelopmentMode true if we're in development mode
     *
     * @return void
     */
    public function setDevelopmentMode($isDevelopmentMode)
    {
        $this->isDevelopmentMode = $isDevelopmentMode;
    }

    /**
     * Determines if we're in one-off redeploy situation. Can be injected (IoC) or set by hand
     *
     * @param boolean $isOneOffRedeploy true if we need to deploy for this request
     *
     * @return void
     */
    public function setOneOffRedeploy($isOneOffRedeploy)
    {
        $this->isOneOffRedeploy = $isOneOffRedeploy;
    }

    public function setRootPath($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * [IoC] Sets the deploy path, from config
     *
     * @param string $deployPath Sets the deploy path
     *
     * @return void
     */
    public function setDeployPath($deployPath)
    {
        $this->deployPath = $deployPath;
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
     * [IoC] Sets isSiteDeployment
     *
     * @param boolean $isSiteDeployment isSiteDeployment
     *
     * @return void
     */
    public function setIsSiteDeployment($isSiteDeployment)
    {
        $this->isSiteDeployment = $isSiteDeployment;
    }

    /**
     * [IoC] Sets the theme directory
     *
     * @param string $viewDirectory The theme directory
     *
     * @return void
     */
    public function setViewDirectory($viewDirectory)
    {
        $this->viewDirectory = $viewDirectory;
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
     * [IoC] Injects the logger
     *
     * @param LoggerInterface $Logger The logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

    /**
     * [IoC] Injects the VersionService
     *
     * @param VersionService $VersionService VersionService
     *
     * @return void
     */
//    public function setVersionService($VersionService)
//    {
//        $this->VersionService = $VersionService;
//    }

    /**
     * Returns the name of the base deploy directory
     *
     * @return string
     */
    protected function getBaseDeployDirectory()
    {
//        if (!$this->isSiteDeployment)
//            $basedir = rtrim($this->deployPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$this->context.DIRECTORY_SEPARATOR;
//        else
//            $basedir = rtrim($this->deployPath, DIRECTORY_SEPARATOR) .DIRECTORY_SEPARATOR  .
//                       (empty($this->siteDomain) ? '' : $this->siteDomain.DIRECTORY_SEPARATOR) .
//                       (empty($this->design)   ? '' : $this->design  .DIRECTORY_SEPARATOR);
//            $basedir = rtrim($this->deployPath, DIRECTORY_SEPARATOR) .DIRECTORY_SEPARATOR  .
//                        $this->Request->getServerName().DIRECTORY_SEPARATOR.
//                        (empty($this->design)   ? '' : $this->design  .DIRECTORY_SEPARATOR);

        return $this->deployPath;
    }

    /**
     * Returns an array of paths that contain assets and/or templates
     *
     * @param string $dir     The base directory to search under
     * @param string $pattern The glob pattern for files (appended to each path returned)
     *
     * @return array
     */
    protected function getPathsToCheck($dir, $pattern)
    {
        $base = ($dir == $this->viewDirectory)?$dir.'/':$dir."/view/";

        $paths = array();

        // allow plugins to provide universal assets, templates, etc
        $paths[] = "{$base}shared/{$pattern}";

        if ($this->isSiteDeployment) {
            // this is a legacy location (default doesn't make sense here)
            $paths[] = "{$base}{$this->context}/default/{$pattern}";

            // shared files by context
            $paths[] = "{$base}{$this->context}/shared/{$pattern}";

            // start: todo: remove in version 3.0.1
            // this is a legacy location (default doesn't make sense here)
            // todo: remove "default" locations unless in design switch
            if ($this->design != 'default')
                $paths[] = "{$base}{$this->context}/{$this->siteDomain}/default/{$pattern}";

            $paths[] = "{$base}{$this->context}/{$this->siteDomain}/{$this->design}/{$pattern}";
            // end: remove in version 3.0.1

            // routes and redirects cannot be aggregated like other files
            // so that is handled by the AbstractPHPAggregatorService
            // after all other paths have been added first
            if ('routes' === $this->subject || 'redirects' === $this->subject) {
                return $paths;
            }

            // shared files by context and domain
            $paths[] = "{$base}{$this->context}/{$this->siteDomain}/shared/{$pattern}";

            // always include default design files
            if ($this->design != 'default')
                $paths[] = "{$base}{$this->context}/{$this->siteDomain}/{$this->deviceView}/default/{$pattern}";

            // build theme search patterns based on context, site name, device view and design
            $paths[] = "{$base}{$this->context}/{$this->siteDomain}/{$this->deviceView}/{$this->design}/{$pattern}";
        } else {
            $paths[] = "{$base}{$this->context}/{$pattern}";
        }

        return $paths;
    }
}

<?php
/**
 * PluginsCliController
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
 * @version     $Id: PluginsCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PluginsCliController
 *
 * @package     CrowdFusion
 */
class PluginsCliController extends AbstractCmsController
{

    protected $PluginService = null;
    protected $AspectService = null;
    protected $ApplicationContext = null;
    protected $PluginInstallationService = null;
    protected $rootPath;

    public function setAspectService(AspectService $AspectService)
    {
        $this->AspectService = $AspectService;
    }

    public function setRootPath($rootPath)
    {
        $this->rootPath = rtrim($rootPath,'/').'/';
    }

    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    public function setPluginService(PluginService $PluginService)
    {
        $this->PluginService = $PluginService;
    }


    public function setPluginInstallationService(PluginInstallationService $PluginInstallationService)
    {
        $this->PluginInstallationService = $PluginInstallationService;
    }

    protected function install()
    {
        $obj = $this->PluginService->getBySlug($this->Request->getParameter('slug'));

        list($log, $status) = $this->PluginInstallationService->installPlugin($obj->Path, $this->errors);

        echo "STATUS: ".$status."\n\n";
        echo $log;
    }


    protected function installScript()
    {
        $obj = $this->PluginService->getBySlug($this->Request->getParameter('slug'));

        $log = "\n";
        $this->PluginInstallationService->processInstallScript($obj, $log);

        echo $log;
    }

    protected function upgrade()
    {
        $obj = $this->PluginService->getBySlug($this->Request->getParameter('slug'));

        list($log,$status) = $this->PluginInstallationService->upgradePlugin($obj->Slug,$obj->Path, $this->errors);

        echo "STATUS: ".$status."\n\n";
        echo $log;
    }

    protected function uninstall()
    {
        $obj = $this->PluginService->getBySlug($this->Request->getParameter('slug'));

        list($log,$status) = $this->PluginInstallationService->uninstallPlugin($obj->Slug, $this->errors);

        echo "STATUS: ".$status."\n\n";
        echo $log;
    }


    protected function uninstallPurge()
    {
        $obj = $this->PluginService->getBySlug($this->Request->getParameter('slug'));

        list($log,$status) = $this->PluginInstallationService->uninstallPlugin($obj->Slug, $this->errors, true);

        echo "STATUS: ".$status."\n\n";
        echo $log;
    }

}

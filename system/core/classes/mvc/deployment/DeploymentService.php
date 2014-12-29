<?php
/**
 * DeploymentService
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
 * @version     $Id: DeploymentService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * DeploymentService
 *
 * @package     CrowdFusion
 */
class DeploymentService
{

    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    public function setDeployPath($deployPath)
    {
        $this->deployPath = $deployPath;
    }

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    public function redeploy()
    {

        $this->Events->bindEvent('shutdown', $this, '_doRedeploy');

    }

    public function _doRedeploy()
    {
        // delete the deploy directory
        // FileSystemUtils::recursiveRmdir($this->deployPath, false);

        $this->ApplicationContext->object('PluginInstallationService')->scanInstall();

        // DEPLOY ASSETS
        $this->ApplicationContext->object('AssetService')->deploy();

        //DEPLOY TEMPLATES
        $this->ApplicationContext->object('TemplateService')->deploy();

        //DEPLOY MESSAGES
        $this->ApplicationContext->object('MessageService')->deploy();


    }

}
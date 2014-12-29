<?php
/**
 * HotdeployCmsController
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
 * @version     $Id: HotdeployCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * HotdeployCmsController
 *
 * @package     CrowdFusion
 */
class HotdeployCmsController extends AbstractCmsController
{
    protected $ApplicationContext;
    protected $VersionService = null;
    protected $isDevelopmentMode = false;

    public function setDevelopmentMode($isDevelopmentMode)
    {
        $this->isDevelopmentMode = $isDevelopmentMode;
    }

    public function setVersionService(VersionService $VersionService)
    {
        $this->VersionService = $VersionService;
    }

    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    public function refresh()
    {
        $this->checkPermission('hotdeploy-toggle');

        $this->ApplicationContext->clearContextFiles();
        $this->VersionService->incrementDeploymentRevision('Refreshed context via hotdeploy AJAX.');
    }
}

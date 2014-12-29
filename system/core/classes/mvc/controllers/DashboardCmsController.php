<?php
/**
 * DashboardCmsController
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
 * @version     $Id: DashboardCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * DashboardCmsController
 *
 * @package     CrowdFusion
 */
class DashboardCmsController extends AbstractCmsController
{
    public function setSubversionTools(SubversionTools $SubversionTools)
    {
        $this->SubversionTools = $SubversionTools;
    }

    public function setVersionService(VersionService $VersionService)
    {
        $this->VersionService = $VersionService;
    }

    protected function svnInfo()
    {
        $svnInfo = $this->SubversionTools->getInfo();
        if(!empty($svnInfo))
            return array($svnInfo);

        return array();
    }

    protected function versions()
    {
        return array(
            array(
                'DeploymentRevision' => (int)$this->VersionService->getDeploymentRevision(),
                'CFVersion' => $this->VersionService->getCrowdFusionVersion()
            ));
    }

    protected function successes()
    {

        if((int)$this->VersionService->getDeploymentRevision() == 1)
        {
            return array(
                array( 'Message' => "Welcome! You've successfully installed Crowd Fusion.")

            );
        }
    }

    protected function warnings()
    {
        return array();

    }
}
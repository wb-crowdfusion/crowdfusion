<?php
/**
 * UpgradeService
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
 * @version     $Id: UpgradeService.php 2014 2010-02-17 14:01:16Z ryans $
 */

/**
 * UpgradeService
 *
 * @package     CrowdFusion
 */
class UpgradeService
{

    public function setVersionService(VersionService $VersionService)
    {
        $this->VersionService = $VersionService;
    }

    public function setCfVersion($cfVersion)
    {
        $this->cfVersion = $cfVersion;
    }

    public function setSystemDataSource(DataSourceInterface $SystemDataSource)
    {
        $this->SystemDataSource = $SystemDataSource;
    }

    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

//    public function setSystemXMLFile($systemXMLFile)
//    {
//        $this->systemXMLFile = $systemXMLFile;
//    }

    public function upgrade()
    {
        $cv = $this->VersionService->getCrowdFusionVersion();

        if(empty($cv))
            die('Your installation lacks a Crowd Fusion version number.');

        $log = "Upgrading from version [$cv] to [{$this->cfVersion}]\n";

        $this->VersionService->incrementDeploymentRevision("Upgrading from version [$cv] to [{$this->cfVersion}]\n\n");
        //echo str_replace("\n","<br/>",$log."<a href=\"/\">Proceed &raquo;</a>");
        //exit;
    }


}
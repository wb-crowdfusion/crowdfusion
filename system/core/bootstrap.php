<?php
 /**
 * No Summary
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @package     CrowdFusion
 * @copyright   2009 Crowd Fusion Inc.
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version     $Id: bootstrap.php 1977 2010-02-09 22:54:46Z ryans $
 */


global $now;

$this->object('ErrorHandler');

$bm = $this->object('Benchmark');

$bm->start('all', $now, true);

if($this->isHotDeploy() || $this->isOneOffRedeploy())
{

    if($this->objectExists('SystemDataSource')) {

        foreach($this->object('SystemDataSource')->getConnectionsForReadWrite() as $connCouplet) {
            $dbConn = $connCouplet->getConnection();

            $tables = $dbConn->readCol("SHOW TABLES LIKE {$dbConn->quote('sysversion')}");

            if(count($tables) == 0)
                $dbConn->import(PATH_SYSTEM."/core/schema/db.sql");

        }

    }

    if($this->property('cf.version') !== $this->object('VersionService')->getCrowdFusionVersion())
    {
        $this->object('UpgradeService')->upgrade();
    }



    $this->object('PluginInstallationService')->scanInstall();
}


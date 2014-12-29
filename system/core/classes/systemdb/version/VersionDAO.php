<?php
/**
 * VersionDAO
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
 * @version     $Id: VersionDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * VersionDAO
 *
 * @package     CrowdFusion
 */
class VersionDAO extends AbstractDAO
{
    protected $dsn;
    protected $cfVersion;

    protected $version = null;
    protected $dbCfVersion = null;

    /**
     * [IoC] Injects the SystemDataSource
     *
     * @param DataSourceInterface $SystemDataSource The SystemDataSource
     *
     * @return void
     */
    public function setSystemDataSource(DataSourceInterface $SystemDataSource)
    {
        $this->dsn = $SystemDataSource;
    }

    public function setCfVersion($cfVersion)
    {
        $this->cfVersion = $cfVersion;
    }

    /**
     * Retrieves the current SystemVersion from the SystemDataSource
     *
     * @return integer The current SystemVersion
     */
    public function getSystemVersion()
    {
        $version = $this->getVersionNumbers()->version;

        if(empty($version))
            return false;

        return $version;
    }

    public function getCrowdFusionVersion()
    {
        $version = $this->getVersionNumbers()->dbCfVersion;

        if(empty($version))
            return false;

        return $version;
    }

    protected function getVersionNumbers()
    {
        if(is_null($this->version))
        {
            $db = $this->getConnection();

            $q = new Query();

            $q->SELECT('Version', true);
            $q->SELECT('CFVersion');
            $q->FROM('sysversion');
            $q->ORDERBY('version desc');
            $q->LIMIT(1);

            $row = $db->readOne($q);
            if(!empty($row))
            {

                $this->version = $row['Version'];
                $this->dbCfVersion = $row['CFVersion'];
            }

            if(empty($this->dbCfVersion))
                $this->insertSystemVersion('Save CFVersion');
        }
        return $this;
    }

    /**
     * Increments the current system version
     *
     * @param string $details   A string detailing changes or the reason for the new version
     * @param string $backtrace A backtrace that documents where this call originated from.
     *
     * @return integer The new current version
     */
    public function insertSystemVersion($details = null, $backtrace = null)
    {
        if(is_null($this->cfVersion))
            return false;

        $db = $this->getConnection();

        $version = $db->insertRecord('sysversion', array(
            'CFVersion' => $this->cfVersion,
            'CreationDate' => $this->DateFactory->newStorageDate(),
            'Details' => is_string($details) ? $details : null,
            'Backtrace' => $backtrace
        ));


        $this->version = $version;
        $this->dbCfVersion = $this->cfVersion;

        return $version;
    }

    /**
     * Returns a DatabaseInterface object we'll use
     * to interact with the database
     *
     * @return DatabaseInterface
     */
    public function getConnection()
    {
        return $this->dsn->getConnectionsForReadWrite()->offsetGet(0)->getConnection();
    }
}
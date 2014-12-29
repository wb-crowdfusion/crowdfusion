<?php
/**
 * Locks
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
 * @version     $Id: Locks.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Locks
 *
 * @package     CrowdFusion
 */
class Locks implements LocksInterface
{

    protected $dbName = false;

    public function setSystemDataSource(DataSourceInterface $SystemDataSource)
    {
        $this->SystemDataSource = $SystemDataSource;
    }



    protected function getConnection()
    {
        $conn = $this->SystemDataSource->getConnectionsForReadWrite()->offsetGet(0)->getConnection();
        if($this->dbName == false)
            $this->dbName = $conn->getDatabaseName();

        return $conn;
    }



    /**
     *
     * @return mixed True if lock was made, false if not
     */
    public function getLock($name, $timeout)
    {
        $db = $this->getConnection();
        $l = $db->readField("SELECT GET_LOCK({$db->quote($this->dbName.':'.$name)}, {$db->quote($timeout)})");
        if($l === null)
            throw new LocksException('Unable to get lock on ['.$name.']');

        return $l == true;
    }


    public function releaseLock($name)
    {
        $db = $this->getConnection();
        $l = $db->readField("SELECT RELEASE_LOCK({$db->quote($this->dbName.':'.$name)})");
        return $l == true;
    }

    public function isLockFree($name)
    {
        $db = $this->getConnection();
        $l = $db->readField("SELECT IS_FREE_LOCK({$db->quote($this->dbName.':'.$name)})");
        if($l === null)
            throw new LocksException('Unable to get lock on ['.$name.']');

        return $l == true;
    }


}
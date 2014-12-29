<?php
/**
 * AbstractMySQLDataSource
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
 * @version     $Id: AbstractMySQLDataSource.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * An abstract datasource definition for mysql
 *
 * @package     CrowdFusion
 */
abstract class AbstractMySQLDataSource
{
    protected $TransactionManager;
    protected $DatabaseServiceName;
    protected $ApplicationContext;

    /**
     * Creates the object
     *
     * @param TransactionManagerInterface $TransactionManager  The transaction manager to use
     * @param ApplicationContext          $ApplicationContext  The application context
     * @param string                      $DatabaseServiceName The name for the database service
     */
    public function __construct(TransactionManagerInterface $TransactionManager, ApplicationContext $ApplicationContext, $DatabaseServiceName)
    {
        $this->TransactionManager = $TransactionManager;
        $this->ApplicationContext = $ApplicationContext;
        $this->DatabaseServiceName = $DatabaseServiceName;
    }

    /**
     * Gets a new connection using the connection info given
     *
     * @param array $connectionInfo An array of connection info
     *
     * @return DatabaseInterface
     */
    protected function getNewConnection($connections)
    {
        $connection = $this->ApplicationContext->object($this->DatabaseServiceName);
        $connection->setConnectionInfo($connections);
        return $connection;
    }

}

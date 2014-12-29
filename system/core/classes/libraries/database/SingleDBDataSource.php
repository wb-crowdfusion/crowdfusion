<?php
/**
 * SingleDBDataSource
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
 * @version     $Id: SingleDBDataSource.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Basic implementation of DataSourceInterface.  Produces a standard PDO object (used for single database deployments).
 *
 * @package     CrowdFusion
 */
class SingleDBDataSource extends AbstractMySQLDataSource implements DataSourceInterface
{
    protected $connection;
    protected $connectionInfo;

    protected $connections = array();

    /**
     * Sets connection information.
     *
     * @param TransactionManagerInterface $TransactionManager  The transaction manager to use
     * @param ApplicationContext          $ApplicationContext  The application context
     * @param string                      $DatabaseServiceName The name for the database service
     * @param array                       $connectionInfo      Connection info array:
     *                                                          array(
     *                                                              'host'          => 'localhost',       # Host name or IP-address
     *                                                                                                    # for database server.
     *                                                                                                    # Defaults to localhost.
     *
     *                                                              'port'          => 3306,              # Port number for database server.
     *                                                                                                    # Defaults to 3306.
     *
     *                                                              'timeout'       => 30,                # Connection timeout (in seconds).
     *                                                              'database'      => 'exampledb',       # Name of database.
     *                                                              'username'      => 'exampleuser',     # User name for database.
     *                                                              'password'      => 'password',        # Password for database.
     *                                                              'unix_socket'   => '/path/to/socket', # Path to Unix socket to connect through.
     *                                                              'persistent'    => false              # If true, use persistent connections.
     *                                                          )
     *
     * @throws Exception If the required parameters aren't passed
     */
    public function __construct(TransactionManagerInterface $TransactionManager,
                                ApplicationContext $ApplicationContext,
                                $DatabaseServiceName,
                                array $connectionInfo)
    {
        parent::__construct($TransactionManager, $ApplicationContext, $DatabaseServiceName);

        $this->connectionInfo = $connectionInfo;
    }


    /**
     * Gets a connection to the data store for master-level reads and writes.
     *
     * @param array $hints Not supported
     *
     * @return array Array of MySQLDatabase objects
     *
     * @throws ConnectionException If a connection could not be retrieved
     */
    public function getConnectionsForReadWrite($hints = false)
    {
        return new ArrayObject( array(new ConnectionCouplet(
            $this->getConnection(true), $hints)) );
    }

    /**
     * Gets a connection to the data store for slave-level reads only.
     *
     * @param array $hints Not supported
     *
     * @return array Array of MySQLDatabase objects
     *
     * @throws ConnectionException If a connection could not be retrieved
     */
    public function getConnectionsForRead($hints = false)
    {
        return new ArrayObject( array(new ConnectionCouplet(
            $this->getConnection(false), $hints)) );
    }

    /**
     * Gets the connection
     *
     * @return DatabaseInterface
     */
    protected function getConnection($bindTransaction)
    {
        if (empty($this->connection)) {
            $this->connection = $this->getNewConnection(array($this->connectionInfo));
            $this->connections[] = $this->connection;

            if($bindTransaction)
                $this->TransactionManager->bindConnection($this->connection);
        }
        return $this->connection;
    }


    public function disconnectAllConnections()
    {
        if (!empty($this->connection)){
            $this->connection->disconnect();
            $this->TransactionManager->unbindConnection($this->connection);
        }

        $this->connection = null;
        $this->connections = array();

    }

    /**
     * Gets all connections
     *
     * @return array
     */
    public function getAllConnections()
    {
        return $this->connections;
    }
}
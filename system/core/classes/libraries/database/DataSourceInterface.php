<?php
/**
 * DataSourceInterface
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
 * @version     $Id: DataSourceInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * A factory for connections to the physical data source that this DataSource object represents. A DataSource object
 * is the preferred means of getting a connection. An object that implements the DataSource interface will typically
 * be registered with a naming service ...
 *
 * @package    CrowdFusion
 */
interface DataSourceInterface
{

    /**
     * Gets a connection to the data store for master-level reads and writes.
     *
     * Connection credentials (if needed) should be injected on the the DataSource
     * implementation prior to calling this function.
     *
     * Typically this method does not actually make the necessary connection to
     * the datastore but passes along the credentials necessary for the connection
     * object to lazy-load the connection when needed.
     *
     * @param array $hints An array used by a specific DataSource implementation
     *  to determine the most appropriate connection to return. Most useful in
     *  a sharded database environment where it's necessary to get a connection
     *  based on the criteria of the request.
     *
     * @return array Array of ConnectionCouplet objects containing a connection
     *  (permits reading and writing from a datastore) and its associated hints,
     *  if any. The type of connection object is dependent on the implementation.
     *
     * @throws ConnectionException If a connection could not be retrieved
     */
    public function getConnectionsForReadWrite($hints = false);

    /**
     * Gets a connection to the data store for slave-level reads only.
     *
     * @param array $hints An array used by a specific DataSource implementation
     *  to determine the most appropriate connection to return. Most useful in
     *  a sharded database environment where it's necessary to get a connection
     *  based on the criteria of the request.
     *
     * @return array Array of ConnectionCouplet objects containing a connection
     *  (permits reading and writing from a datastore) and its associated hints,
     *  if any. The type of connection object is dependent on the implementation.
     *
     * @throws ConnectionException If a connection could not be retrieved
     */
    public function getConnectionsForRead($hints = false);

    /**
     * Return all connection objects constructed by this DataSource during the
     * course of the request.  This is handy for reseting all connections at
     * once or managing transactions across all the connections.
     *
     * @return array All connections created during this request
     */
    public function getAllConnections();

    /**
     * Resets all connections, useful for long-running scripts
     */
    public function disconnectAllConnections();

}
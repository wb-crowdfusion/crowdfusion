<?php
/**
 * ConnectionCouplet
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
 * @version     $Id: ConnectionCouplet.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Defines a connection couplet.
 *
 * When running a system with multiple databases (sharded), it becomes impossible to
 * simply specify getConnection() because different datasets may be stored in different databases.
 *
 * The connectionCouplet, therefore, stores the connection to the database (or other data store) with
 * an array of $hints to identify the connection.
 *
 * @package    CrowdFusion
 * 
 * @see        DataSourceInterface::getConnectionsForReadWrite()
 */
class ConnectionCouplet
{

    protected $hints = array();
    protected $connection = null;

    protected $attributes = array();

    /**
     * Creates a new ConnectionCouplet object.
     *
     * @param object $connection Required. Any object that represents a data store connection.
     * @param array  $hints      An array of objects used to find this connection.
     */
    public function __construct($connection, $hints = null)
    {
        if (!empty($hints))
            $this->hints = $hints;

        $this->connection = $connection;
    }

    /**
     * Gets the wrapped data store connection object
     *
     * @return object Wrapped data store connection object
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Gets the hints array
     *
     * @return array Hint array
     */
    public function getHints()
    {
        return $this->hints;
    }

    /**
     * Saves an attribute for this ConnectionCouplet
     *
     * @param string $key   The attribute key
     * @param mixed  $value The attribute value
     *
     * @return void
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Retrieves an attribute stored on this ConnectionCouplet
     *
     * @param string $key The attribute key
     *
     * @return mixed the value that was stored in the attribute, or null if the key doesn't exist
     */
    public function getAttribute($key)
    {
        return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : null;
    }


}
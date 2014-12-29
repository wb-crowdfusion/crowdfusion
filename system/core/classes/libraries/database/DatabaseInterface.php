<?php
/**
 * DatabaseInterface
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
 * @version     $Id: DatabaseInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Presents a simple convenience wrapper to PDO database access
 *
 * To save the need for making explicit database connection calls, the first
 * statement will make the initial connection to the database
 *
 * NOTE: It is expected that all DATETIME or TIMESTAMP or other native
 * date column types should automatically be converted to StorageDate when
 * selecting data from the database.  Likewise, when inserting data, your
 * implementation should convert from StorageDate into the native column type.
 *
 * Support for master/slave or sharded databases is determined by the
 * implementation
 *
 * @package     CrowdFusion
 */
interface DatabaseInterface
{

    /**
     * Used to indicate you'd like write() to return number of affected rows
     */
    const AFFECTED_ROWS = 0;

    /**
     * Used to indicate you'd like write() to return the insertion id
     */
    const INSERTION_ID  = 1;

    /**
     * Set the connection information necessary for create
     *
     * @param array $connectionInfo The connection info
     *
     * @return void
     */
    public function setConnectionInfo(array $connectionInfo);

    /**
     * Gets the current PDO connection object
     *
     * If no connection was established, this will make the connection
     *
     * @return PDO
     */
    public function getConnection();

    /**
     * Start a transaction, all subsequent inserts/updates/deletes will be
     * part of the transaction
     *
     * @return void
     * @throws DatabaseException Upon failure
     */
    public function beginTransaction();

    /**
     * Commit the transaction
     *
     * @return void
     * @throws DatabaseException Upon failure to commit
     */
    public function commit();

    /**
     * Rollback the transaction
     *
     * @return void
     * @throws DatabaseException Upon failure to rollback
     */
    public function rollback();

    /**
     * Returns true if a transaction has been started and has not been committed
     * or rolled back.
     *
     * @return bool True if transaction is in progress
     */
    public function isTransactionInProgress();

    /**
     * Closes any open connections to the database
     *
     * This is useful for long-running commands where database connection
     * timeouts would cause connection errors for new SQL statements
     *
     * @return void
     */
    public function disconnect();

    /**
     * Returns the database server version number as a string
     *
     * @return string Server version, ex. '5.0.0'
     */
     public function getServerVersion();

    /**
     * Returns the first column of the first row of the result set
     *
     * NOTE: It is expected that all DATETIME or TIMESTAMP or other native
     * date column types should automatically be converted to StorageDate.
     *
     * @param string $sql SELECT statement
     *
     * @return string|StorageDate
     */
    public function readField($sql);

    /**
     * Returns an array of values for the first column of the result set
     *
     * <code>
     *  $result = $this->readCol('SELECT col FROM table');
     *
     *  ...
     *
     *  array(
     *      0 => 'value1',
     *      1 => 'value2',
     *      2 => 'value3'
     *  )
     * </code>
     *
     * @param string $sql SELECT statement
     *
     * @return array Single dimension array of values for the column
     */
    public function readCol($sql);

    /**
     * Returns the first row of the result set as an associative array, regardless
     * of the overall number of rows
     *
     * <code>
     *  $result = $this->readOne('SELECT col1, col2 FROM table');
     *
     *  ...
     *
     *  array(
     *      'col1' => 'value of col1',
     *      'col2' => 'value of col2'
     *  )
     *
     * </code>
     *
     * @param string $sql SELECT statement
     *
     * @return array Single dimension array of the first row returned by the
     *  SELECT query, regardless of overall result set count
     */
    public function readOne($sql);

    /**
     * Returns an associative array of rows and columns
     *
     * <code>
     * $result = $this->readAll('SELECT id, col1 FROM table');
     *
     * ...
     *
     * array(
     *  0 => array( 'id' => '1', 'col1' => 'value'),
     *  1 => array( 'id' => '2', 'col1' => 'value'),
     *  ...
     * )
     * </code>
     *
     * @param string  $sql           SELECT statement
     * @param boolean $calcFoundRows Flag to indicate to the underlying DB that
     *  the query should calculate the total number of rows matching the
     *  WHERE clause of the SELECT regardless of any LIMIT clause.  In MySQL,
     *  this equates to injecting SQL_CALC_FOUND_ROWS into the SELECT.
     *
     * @return array Associative array of rows and columns
     */
    public function readAll($sql, $calcFoundRows = true);

    /**
     * Returns a PDOStatement object for the result
     *
     * @param string  $sql           SELECT statement
     *
     * @return PDOStatement The query result
     * @see http://us.php.net/manual/en/pdo.query.php
     */
    public function read($sql);

    /**
     * Returns the original row count, or total found rows, of the previous
     * SELECT statement regardless of the LIMIT.  In MySQL, this equates to
     * "SELECT FOUND_ROWS();"
     *
     * @return int Number of rows matching original WHERE clause
     */
    public function getOriginalRowCount();

    /**
     * Executes a SQL statement, similar to the PDO exec() method
     *
     * @param string $sql    SQL statement to execute
     * @param int    $return Controls whether to return the insert id or the
     *  number of affected rows. This value must be one of the 2 DatabaseInterface
     *  constants: AFFECTED_ROWS or INSERTION_ID
     *
     * @return int see {@link $return}
     */
    public function write($sql, $return = self::INSERTION_ID);

    /**
     * Inserts multiple records utilizing less INSERT statements.
     *
     * This function will execute as many statements as necessary to insert
     * all the data supplied by the array {@link $parameters} inserting {@link $limit}
     * records per statement.  {@link $limit} is useful for preventing max
     * query size errors.
     *
     * @param string $tablename  Table to insert records
     * @param string $parameters An associative array of rows and columns to insert
     *  similar to the results of {@link readAll()}
     *  <code>
     *   array(
     *     0 => array( 'id' => '1', 'col1' => 'value'),
     *     1 => array( 'id' => '2', 'col1' => 'value'),
     *     ...
     *   )
     *  </code>
     * @param int    $limit      The maximum number of records to insert on a
     *  single statement, defaults to 25
     *
     * @return int Number of inserted rows
     * @throws DatabaseException if tablename or parameters array is empty
     */
    public function bulkInsertRecords($tablename, $parameters, $limit = 25);

    /**
     * Provides a convenience method for inserting an single row of columns
     * and values into a database table
     *
     * @param string $tablename  Table to insert record
     * @param array  $parameters Associative array of columns and values, ex.
     * <code>
     * array(
     *      'col1' => 'value of col1',
     *      'col2' => 'value of col2'
     *  )
     * </code>
     *
     * @return int Insert id of the record
     * @throws DatabaseException if tablename or parameters array is empty
     */
    public function insertRecord($tablename, $parameters);

    /**
     * Provides convenience method for updating columns on a table for 1 or
     * many records matching a WHERE clause
     *
     * @param string $tablename  Table to update
     * @param array  $parameters Associative array of columns and values, ex.
     * <code>
     * array(
     *      'col1' => 'value of col1',
     *      'col2' => 'value of col2'
     *  )
     * </code>
     * @param string $where      Where clause used to find the records to update,
     *  ex. "id = '2'", "title LIKE '%example%' AND status = '2'"
     *
     * @return int Number of affected rows
     * @throws DatabaseException if tablename, parameters, or where clause is empty
     */
    public function updateRecord($tablename, $parameters, $where);

    /**
     * Provides convenience method for deleting records matching a WHERE clause
     *
     * @param string $tablename Table to delete from
     * @param string $where     Where clause used to find the records to delete,
     *                              ex. "id = '2'", "title LIKE '%example%' AND status = '2'"
     *
     * @return int Number of affected rows
     * @throws DatabaseException if tablename or where clause is empty
     */
    public function deleteRecord($tablename, $where);

    /**
     * Returns escaped value automatically wrapped in single quotes
     *
     * NOTE: Will convert Date types into strings for database entry
     *
     * @param mixed $var Value to escape and quote
     *
     * @return string Escaped and quoted string value ready for a SQL statement
     */
    public function quote($var);

    /**
     * Returns escaped value, does NOT wrap in single quotes
     *
     * NOTE: Will convert Date types into strings for database entry
     *
     * @param mixed $var Value to escape
     *
     * @see quote()
     *
     * @return string Escaped string value ready for a SQL statement
     */
    public function esc($var);

    /**
     * Returns a comma-separated quoted list of arguments for use within an
     * SQL IN(..) statement.
     *
     * @param array $array Array list of values
     *
     * @see quote()
     *
     * @return string In the form "'value1','value2','value3'"
     */
    public function joinQuote(array $array);
}
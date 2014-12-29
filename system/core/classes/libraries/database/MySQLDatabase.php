<?php
/**
 * Default Database implementation for MySQL
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
 * @version     $Id: MySQLDatabase.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Default Database implementation for MySQL
 *
 * @package     CrowdFusion
 */
class MySQLDatabase implements DatabaseInterface
{
    protected $Logger;
    protected $DateFactory;

    protected $connectionConfig;

    protected $connection = null;
    protected $deadlockRetries = null;

    //protected $shouldStartTransaction = false;
    protected $transactionInProgress = false;
    protected $connDebugString;
    protected $currentDBName;

    /**
     * Constructs the MySQLDatabase object
     *
     * @param LoggerInterface    $Logger          IoC Logger
     * @param BenchmarkInterface $Benchmark       IoC Benchmark
     * @param DateFactory        $DateFactory     IoC DateFactory
     * @param integer            $deadlockRetries The number of deadlock retries before an exception is thrown. Default: 6
     */
    public function __construct(LoggerInterface $Logger,
                                BenchmarkInterface $Benchmark,
                                DateFactory $DateFactory,
                                $deadlockRetries = 6)
    {
        $this->Logger          = $Logger;
        $this->Benchmark       = $Benchmark;
        $this->DateFactory     = $DateFactory;
        $this->deadlockRetries = $deadlockRetries;
    }


    /**
     * Validates required values are present for a database connection.
     *
     * @param array $connectionInfo Connection info. See {@link __construct(...)} for details on keys and values.
     *
     * @return void
     * @throws Exception If the required parameters aren't passed
     */
    public function setConnectionInfo(array $connections)
    {

        foreach($connections as $connectionInfo)
        {
            if (empty($connectionInfo['hostname']))
                $connectionInfo['hostname'] = 'localhost';

            if (empty($connectionInfo['port']))
                $connectionInfo['port'] = 3306;

            if (empty($connectionInfo['timeout']))
                $connectionInfo['timeout'] = 30;

            if (empty($connectionInfo['database']))
                throw new DatabaseException("Database name is empty.");

            if (empty($connectionInfo['username']))
                throw new DatabaseException("Database username is empty.");

        }

         //if(empty($connectionInfo['password']))
         //    throw new DatabaseException("Database password is empty.");

        $this->connectionConfig = $connections;
    }

    /**
     * Gets the current PDO connection object
     *
     * @return PDO MySQL PDO Connection
     */
    public function getConnection()
    {
        if (empty($this->connection)) {

            if (empty($this->connectionConfig) || !is_array($this->connectionConfig))
                throw new DatabaseException("Connection config is empty or not an array.");

            $connCount = count($this->connectionConfig);
            for($i = 0; $i < $connCount; ++$i)
            {
                $connectionInfo = $this->connectionConfig[$i];

                if (empty($connectionInfo['port']))
                    $connectionInfo['port'] = 3306;

                $dsn = "mysql:dbname={$connectionInfo['database']};host={$connectionInfo['host']};port={$connectionInfo['port']}";
                if (!empty($connectionInfo['unix_socket']))
                    $dsn .= ';unix_socket='.$connectionInfo['unix_socket'];

                try {

                    $trx_attempt = 0;
                    while ($trx_attempt < $this->deadlockRetries) {

                        try {

                            $this->connection = new PDO($dsn, $connectionInfo['username'], $connectionInfo['password'], array(
                                PDO::ATTR_PERSISTENT => (!empty($connectionInfo['persistent'])?StringUtils::strToBool($connectionInfo['persistent']):false),
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_CASE => PDO::CASE_NATURAL,
                                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                                PDO::MYSQL_ATTR_DIRECT_QUERY => true,
                                PDO::ATTR_TIMEOUT => (!empty($connectionInfo['timeout'])?$connectionInfo['timeout']:30) ));


                            $this->connDebugString = $connectionInfo['host'].':'.$connectionInfo['database'];
                            $this->currentDBName = $connectionInfo['database'];

                            $this->Logger->debug(array(
                                'SQL'                    => 'CONNECTION - DB: '.$this->connDebugString,
                            ));

                            return $this->connection;

                        } catch (PDOException $pe) {
                            if ((strpos($pe->getMessage(), 'Too many connections') !== FALSE) && ++$trx_attempt < $this->deadlockRetries)
                            {
                                usleep( pow(2,$trx_attempt) * 100000 );
                                continue;
                            }

                            throw $pe;
                        }
                    }

                } catch(PDOException $pe)
                {

                    // if the last connection fails
                    if($i == ($connCount-1))
                        throw new SQLConnectionException($pe->getMessage());
                }
            }
        }

        return $this->connection;
    }

    public function getDatabaseName()
    {
        return $this->currentDBName;
    }


    /**
     * Start a transaction, all subsequent inserts/updates/deletes will be
     * part of the transaction
     *
     * @return void
     * @throws DatabaseException Upon failure
     */
    public function beginTransaction()
    {
//        $this->shouldStartTransaction = true;


        if (!$this->transactionInProgress) {

            $conn = $this->getConnection();

            $this->Benchmark->start('sql-trans');

            if($conn->beginTransaction() === false)
                throw new DatabaseException('TRANSACTION BEGIN FAILED');

            //$this->write('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

            $this->transactionInProgress = true;
            // register_shutdown_function(array($this, "__shutdown_check"));

            $this->Logger->debug(array(
                            'Conn'                => $this->connDebugString,
                            'SQL'                 => 'BEGIN TRANSACTION',
                            'Execution Time (ms)' => $this->Benchmark->end('sql-trans'),
                        ));
        }
    }

    /**
     * Commit the transaction
     *
     * @return void
     * @throws DatabaseException Upon failure to commit
     */
    public function commit()
    {
        if ($this->transactionInProgress === true) {

            $this->Benchmark->start('sql-trans');

            if ($this->getConnection()->commit() === false)
                throw new DatabaseException('TRANSACTION COMMIT FAILED');

            $this->Logger->debug(array(
                            'Conn'                => $this->connDebugString,
                            'SQL'                 => 'COMMIT',
                            'Execution Time (ms)' => $this->Benchmark->end('sql-trans'),
                        ));
        }

        $this->transactionInProgress  = false;
        //$this->shouldStartTransaction = false;

    }

    /**
     * Rollback the transaction
     *
     * @return void
     * @throws DatabaseException Upon failure to rollback
     */
    public function rollback()
    {
        if ($this->transactionInProgress === true) {

            $this->Benchmark->start('sql-trans');


            if ($this->getConnection()->rollBack() === false)
                throw new DatabaseException('TRANSACTION ROLLBACK FAILED');

            $this->Logger->debug(array(
                            'Conn'                => $this->connDebugString,
                            'SQL'                 => 'ROLLBACK',
                            'Execution Time (ms)' => $this->Benchmark->end('sql-trans'),
                        ));
        }

        $this->transactionInProgress  = false;
        //$this->shouldStartTransaction = false;
    }

    /**
     * Returns true if a transaction has been started and not been committed or
     * rolled back.
     *
     * @return bool True if transaction is in progress
     */
    public function isTransactionInProgress()
    {
        return $this->transactionInProgress;
    }

    /**
     * Closes any open connections to the database, forcing any new requests
     * to re-establish the connection.
     *
     * This is useful for long-running commands where database connection
     * timeouts would cause connection errors for new SQL statements
     *
     * @return void
     */
    public function disconnect()
    {
        $this->connection = null;
        unset($this->connection);
    }

    /**
     * Returns the database server version number as a string
     *
     * @return string Server version, ex. '5.0.0'
     */
    public function getServerVersion()
    {
        return $this->getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

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
    public function readField($sql)
    {
        $result = $this->read($sql);
        $result->setFetchMode(PDO::FETCH_COLUMN, 0);
        $row = $result->fetch();
        //$this->convertDatesInRows($result, $row);
        unset($result);
        return $row;
    }

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
    public function readCol($sql)
    {
        $result = $this->read($sql);
        $row    = $result->fetchAll(PDO::FETCH_COLUMN, 0);
        //$this->convertDatesInRows($result, $row);
        unset($result);
        return $row;
    }

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
    public function readOne($sql)
    {
        $result = $this->readAll($sql, false);
        if (count($result) > 0) {
            return $result[0];
        }
    }

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
    public function readAll($sql, $calcFoundRows = false)
    {
        $result = $this->read($calcFoundRows ? $this->insertPagingFlag($sql) : $sql);
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        //$this->convertDatesInRows($result, $rows);
        unset($result);
        unset($sql);
        return $rows;
    }

    /**
     * Returns the original row count, or total found rows, of the previous
     * SELECT statement regardless of the LIMIT.  In MySQL, this equates to
     * "SELECT FOUND_ROWS();"
     *
     * @return int Number of rows matching original WHERE clause
     */
    public function getOriginalRowCount()
    {
        return intVal($this->readField('SELECT FOUND_ROWS()'));
    }

    /**
     * Executes a SQL statement, similar to the PDO exec() method
     *
     * @param string $sql    SQL statement to execute
     * @param int    $return Controls whether to return the insert id or the
     *                        number of affected rows. This value must be one of the 2 DatabaseInterface
     *                        constants: AFFECTED_ROWS or INSERTION_ID.
     *                        Default: INSERTION_ID
     *
     * @return int Returns the value specified by {@link $return}
     */
    public function write($sql, $return = DatabaseInterface::INSERTION_ID)
    {
        $sql = trim((string)$sql);

        $conn = $this->getConnection();

        $this->Benchmark->start('sql-query');

        $trx_attempt = 0;
        while ($trx_attempt < $this->deadlockRetries) {

            try {
                $affectedRows = $conn->exec($sql);

                if ($affectedRows !== false) {
                    $insertId = 0;
                    if ($return == DatabaseInterface::AFFECTED_ROWS) {
                        $result = $affectedRows;
                    } else {
                        $insertId = $conn->lastInsertId();
                        $result   = $insertId;
                    }

                    $this->Logger->debug(array(
                        'Conn'                => $this->connDebugString,
                        'SQL'                 => $sql,
                        'Execution Time (ms)' => $this->Benchmark->end('sql-query'),
                        'Rows Affected'       => $affectedRows,
                        'Rows Returned'       => 'ID: ' . $insertId,
                    ));

                    return $result;
                }
                return false;

            } catch (PDOException $pe) {

                if (($pe->getCode() == 'HY000' || $pe->getCode() == 40001) && ++$trx_attempt < $this->deadlockRetries) {
                    usleep( pow(2,$trx_attempt) * 100000 );
                    continue;
                }

                $errorMessage = $pe->getMessage();

                $this->Logger->debug(array(
                    'Conn'                => $this->connDebugString,
                    'SQL'                 => $sql,
                    'Execution Time (ms)' => $this->Benchmark->end('sql-query'),
                    'Error'               => $errorMessage
                ));

                if ($pe->getCode() == 23000 && strpos($errorMessage, 'Integrity constraint violation: 1062') !== FALSE) {
                    throw new SQLDuplicateKeyException($sql, $errorMessage, intVal($pe->getCode()));
                }

                throw new SQLException($sql, $errorMessage, intVal($pe->getCode()));
            }
        }

        return false;
    }

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
     *                            similar to the results of {@link readAll()}
     *                            <code>
     *                             array(
     *                               0 => array( 'id' => '1', 'col1' => 'value'),
     *                               1 => array( 'id' => '2', 'col1' => 'value'),
     *                               ...
     *                             )
     *                            </code>
     * @param int    $limit      The maximum number of records to insert on a
     *                            single statement, defaults to 25
     *
     * @return int Number of inserted rows
     * @throws DatabaseException if tablename or parameters array is empty
     */
    public function bulkInsertRecords($tablename, $parameters, $limit = 25)
    {
        if(empty($parameters))
            throw new DatabaseException('bulkInsertRecords: no parameters.');

        $count      = 0;
        $tcount     = 0;
        $totalcount = count($parameters);

        foreach ($parameters as $value) {

            if (!is_array($value))
                throw new DatabaseException('bulkInsertRecords requires an array of arrays');

            if ($count == 0) {
                $sql = "INSERT INTO  ".$tablename." ";
                $sql .= " (".implode(",", array_keys($value)).")\n VALUES\n ";
                $first = false;
            }

            $sql .= "(".implode(",", array_map(array($this, 'quote'), (array)$value))."),\n";

            ++$count;
            ++$tcount;

            if ($count == $limit || $tcount == $totalcount) {
                $sql = substr($sql, 0, -2);
                $this->write($sql);
                $count = 0;
            }
        }
    }

    /**
     * Provides a convenience method for inserting an single row of columns
     * and values into a database table
     *
     * @param string  $tablename  Table to insert record
     * @param array   $parameters Associative array of columns and values, ex.
     *                             <code>
     *                             array(
     *                                  'col1' => 'value of col1',
     *                                  'col2' => 'value of col2'
     *                              )
     *                             </code>
     * @param boolean $ignore     If true, then we'll ignore key conflicts on insert. Default: false
     *
     * @return int Insert id of the record
     * @throws DatabaseException if tablename or parameters array is empty
     */
    public function insertRecord($tablename, $parameters, $ignore = false)
    {
        if (empty($parameters))
            throw new DatabaseException('insertRecord: no parameters.');

        $insert_fields = array();
        $insert_values = array();

        foreach ($parameters as $name => $value) {
            $insert_fields[] = $name;
            $insert_values[] = $this->quote($value);
        }

        $sql = "INSERT ".( $ignore ? "IGNORE " : "") . "INTO  ".
                 $tablename . " (" . implode(",", $insert_fields) .
                ") VALUES (".
                implode(",", $insert_values) . ")";

        return $this->write($sql);
    }

    /**
     * Provides convenience method for updating columns on a table for 1 or
     * many records matching a WHERE clause
     *
     * @param string $tablename  Table to update
     * @param array  $parameters Associative array of columns and values, ex.
     *                              <code>
     *                              array(
     *                                   'col1' => 'value of col1',
     *                                   'col2' => 'value of col2'
     *                               )
     *                              </code>
     * @param string $where      Where clause used to find the records to update,
     *                              ex. "id = '2'", "title LIKE '%example%' AND status = '2'"
     *
     * @return int Number of affected rows
     * @throws DatabaseException if tablename, parameters, or where clause is empty
     */
    public function updateRecord($tablename, $parameters, $where)
    {
        $update = array();

        foreach ($parameters as $name => $value) {
            $update[] = "$name = " . $this->quote($value) . "";
        }

        $sql = "UPDATE ". $tablename ." SET ". implode(",", $update) . " WHERE " . $where;

        return $this->write($sql, DatabaseInterface::AFFECTED_ROWS);
    }

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
    public function deleteRecord($tablename, $where)
    {
        if (!empty($tablename) && !empty($where)) {
            $sql = "DELETE FROM ".$tablename." WHERE ".$where;
            return $this->write($sql, DatabaseInterface::AFFECTED_ROWS);
        }
    }

    /**
     * Returns escaped value automatically wrapped in single quotes
     *
     * NOTE: Will convert Date types into strings for database entry
     *
     * @param mixed $var Value to escape and quote
     *
     * @return string Escaped and quoted string value ready for a SQL statement
     */
    public function quote($var)
    {
        if(is_null($var) || strtoupper($var) == 'NULL')
            return 'NULL';

        if ($var instanceof Date)
            $var = $this->DateFactory->toStorageDate($var)->toMySQLDate();

        return $this->getConnection()->quote($var);
    }

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
    public function esc($var)
    {
        throw new DatabaseException("esc is deprecated");
    }

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
    public function joinQuote(array $array)
    {
        return join(',', array_map(array($this, 'quote'), (array)$array));
    }

    /**
     * Returns the quoted version of the {@link $identifier}
     *
     * @param string $identifier The identifier to quote
     *
     * @return string The quoted version of the {@link $identifier}
     */
    public function quoteIdentifier($identifier)
    {
        return "`".trim($identifier, "`")."`";
    }

    /**
     * Takes an array of identifiers and returns a string
     * containing all identifiers quoted and ready for use in the query.
     *
     * @param array $array An array of identifiers to join
     *
     * @return string
     */
    public function joinQuoteIdentifiers(array $array)
    {
        return join(',', array_map(array($this, 'quoteIdentifier'), (array)$array));
    }

    /**
     * Runs the SQL query in read-only mode from the database
     *
     * @param string $sql The SQL query to run
     *
     * @return PDOStatement The query result
     * @see http://us.php.net/manual/en/pdo.query.php
     */
    public function read($sql)
    {
        $sql = (string)$sql;

        $conn = $this->getConnection();
        $this->Benchmark->start('sql-query');

        try {

//            if(!substr($sql, 0, 4) == 'SHOW') {
//
//                $explain = $conn->query("EXPLAIN ".$sql);
//
//                $explainresults = $explain->fetchAll(PDO::FETCH_ASSOC);
//            }

//            $start =  microtime(TRUE);

            $result = $conn->query($sql);

//            $end =  microtime(TRUE);
//            $totalms = ($end-$start)*1000;

//            if($totalms > 1 && !empty($explainresults)) {
//
//                foreach($explainresults as $qline)
//                {
//
//                    if(strtolower($qline['select_type']) != 'union result' && strtolower($qline['type']) == 'all')
//                    {
//                        // table scan
//                        $pr = ArrayUtils::arrayToStr($explainresults);
//                        error_log("[SQL]  BAD QUERY: TABLE SCAN {$qline['key']}\n"."SQL:\n\n{$sql}\n\nEXPLAIN:\n\n{$pr}\n\nTOTAL TIME:{$totalms}ms\n");
//
//                    }else if($qline['rows'] > 5000) {
//
//                        // poor index
//                        $pr = ArrayUtils::arrayToStr($explainresults);
//                        error_log("[SQL]  BAD QUERY: SCANNED OVER 5000 ROWS {$qline['key']}\n"."SQL:\n\n{$sql}\n\nEXPLAIN:\n\n{$pr}\n\nTOTAL TIME:{$totalms}ms\n");
//
//                    }
//                }
//
//            }
//
//            if($totalms > 1000) {
//
//                if(!empty($explainresults)) {
//                    $pr = ArrayUtils::arrayToStr($explainresults);
//                    $email = "SQL:\n\n{$sql}\n\nEXPLAIN:\n\n{$pr}\n\nTOTAL TIME:{$totalms}ms\n";
//                } else {
//                    $email = "SQL:\n\n{$sql}\n\nTOTAL TIME:{$totalms}ms\n";
//                }
//                error_log("[SQL]  BAD QUERY: TOOK OVER 1 SECOND\n".$email);
//
//            }




        } catch(PDOException $pe) {
            $errorMessage = $pe->getMessage();

            $this->Logger->debug(array(
                'Conn'                => $this->connDebugString,
                'SQL'                 => $sql,
                'Execution Time (ms)' => $this->Benchmark->end('sql-query'),
                'Error'               => $errorMessage,
            ));


            throw new SQLException($sql, $errorMessage, intVal($pe->getCode()));
        }

        $this->Logger->debug(array(
                'Conn'                => $this->connDebugString,
                'SQL'                 => $sql,
                'Execution Time (ms)' => $this->Benchmark->end('sql-query'),
                'Rows Returned'       => $result->rowCount(),
            ));

        unset($sql);
        unset($conn);

        return $result;
    }

    /**
     * Converts the date-columns in the $rows to their appropriate StorageDate objects.
     *
     * @param PDOStatement &$result The query result object
     * @param array        &$rows   An associative-array of all the rows returned in the query.
     *
     * @return void
     */
//    protected function convertDatesInRows(&$result, &$rows)
//    {
//        $dateColumns = array();
//        for ($i = 0; $i < $result->columnCount(); $i++) {
//            $column = $result->getColumnMeta($i);
//            if (isset($column['native_type']) && ($column['native_type'] == 'DATETIME' || $column['native_type'] == 'TIMESTAMP'))
//                $dateColumns[] = $column['name'];
//        }
//        if (is_array($rows)) {
//            foreach ($rows as &$row) {
//                if (is_array($row)) {
//                    foreach ($dateColumns as $dateColumn) {
//                        $row[$dateColumn]        = ( $row[$dateColumn] != null ? $this->DateFactory->newStorageDate($row[$dateColumn]) : null);
//                        $row[$dateColumn.'Unix'] = ( $row[$dateColumn] != null ? $row[$dateColumn]->toUnix() : 0);
//                    }
//                } else if (!empty($dateColumns))
//                    $row = ( $row != null ? $this->DateFactory->newStorageDate($row) : null);
//            }
//        } else if (!empty($dateColumns))
//                $rows = ( $rows != null ? $this->DateFactory->newStorageDate($rows) : null);
//        unset($dateColumns);
//    }

    /**
     * Inserts the paging flag into the query passed.
     * The paging flag returns the total count of results for the query.
     *
     * @param string $sql The SQL to modify
     *
     * @return string The original SQL query containing the paging flag.
     */
    protected function insertPagingFlag($sql)
    {
        return preg_replace("/SELECT /i", "SELECT SQL_CALC_FOUND_ROWS ", $sql, 1);
    }




    /* IMPORTS ----------------------- */


    /**
     * Imports and runs sql contained in the specified file.
     * The file must contain only valid sql.
     *
     * @param string $filename The filename to load
     *
     * @return void
     */
    public function import($filename)
    {
        // TODO: Wrap this in a transaction!
        if (!file_exists($filename)) {
            throw new Exception("File not found: $filename");
        }

        $sql_queries = file_get_contents($filename);
        $sql_queries = $this->_removeRemarks($sql_queries);
        $sql_queries = StringUtils::smartSplit($sql_queries, ";", "'", "\\'");

        foreach ($sql_queries as $sql) {
            $sql = trim($sql); // Trim newlines so we don't have blank queries
            if (empty($sql))
                continue;
            $this->write($sql);
        }
    }

    /**
     * remove_remarks will strip the sql comment lines out of an uploaded sql file
     *
     * @param string $sql The sql to process
     *
     * @return string the SQL with the remarks removed
     */
    private function _removeRemarks($sql)
    {
        $sql = preg_replace('/\n{2,}/', "\n", preg_replace('/^[-].*$/m', "\n", $sql));
        $sql = preg_replace('/\n{2,}/', "\n", preg_replace('/^#.*$/m', "\n", $sql));
        return $sql;
    }

}

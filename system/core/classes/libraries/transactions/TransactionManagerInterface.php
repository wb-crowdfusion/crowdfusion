<?php

interface TransactionManagerInterface
{
    /**
     * Begins a transaction.
     *
     * @return self
     * @throws TransactionException
     */
    public function begin();

    /**
     * Commits a transaction.
     *
     * @return self
     * @throws TransactionException
     */
    public function commit();

    /**
     * Rollback a transaction.
     *
     * @return self
     * @throws TransactionException
     */
    public function rollback();

    /**
     * Indicates if a transaction is in progress.
     *
     * @return bool
     */
    public function isTransactionInProgress();

    /**
     * Indicates that a transaction occurred in this request
     *
     * Useful for subsequent requests to the master database after a transaction
     * has been committed.  This avoids the requests suffering from slave lag.
     *
     * @return bool
     */
    public function isTransactionalRequest();

    /**
     * Reset transactional request
     *
     * Long running processes that may want to read from the slave database
     * after having committed a transaction should call this method.
     *
     * @return self
     */
    public function resetTransactionalRequest();

    /**
     * Binds a connection object's transaction methods to the TransactionManager
     *
     * If upon binding, a transaction is in progress, this function will automatically
     * start the transaction on the Database object
     *
     * @param DatabaseInterface $connection
     *
     * @return self
     */
    public function bindConnection(DatabaseInterface $connection);

    /**
     * Unbinds a connection object's transaction methods to the TransactionManager
     *
     * @param DatabaseInterface $connection
     *
     * @return self
     */
    public function unbindConnection(DatabaseInterface $connection);
}

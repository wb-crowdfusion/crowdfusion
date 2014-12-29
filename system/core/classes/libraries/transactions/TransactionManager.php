<?php

/**
 * TransactionManager manages transactions. It fires events that start and commit the transaction,
 * and fully supports rollbacks on fatal errors or unexpected shutdown.
 *
 * TransactionManager fires events for each action.  The connections delivered by
 * the DataSourceInterface implementation should subscribe to the three events used by this implementation.
 */
class TransactionManager implements TransactionManagerInterface
{
    /** @var Events */
    protected $Events;
    protected $transactionInProgress = false;
    protected $transactionalRequest = false;
    protected $registeredShutdown = false;

    /**
     * @param Events $Events
     */
    public function __construct(Events $Events)
    {
        $this->Events = $Events;
    }

    /**
     * @see TransactionManagerInterface::begin
     */
    public function begin()
    {
        if ($this->transactionInProgress) {
            throw new TransactionException('Transaction already started.');
        }

        $this->Events->trigger('TransactionManager.begin');
        if (!$this->registeredShutdown) {
            register_shutdown_function(array($this, 'shutdownCheck'));
            $this->registeredShutdown = true;
        }
        $this->transactionInProgress = true;
        $this->transactionalRequest = true;

        return $this;
    }

    /**
     * Shutdown callback to be executed at end of PHP script
     */
    final public function shutdownCheck()
    {
        if ($this->isTransactionInProgress()) {
            $this->rollback();
        }
    }

    /**
     * @see TransactionManagerInterface::commit
     */
    public function commit()
    {
        if ($this->transactionInProgress) {
            $this->Events->trigger('TransactionManager.commit');
            $this->transactionInProgress = false;
        }

        return $this;
    }

    /**
     * @see TransactionManagerInterface::rollback
     */
    public function rollback()
    {
        if (!$this->transactionInProgress) {
            throw new TransactionException("No transaction is started.");
        }

        $this->Events->trigger('TransactionManager.rollback');
        $this->transactionInProgress = false;

        return $this;
    }

    /**
     * @see TransactionManagerInterface::isTransactionInProgress
     */
    public function isTransactionInProgress()
    {
        return $this->transactionInProgress;
    }

    /**
     * @see TransactionManagerInterface::isTransactionalRequest
     */
    public function isTransactionalRequest()
    {
        return $this->transactionalRequest;
    }

    /**
     * @see TransactionManagerInterface::resetTransactionalRequest
     */
    public function resetTransactionalRequest()
    {
        $this->transactionalRequest = false;
        return $this;
    }

    /**
     * @see TransactionManagerInterface::bindConnection
     *
     * Notice the event priorities of how the connection is bound to the transaction.
     *
     * This is important because if you are binding to 'TransactionManager.commit' with
     * a priority of "10" (default) then guess what, your event fires BEFORE the database
     * transaction and you might be doing work before the database has been updated.
     */
    public function bindConnection(DatabaseInterface $connection)
    {
        $this->Events->bindEvent('TransactionManager.rollback', $connection, 'rollback', 100);
        $this->Events->bindEvent('TransactionManager.commit', $connection, 'commit', 100);
        $this->Events->bindEvent('TransactionManager.begin', $connection, 'beginTransaction', 100);

        if ($this->isTransactionInProgress()) {
            $connection->beginTransaction();
        }

        return $this;
    }

    /**
     * @see TransactionManagerInterface::unbindConnection
     */
    public function unbindConnection(DatabaseInterface $connection)
    {
        $this->Events->unbindEvent('TransactionManager.rollback', $connection, 'rollback');
        $this->Events->unbindEvent('TransactionManager.commit', $connection, 'commit');
        $this->Events->unbindEvent('TransactionManager.begin', $connection, 'beginTransaction');
        return $this;
    }
}

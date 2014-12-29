<?php

/**
 * Interface for capturing output into logs
 *
 * @package     CrowdFusion
 */
interface LoggerInterface
{
    /**
     * Records a debug message
     *
     * Debug messages contain information related to internal
     * functions and are only useful to display while developing
     * the software.
     *
     * @param mixed $message The message to record in the log
     *
     * @return $message
     *
     * @throws LoggingException If log message write fails
     **/
    public function debug($message);

    /**
     * Records an info message
     *
     * Info messages provide information about the code and
     * can also include notices and/or information that provides
     * insight on the progress of any action.
     *
     * @param mixed $message The message to record in the log
     *
     * @return $message
     *
     * @throws LoggingException If log message write fails
     **/
    public function info($message);

    /**
     * Records a warning message
     *
     * Warning messages provide information about potentially
     * incorrect or unsafe situations. The
     *
     * @param mixed $message The message to record in the log
     *
     * @return $message
     *
     * @throws LoggingException If log message write fails
     **/
    public function warn($message);

    /**
     * Records an error message
     *
     * Error messages are when bad things happen. These messages
     * will nearly always be generated when an exception is thrown
     * or something happens that disrupts the normal application flow.
     *
     * @param mixed $message The message to record in the log
     *
     * @return $message
     *
     * @throws LoggingException If log message write fails
     **/
    public function error($message);

    /**
     * Records a log message
     *
     * @param mixed  $message The message to record in the log
     * @param int $level   The log level (see defined constants above)
     *
     * @return $message
     *
     * @throws LoggingException If log message write fails
     **/
    public function log($message, $level = LOG_INFO);

    /**
     * Returns true if the Logging system is enabled
     *
     * @return boolean
     */
    public function isEnabled();
}
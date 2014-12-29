<?php
/**
 * AbstractLogger
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
 * @version     $Id: Logger.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Abstract Logger
 *
 * @package     CrowdFusion
 */
abstract class AbstractLogger implements LoggerInterface
{
    protected $history = array();

    protected $levels = array(
        LOG_DEBUG   => 'DEBUG',
        LOG_INFO    => 'INFO',
        LOG_NOTICE  => 'NOTICE',
        LOG_WARNING => 'WARN',
        LOG_ERR     => 'ERROR',
        LOG_CRIT    => 'CRIT',
        LOG_EMERG   => 'EMERG'
    );

    protected $siteSlug   = null;
    protected $deviceView;
    protected $design;
    protected $isSiteDeployment;

    protected $ApplicationContext;
    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    protected $environment;
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    protected $context;
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @var bool $enabled Logger Enabled or not (defaults to true)
     */
    protected $enabled = true;
    public function setLoggerEnabled($loggerEnabled)
    {
        $this->enabled = $loggerEnabled;
    }

    /**
     * @var $logLevel Set to one of the constants: LOG_DEBUG, LOG_INFO, LOG_NOTICE,
     *                LOG_WARNING, LOG_ERR, LOG_CRIT, LOG_EMERG
     *                Default: LOG_DEBUG
     */
    protected $logLevel = LOG_DEBUG;
    public function setLoggerLevel($loggerLevel)
    {
        $this->logLevel = $loggerLevel;
    }

    /**
     * @param $permittedClasses Is either an array containing class names that we want to log, or is the string 'all'
     *                          which means we want to log everything from all classes.
     *                          Default: 'all'
     */
    protected $permittedClasses = 'all';
    public function setLoggerAllow($loggerAllow)
    {
        $this->permittedClasses = $loggerAllow;
    }

    /**
     * @param $blockedClasses Is either an array containing class names that we don't want to log, or is the string 'none'
     *                        which means that we want to log all classes
     *                        Default: 'none'
     */
    protected $blockedClasses = 'none';
    public function setLoggerDeny($loggerDeny)
    {
        $this->blockedClasses = $loggerDeny;
    }

    /**
     * Creates an instance of a simple PHP logger.
     *
     * @param boolean $loggerEnabled Used to disable logging based on an environment parameter
     *                                  Default: true
     * @param int   $loggerLevel   Set to one of the constants: LOG_DEBUG, LOG_INFO, LOG_NOTICE,
     *                                  LOG_WARNING, LOG_ERR, LOG_CRIT, LOG_EMERG
     *                                  Default: LOG_DEBUG
     * @param mixed   $loggerAllow   Is either an array containing class names that we want to log, or is the string 'all'
     *                                  which means we want to log everything from all classes.
     *                                  Default: 'all'
     * @param mixed   $loggerDeny    Is either an array containing class names that we don't want to log, or is the string 'none'
     *                                  which means that we want to log all classes
     *                                  Default: 'none'
     */
    public function __construct($loggerEnabled = true,
                                $loggerLevel = LOG_DEBUG,
                                $loggerAllow = 'all',
                                $loggerDeny = 'none')
    {
        $this->enabled          = $loggerEnabled;
        $this->logLevel         = $loggerLevel;
        $this->permittedClasses = $loggerAllow;
        $this->blockedClasses   = $loggerDeny;
    }

    /**
     * Returns true if the logging system is enabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Returns the current log level for this logger
     *
     * @return const
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    /**
     * Returns the name of the class that called the logger function
     *
     * @return void
     */
    protected function getCalleeClass()
    {

        if (PHP_VERSION_ID < 50205)
            $backtrace = debug_backtrace();
        else
            $backtrace = debug_backtrace(false);

        foreach ( $backtrace as $bt ) {
            // Making sure the class we're getting out of the backtrace is not a logger
            if (!is_a($this, $bt['class']) && strpos($bt['class'], 'Logger') === false) {
                return $bt['class'];
            }
        }

        return '';
    }

    /**
     * Returns the name of the method that called the logger function
     *
     * @return void
     */
    protected function getCalleeMethod()
    {

        if (PHP_VERSION_ID < 50205)
            $backtrace = debug_backtrace();
        else
            $backtrace = debug_backtrace(false);

        foreach ( $backtrace as $bt ) {
            // Making sure the class we're getting out of the backtrace is not a logger
            if (!is_a($this, $bt['class']) && strpos($bt['class'], 'Logger') === false) {
                return $bt['function'];
            }
        }

        return '';
    }

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
    public function debug($message)
    {
        return $this->log($message, LOG_DEBUG);
    }

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
    public function info($message)
    {
        return $this->log($message, LOG_INFO);
    }

    /**
     * Records a notice message
     *
     * Notice messages provide insight on the progress of any action.
     *
     * @param mixed $message The message to record in the log
     *
     * @return $message
     *
     * @throws LoggingException If log message write fails
     **/
    public function notice($message)
    {
        return $this->log($message, LOG_NOTICE);
    }

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
    public function warn($message)
    {
        return $this->log($message, LOG_WARNING);
    }

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
    public function error($message)
    {
        return $this->log($message, LOG_ERR);
    }

    /**
     * This function prints the {@link $message} to the PHP error log directly if it's a scalar value (int, float, string)
     * or, if it's non-scalar, print_r(...) is used to convert it to a string.  If the class was created with
     * {@link $enabled} = false, then logging is skipped and this function returns null.
     *
     * @param mixed  $message The message to record in the log
     * @param string $level   The log level (see defined constants above)
     *
     * @return $message
     *
     * @throws LoggingException If log message write fails
     **/
    public function log($message, $level = LOG_INFO)
    {
        if (!$this->enabled || $this->logLevel < $level) return null;

        if (null === $this->siteSlug) {
            $this->siteSlug = (string) $_SERVER['SITE']['slug'];
            if ($this->siteSlug === $_SERVER['CONTEXT']) {
                $this->siteSlug = $this->ApplicationContext->object('SiteService')->getAnchoredSite()->Slug;
            }

            // wire up additional properties for the log entry
            // todo: find out why the fucking Instantiator skips certain autowired properties,
            // methinks the multilogger or something else is loaded before half the rest of
            // the fucking DI magic graph is loaded, srsly, fuck you.
            if (null === $this->isSiteDeployment) {
                $this->isSiteDeployment = $this->ApplicationContext->property('isSiteDeployment');

                if (null === $this->isSiteDeployment) {
                    $this->isSiteDeployment = false;
                } else {
                    $this->deviceView = $this->ApplicationContext->property('deviceView');
                    $this->design = $this->ApplicationContext->property('design');
                }
            }
        }

        $klass = $this->getCalleeClass();

        if (is_array($this->blockedClasses) && in_array($klass, $this->blockedClasses)) return null;

        if ($this->permittedClasses === 'all' || (is_array($this->permittedClasses) && in_array($klass, $this->permittedClasses))) {
            $method = $this->getCalleeMethod();
            $output = is_scalar($message) ? $message : print_r($message, true);

            if (!isset($this->history[$klass]))
                $this->history[$klass] = array();

            $this->history[$klass][] = $output;

            $this->logLine("[{$this->environment}][{$this->siteSlug}][{$this->context}]" . ($this->isSiteDeployment ? "[{$this->deviceView}:{$this->design}]" : '')  . "[{$this->levels[$level]}][{$klass}][{$method}] $output");
            return $output;
        }

        return null;
    }

    /**
     * This function returns the current array of log message for a particular category.
     *
     * @param string $klass The category of log messages. If this parameter is omitted, then all log history is returned.
     *
     * @return array Array of strings containing sequential log messages for the given {@link $klass}.
     **/
    public function getLogHistory($klass = null)
    {
        if (empty($klass))
            return $this->history;

        if (!isset($this->history[$klass]))
            return array();

        return $this->history[$klass];
    }

    /**
     * This function clears the current array of log message for a particular category.
     *
     * @param string $klass The category of log messages. If this parameter is ommitted, then all history is cleared.
     *
     * @return void
     **/
    public function clearLogHistory($klass = null)
    {
        if (empty($klass))
            $this->history = array();

        if (!isset($this->history[$klass]))
            return;

        $this->history[$klass] = array();
    }
}


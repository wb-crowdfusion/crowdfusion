<?php
/**
 * FileLogger
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
 * @version     $Id: FileLogger.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * FileLogger
 *
 * @package     CrowdFusion
 */
class FileLogger extends Logger
{
    protected $logFile;
    public function setLoggerFile($loggerFile)
    {
        $this->logFile = $loggerFile;
    }

    /**
     * Used in conjunction with IoC Setters for backwards compatibility
     *
     * @param $loggerFile
     * @param $datesLocalTimeZone
     * @param bool $loggerEnabled
     * @param int $loggerLevel
     * @param string $loggerAllow
     * @param string $loggerDeny
     */
    public function __construct($loggerFile,
                                $datesLocalTimeZone,
                                $loggerEnabled = true,
                                $loggerLevel = LOG_DEBUG,
                                $loggerAllow = 'all',
                                $loggerDeny = 'none')
    {
        parent::__construct($loggerEnabled, $loggerLevel, $loggerAllow, $loggerDeny);

        if ($datesLocalTimeZone) {
            date_default_timezone_set($datesLocalTimeZone);
        } else {
            date_default_timezone_set('US/Eastern');
        }
        $this->logFile = $loggerFile;
    }

    protected function logLine($message)
    {
        FileSystemUtils::safeFilePutContents($this->logFile, '['.date('Y-m-d H:i:s').'] ' . $message . "\n", FILE_APPEND);
    }
}
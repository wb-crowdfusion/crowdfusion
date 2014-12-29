<?php
/**
 * Logger
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
 * Simple logging implementation.  This class will output log messages to the standard PHP error log using the
 * error_log(...) function.
 *
 * @package     CrowdFusion
 */
class Logger extends AbstractLogger
{
    protected $multiline = false;
    public function setErrorMultiline($multiline = false)
    {
        $this->multiline = StringUtils::strToBool($multiline);
    }

    protected function logLine($message)
    {
        if (!$this->multiline) {
            $message = str_replace("\n", "<br/>", $message);
        }
        error_log($message);
    }
}

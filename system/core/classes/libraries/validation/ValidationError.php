<?php
/**
 * ValidationError
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
 * @version     $Id: ValidationError.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * A validation error describes a form error that will be
 * displayed to the end user
 *
 * @package     CrowdFusion
 */
class ValidationError
{
    protected $errorCode;
    protected $defaultErrorMessage;

    /**
     * Creates the validation error object
     *
     * @param string $errorCode           An error code to identify the type of error
     * @param string $defaultErrorMessage A default error message to display if no others are found
     */
    public function __construct($errorCode, $defaultErrorMessage)
    {
        $this->errorCode = $errorCode;
        $this->defaultErrorMessage = $defaultErrorMessage;
    }

    /**
     * Returns the defaultErrorMessage
     *
     * @return string
     */
    public function getDefaultErrorMessage()
    {
        return $this->defaultErrorMessage;
    }

    /**
     * Returns the Error Code
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }


}
?>

<?php
/**
 * ValidationException
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
 * @version     $Id: ValidationException.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ValidationException
 *
 * @package     CrowdFusion
 */
class ValidationException extends Exception
{
    protected $errors;

    /**
     * Constructs a validation exception
     *
     * @param Errors $errors The validation errors
     */
    public function __construct(Errors $errors)
    {
        $this->errors = $errors;

        if (!$errors->hasErrors())
            parent::__construct('No errors');
        else
            parent::__construct($errors->toString());
    }

    /**
     * Returns the errors for this exception
     *
     * @return Errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
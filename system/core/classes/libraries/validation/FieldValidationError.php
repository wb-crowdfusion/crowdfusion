<?php
/**
 * FieldValidationError
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
 * @version     $Id: FieldValidationError.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * A field validation error is a validation error that
 * is associated with a field, so it contains
 * some information about that field
 *
 * @package     CrowdFusion
 */
class FieldValidationError extends ValidationError
{
    protected $fieldTitle;
    protected $fieldResolved;
    protected $failureCode;
    protected $value;

    /**
     * Create a ValidationError error that is associated with a form field.
     *
     * @param string $failureCode         The code or slug that identifies this error's failure cause
     * @param string $fieldResolved       The machine-readable error code that uniquely identifies the error
     * @param string $fieldType           Can be 'field', 'meta', or 'tag' to determine the type of error field
     * @param string $fieldTitle          The title or human readable name for the failing field
     * @param string $value               The value that was invalid
     * @param string $defaultErrorMessage A default error message to display if we can't
     *                                      Lookup another one using the failureCode
     */
    public function __construct($failureCode, $fieldResolved, $fieldType, $fieldTitle, $value, $defaultErrorMessage)
    {
        $errorCode = $fieldResolved . '.' . $failureCode;

        parent::__construct($errorCode, $defaultErrorMessage);

        $this->failureCode   = $failureCode;
        $this->fieldResolved = $fieldResolved;
        $this->fieldTitle    = $fieldTitle;
        $this->fieldType     = $fieldType;
        $this->value         = $value;

    }

    /**
     * Returns the value for fieldResolved
     *
     * @return string
     */
    public function getFieldResolved()
    {
        return $this->fieldResolved;
    }

    /**
     * Returns the fieldTitle
     *
     * @return string
     */
    public function getFieldTitle()
    {
        return $this->fieldTitle;
    }

    /**
     * Returns the fieldType.
     *
     * fieldType is one of the following: 'meta', 'field', or 'tag'
     *
     * @return string
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * Returns the failureCode
     *
     * @return string
     */
    public function getFailureCode()
    {
        return $this->failureCode;
    }

    /**
     * Returns the value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}

?>

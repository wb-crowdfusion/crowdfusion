<?php
/**
 * Errors
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
 * @version     $Id: Errors.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Errors is a class that holds a collection of ValidationError objects.
 * This is used in validation to hold details about form errors.
 *
 * @package     CrowdFusion
 */
class Errors
{
    protected $errors = array();

    /**
     * Reset the errors collection to 0.
     *
     * @return this
     */
    public function resetErrors()
    {
        $this->errors = array();
        return $this;
    }

    /**
     * Returns TRUE if we have at least one error
     *
     * @return boolean
     */
    public final function hasErrors()
    {
        return empty($this->errors) ? false : true;
    }

    /**
     * Returns a count of how many errors have been collected
     *
     * @return integer
     */
    public function getErrorCount()
    {
        return count($this->errors);
    }

    /**
     * Adds a "global" error. That is, an error that's not tied
     * to any particular field.
     *
     * @param string $errorCode           An error code that will identify this error.
     *                                      Almost like a slug for the error.
     *                                      Used to lookup the error code in the language files.
     * @param string $defaultErrorMessage The default error message to display if we can't
     *                                      find a translation for the error message in the
     *                                      language files.
     *
     * @return this
     */
    public function addGlobalError($errorCode, $defaultErrorMessage)
    {
        $this->errors[] = new ValidationError($errorCode, $defaultErrorMessage);
        return $this;
    }

    /**
     * Adds an error that is associated with a form field.
     *
     * @param string $failureCode         The code or slug that identifies this error's failure cause
     * @param string $fieldResolved       The machine-readable error code that uniquely identifies the error
     * @param string $fieldType           Can be 'field', 'meta', or 'tag' to determine the type of error field
     * @param string $fieldTitle          The title or human readable name for the failing field
     * @param string $value               The value that was invalid
     * @param string $defaultErrorMessage A default error message to display if we can't
     *                                      Lookup another one using the failureCode
     *
     * @return this
     */
    public function addFieldError($failureCode, $fieldResolved, $fieldType, $fieldTitle, $value, $defaultErrorMessage)
    {
        $this->errors[] = new FieldValidationError($failureCode, $fieldResolved, $fieldType, $fieldTitle, $value, $defaultErrorMessage);
        return $this;
    }

    /**
     * Adds a validation error to our collection of errors.
     *
     * @param ValidationError $error The error to add
     *
     * @return this
     */
    public function addError(ValidationError $error)
    {
        $this->errors[] = $error;
        return $this;
    }

    /**
     * Adds more than one error to the collection of errors
     *
     * @param array $errors Each object in the array must be of type ValidationError
     *
     * @return this
     */
    public function addErrors(array $errors)
    {
        foreach ($errors as $error) {
            if (!$error instanceof ValidationError)
                throw new Exception('Error was not instance of ValidationError: '.$error);
            if (!in_array($error, $this->errors))
                $this->errors[] = $error;
        }

        return $this;
    }

    /**
     * Returns our collection of errors
     *
     * @return array An array of ValidationError objects
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Determines if an error has occurred for the supplied field already
     *
     * @param $field Field name
     * @return boolean
     */
    public function hasFieldError($field)
    {
        foreach ((array)$this->errors as $err) {

            if ($err instanceof FieldValidationError) {
                $tfield = $err->getFieldResolved();
                if($tfield == $field)
                    return true;
            }
        }

        return false;
    }

    public function hasError($errorCode)
    {

        foreach ((array)$this->errors as $err) {
            if($err->getErrorCode() == $errorCode)
                return true;
        }

        return false;

    }

    /**
     * Extracts the errors from the collection as ValidationErrors into simple arrays
     *
     * @return array
     */
    public function getErrorsAsArray()
    {
        $errors = array();
        $errors['HasErrors'] = $this->hasErrors();
        $errors['ErrorCount'] = $this->getErrorCount();
        $errors['Errors'] = (array)$this->errors;

        /*
        $errors['ErrorString'] = '';
        $errors['FieldErrorString'] = '';
        $errors['GlobalErrorString'] = '';
        $errors['ErrorStringHTML'] = '';
        $errors['FieldErrorStringHTML'] = '';
        $errors['GlobalErrorStringHTML'] = '';

        */

        foreach ((array)$this->errors as $err) {

            if ($err instanceof FieldValidationError) {
                $field = $err->getFieldResolved();
                $errors['Has'.$field.'Error'] = true;
                // $errors[$field.'ErrorMessage'] = $err->getDefaultErrorMessage();
                // $errors[$field.'ErrorCode'] = $err->getErrorCode();
                $errors['ErrorFields'][] = $field;
                $errors['ErrorValues'][] = $err->getValue();

                // $errors['FieldErrorString'] .= $err->getDefaultErrorMessage()."\n";
                // $errors['FieldErrorStringHTML'] .= $err->getDefaultErrorMessage().'<br/>';
            } else {
                // $errors['GlobalErrorString'] .= $err->getDefaultErrorMessage()."\n";
                // $errors['GlobalErrorStringHTML'] .= $err->getDefaultErrorMessage().'<br/>';
            }

            $errors['ErrorCodes'][] = $err->getErrorCode();
            // $errors['ErrorMessages'][] = $err->getDefaultErrorMessage();
            // $errors['ErrorString'] .= $err->getDefaultErrorMessage()."\n";
            // $errors['ErrorStringHTML'] .= $err->getDefaultErrorMessage().'<br/>';
        }

        return $errors;

    }

    /**
     * Returns a string that's a combination of all errors
     *
     * @return string
     */
    public function toString()
    {
        $errorString = '';
        foreach ((array)$this->errors as $err) {
            $errorString .= $err->getDefaultErrorMessage().", ";
        }
        return substr($errorString, 0, -2);
    }

    /**
     * Throws an exception if we have at least one error
     *
     * @return void
     * @throws ValidationException if we have at least one error
     */
    public function throwOnError()
    {
        if($this->hasErrors())
            throw new ValidationException($this);
    }

    /**
     * Combines the errors from the Errors object passed into this object
     *
     * @param Errors $errors An errors object to combine with this.
     *
     * @return this updated with all errors from both objects
     */
    public function chain(Errors $errors)
    {
        $this->addErrors($errors->getErrors());

        return $this;
    }

    /**
     * Adds a field error if the {@link $value} given is empty or just whitespace
     *
     * The field error will be added with a failureCode of 'required'
     *
     * @param string $fieldResolved The machine readable field identifier
     * @param string $fieldType     'field', 'meta', or 'tag'
     * @param string $fieldTitle    The human readable field name
     * @param string $value         The value to check
     * @param string $message       (optional) A default error message to display if one cannot be located.
     *
     * @return this
     */
    public function rejectIfEmptyOrWhitespace($fieldResolved, $fieldType, $fieldTitle, $value, $message = '')
    {
        if ($value == null || strlen(trim($value)) == 0)
            $this->addFieldError('required', $fieldResolved, $fieldType, $fieldTitle, $value, $message==''?"$fieldTitle is required.":$message);

        return $this;
    }

    /**
     * Adds a field error if the {@link $value} given is empty
     *
     * The field error will be added with a failureCode of 'required'
     *
     * @param string $fieldResolved The machine readable field identifier
     * @param string $fieldType     'field', 'meta', or 'tag'
     * @param string $fieldTitle    The human readable field name
     * @param string $value         The value to check
     * @param string $message       (optional) A default error message to display if one cannot be located.
     *
     * @return this
     */
    public function rejectIfEmpty($fieldResolved, $fieldType, $fieldTitle, $value, $message = '')
    {
        if($value == null || strlen($value) == 0)
            $this->addFieldError('required', $fieldResolved, $fieldType, $fieldTitle, $value, $message==''?"$fieldTitle is required.":$message);

        return $this;
    }

    /**
     * Adds a field error to the specified field
     *
     * @param string $errorCode     The error code to use
     * @param string $fieldResolved The machine readable field identifier
     * @param string $fieldTitle    The human readable field name
     * @param string $value         The value to check
     * @param string $message       (optional) A default error message to display if one cannot be located.
     *
     * @return this
     */
    public function rejectField($errorCode, $fieldResolved, $fieldTitle, $value, $message = '')
    {
        $this->addFieldError($errorCode, $fieldResolved, 'field', $fieldTitle, $value, $message==''?"$fieldTitle is required.":$message);
        return $this;
    }

    /**
     * Adds a global error
     *
     * @param string $message   The global error message to add
     * @param string $errorCode The error code to use. Default: 'invalid'
     *
     * @return this
     */
    public function reject($message = '', $errorCode = 'invalid')
    {
        $this->addGlobalError($errorCode, $message==''?'Submission was invalid.':$message);
        return $this;
    }

    /**
     * Adds a field error if the {@link $value} given is empty
     *
     * The field error will be added with a failureCode of 'required'
     *
     * @param string               $fieldResolved The machine readable field identifier
     * @param string               $fieldType     'field', 'meta', or 'tag'
     * @param string               $fieldTitle    The human readable field name
     * @param string               $value         The value to check
     * @param ValidationExpression $ve            The ValidationExpression used to validate the value
     * @param string               $message       (optional) A default error message to display if one cannot be located.
     *
     * @return this
     */
    public function rejectIfInvalid($fieldResolved, $fieldType, $fieldTitle, $value, ValidationExpression $ve, $message='')
    {
        if (!$ve->isValid($value)) {
            $this->addFieldError($ve->getFailureCode(), $fieldResolved, $fieldType, $fieldTitle, $value,
                    $message==''?"$fieldTitle {$ve->getFailureMessage()}.":$message);
        }

        return $this;
    }

    /**
     * Validates the model object, rejecting the fields that fail validation
     *
     * @param ModelObject $model The object to validate
     *
     * @return void
     */
    public function validateModelObject(ModelObject $model)
    {
        $fields = $model->getPersistentFields();

        foreach ( $fields as $fieldName ) {

            $value         = $model->$fieldName;
            $fieldResolved = get_class($model).'.'.$fieldName;

            $this->rejectIfInvalid($fieldResolved,
                                   'field',
                                   $model->getFieldTitle($fieldName),
                                   $value,
                                   new ValidationExpression($model->getValidation($fieldName)));
        }
    }

}

?>

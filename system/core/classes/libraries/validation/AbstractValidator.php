<?php
/**
 * AbstractValidator
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
 * @version     $Id: AbstractValidator.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractValidator
 *
 * @package     CrowdFusion
 */
abstract class AbstractValidator implements ValidatorInterface
{
    protected $errors = null;

    /**
     * Constructs the validator
     */
    public function __construct()
    {
        $this->construct();
    }

    /**
     * Helper function for children.
     * Use this construct method instead of over-riding the __construct in all child classes.
     *
     * @return void
     */
    protected function construct()
    {
    }

    /**
     * This function is called before validation occurs
     *
     * @return void
     */
    protected function preValidate()
    {
        // TODO: Do we want to accept arguments here?
    }

    /**
     * This function is called immediately after validation
     *
     * @return void
     */
    protected function postValidate()
    {
        // TODO: Do we want to accept arguments here?
    }

    /**
     * This method is called to perform validation
     *
     * @param string $context        A string representing the name of the context
     *                                 in which the validation occurs. For example: 'add', 'edit', 'delete', etc.
     * @param mixed  $arg, $arg, ... Using func_get_args() these arguments will be passed to the validator function
     *                                  invoked by this function.
     *
     * @return array an array of Errors (or empty array)
     */
    public function validateFor($context)
    {

        $this->errors = new Errors();

        // create camelized method name
        $methodResolved = ActionUtils::parseMethod($context);

        // check method exists
        if(!method_exists($this, $methodResolved))
            throw new Exception('Method ['.$methodResolved.'] does not exist on class ['.get_class($this).']');

        $this->preValidate();

        // if(LOG_ENABLE) System::log(self::$logType, 'Executing method ['.$methodResolved.']');

        $arr = array_slice(func_get_args(), 1);

        // CALL THE METHOD
        call_user_func_array(array($this, $methodResolved), $arr);

        $this->postValidate();

        return $this->errors;
    }

    /**
     * Returns an array of all errors that have been recored
     *
     * @return array An array of Errors
     */
    public function &getErrors()
    {
        return $this->errors;
    }

}

?>

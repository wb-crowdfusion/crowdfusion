<?php
/**
 * ValidatorInterface
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
 * @version     $Id: ValidatorInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * A validator will perform the validation according to a context and return any Errors that have occured
 *
 * @package     CrowdFusion
 */
interface ValidatorInterface
{

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
    public function validateFor($context);

    /**
     * Returns an array of all errors that have been recored
     *
     * @return array An array of Errors
     */
    public function &getErrors();

}
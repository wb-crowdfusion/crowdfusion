<?php
/**
 * FiltererInterface
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
 * @version     $Id: FiltererInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * A Filterer provides methods that are used by the templates.
 * These methods provide logic and access that is not possible in a template.
 *
 * @package     CrowdFusion
 */
interface FiltererInterface
{
    /**
     * This function is used to handle the call to the filterer method.
     *
     * @param string $filtererName Name of the filter namespace, typically the class name
     * @param string $method       The filterer method to run
     * @param array  $params       An array of parameters for the filterer method
     * @param array  $locals       An array of locals
     * @param array  $globals      An array of globals
     *
     * @return string The result from the filterer method that will be inserted into the template.
     */
    public function handleFilter($filtererName, $method, $params, $locals, $globals);

    /**
     * Returns true if the filter call contains template code to be processed
     *
     * @return boolean
     */
    public function isAllowTemplateCode();

}

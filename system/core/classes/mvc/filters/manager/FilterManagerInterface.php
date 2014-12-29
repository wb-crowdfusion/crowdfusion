<?php
/**
 * FilterManagerInterface
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
 * @version     $Id: FilterManagerInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * A FilterManager encapsulates the logic that is used to locate and execute the filterer classes.
 *
 * @package     CrowdFusion
 */
interface FilterManagerInterface
{


    /**
     * Returns an instance of the class identified by the {@link $filtererName}.
     *
     * In this function, the resultant class name is:
     * {$filtererName}{$context}Filterer
     *
     * For example, with context = 'web' and filtererName = 'Bacon'
     * an instance of the class 'BaconWebFilterer' would be returned from this function
     *
     * @param string $filtererName The identifier for the filter to instantiate.
     *
     * @return FiltererInterface
     */
    public function getFiltererByName($filtererName);


    /**
     * Invokes the method specified by {@link $filterCall} (which also specifies the class of the filterer to load).
     *
     * @param string $filterCall The filterer to load and method to run
     * @param array  $params     An array of parameters for the filterer method
     * @param array  $locals     An array of locals
     * @param array  $globals    An array of globals
     *
     * @return string The result from the filterer method that will be inserted into the template.
     */
    //public function invokeFilter($filterCall, $params, $locals, $globals);

}
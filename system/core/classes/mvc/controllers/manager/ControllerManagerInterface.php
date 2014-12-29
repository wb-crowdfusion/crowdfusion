<?php
/**
 * ControllerManagerInterface
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
 * @version     $Id: ControllerManagerInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Controller Manager - Manages controllers and the invocation of the actions and datasources
 *
 * @package     CrowdFusion
 */
interface ControllerManagerInterface
{

    /**
     * Invokes the action specified by {@link $action}.
     *
     * @param string $action_string the Action string that specifies the action to run
     *
     * @return View the view object that results from the invoked action
     */
    public function invokeAction($action);

    /**
     * Invokes the datasource specified by {@link $datasource}.
     *
     * @param string $datasource The datasource to invoke
     * @param array  &$preloadedData       The data for the datasource
     * @param array  &$templateVariables    The locals for the datasource
     *
     * @return mixed The data returned from the datasource (usually an array)
     */
    public function invokeDatasource($datasource, array &$preloadedData, array &$templateVariables);


}
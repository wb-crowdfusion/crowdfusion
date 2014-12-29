<?php
/**
 * Manages controllers and the invocation of the actions and datasources
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
 * @version     $Id: ControllerInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Manages controllers and the invocation of the actions and datasources
 *
 * @package     CrowdFusion
 */
interface ControllerInterface
{
    /**
     * Handles the action specified
     *
     * @param string $name the controller name, without context, ex. "members"
     * @param string $actionName the action name
     *
     * @return ControllerInterface the instance $this, for chaining
     */
    public function handleAction($name, $actionName);

    /**
     * Handles the datasource specified
     *
     * @param string $name the controller name, without context, ex. "members"
     * @param string $datasourceName The datasource name
     *
     * @return ControllerInterface the instance $this, for chaining
     */
    public function handleDatasource($name, $datasourceName, array $preloadedData, array &$templateVariables);

    public function getView();

    public function getData($datasource = '');

}
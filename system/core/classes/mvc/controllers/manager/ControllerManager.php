<?php
/**
 * ControllerManager
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
 * @version     $Id: ControllerManager.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Controller Manager - Manages controllers and the invocation of the actions and datasources
 *
 * @package     CrowdFusion
 */
class ControllerManager implements ControllerManagerInterface
{

    protected $ApplicationContext;
    protected $context;

    /**
     * Creates the controller manager. Autowired
     *
     * @param ApplicationContext $ApplicationContext autowired
     * @param string             $context           autowired
     */
    public function __construct(ApplicationContext $ApplicationContext, $context)
    {
        $this->ApplicationContext = $ApplicationContext;
        $this->context           = $context;
    }

    /**
     * Returns an instance of the controller identified by {@link $controllerName}.
     *
     * The class name is built from the {@link $controllerName}, the {@link $context} and the word 'Controller'
     *
     * For example, if we're in the 'web' context and try to get the 'awesome' controller,
     * an instance of the class AwesomeWebController would be returned from this function.
     *
     * @param string $controllerName The name of the controller to get
     *
     * @return object An instance of the controller class
     */
    protected function getControllerByName($controllerName)
    {
        if(trim($controllerName) == "")
            throw new Exception('No controller class specified');

        $controllerNameResolved = ucfirst($controllerName).ucfirst(strtolower($this->context)).'Controller';

        $controller = $this->ApplicationContext->object($controllerNameResolved);

        if(!$controller instanceof ControllerInterface)
            throw new Exception('Controller class does not implement ControllerInterface: '.get_class($controller));


        return $controller;
    }

    /**
     * Invokes the action specified by {@link $action_string}.
     *
     * The {@link $action_string} can be split into controller and action
     * through ActionUtils::parseActionDatasource().
     *
     * @param string $action_string the Action string that specifies the action to run
     *
     * @return View the view object that results from the invoked action
     */
    public function invokeAction($action_string)
    {
        list($controllerName, $action) = ActionUtils::parseActionDatasource($action_string);

        $controller = $this->getControllerByName($controllerName);

        $view = $controller->handleAction($controllerName, $action)->getView();

        return $view;
    }

    /**
     * Invokes the datasource specified by {@link $datasource}.
     *
     * The {@link $datasource} can be split into controller and datasource
     * through ActionUtils::parseActionDatasource().
     *
     * @param string $datasource The datasource to invoke
     * @param array  $data       The data for the datasource
     * @param array  &$locals    The locals for the datasource
     *
     * @return mixed The data returned from the datasource (usually an array)
     */
    public function invokeDatasource($datasource, array &$preloadedData, array &$templateVariables)
    {
        list($controllerName, $datasource) = ActionUtils::parseActionDatasource($datasource);

        $controller = $this->getControllerByName($controllerName);

        $data = $controller->handleDatasource($controllerName, $datasource, $preloadedData, $templateVariables)->getData();

        return $data;
    }

}
<?php
/**
 * Provides basic functionality to handle actions and datasources.
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
 * @version     $Id: AbstractController.php 870 2012-04-10 13:05:34Z clayhinson $
 */

/**
 * Provides basic functionality to handle actions and datasources.
 *
 * @package     CrowdFusion
 */
abstract class AbstractController implements ControllerInterface {

    protected $templateVars = array(); // current scope format variables

    protected $currentActionOrDatasource = null;
    protected $currentAction = null;
    protected $currentDatasource = null;
    protected $originalAction = null;
    protected $currentMethod = null;

    protected $Logger = null;
    protected $Request = null;
    protected $Response = null;
    protected $RequestContext = null;
    protected $Session = null;

    protected $isCommandLine = false;

    protected $TransactionManager = null;
    protected $Nonces = null;
    protected $Permissions = null;
    protected $DateFactory = null;
    protected $ModelMapper = null;

    protected $params = array(); // request parameters
    protected $rawParams = array(); // raw request parameters

    protected $errors = null;

    protected static $data;

    // used to store the active datasource
    // allows you to handle a datasource and then immediately call getData()
    protected $activeDatasource = false;

    // stores the action datasource for replacing it later
    // element actions only mess with their own datasources
    protected static $actionBoundDatasource;

    protected $actionResult = null;

    // default action buttons, can be overridden
    protected $saveKey = 'save';

    public function __construct(LoggerInterface $Logger, Request $Request, RequestContext $RequestContext,
                                TransactionManagerInterface $TransactionManager, Nonces $Nonces, Permissions $Permissions,
                                DateFactory $DateFactory, ModelMapper $ModelMapper, Session $Session, Response $Response,
                                $isCommandLine)
    {

        $this->Logger = $Logger;
        $this->Request = $Request;
        $this->Response = $Response;
        $this->RequestContext = $RequestContext;
        $this->TransactionManager = $TransactionManager;
        $this->Permissions = $Permissions;
        $this->Nonces = $Nonces;
        $this->DateFactory = $DateFactory;
        $this->ModelMapper = $ModelMapper;
        $this->Session = $Session;

        $this->isCommandLine = $isCommandLine;

        $this->params = $Request->getParameters();
        $this->rawParams = $Request->getRawParameters();

    }


    /* BEGIN HOOKS */
    protected function preAction()      {return true;}
    protected function postAction()     {return true;}
    protected function preDatasource()  {return true;}
    protected function postDatasource() {return true;}
    protected function referenceData()  {return array();}
    /* END HOOKS */


    public function handleAction($name, $method)
    {
        $this->name = $name;

        // initialize the Errors object
        $this->errors = new Errors();

        // reset the actionBoundDatasource
        self::$actionBoundDatasource = null;

        // set the current action
        $this->originalAction = ActionUtils::createActionDatasource($name, $method);

        // get basics
        $override_method = $this->RequestContext->getControls()->getControl('action_override_method');

        $methodDashed = $method;
        if($override_method && $override_method != null)
            $methodDashed = $override_method;

        // construct method and check permissions
        $action = ActionUtils::createActionDatasource($name,$methodDashed);

        //if(LOG_ENABLE) System::log(self::$logType, 'Action: ['.$action.']');

        $this->currentAction = $action;
        $this->currentActionOrDatasource = $action;

        // create camelized method name
        $methodResolved = ActionUtils::parseMethod($methodDashed);

        $this->currentMethod = $methodResolved;

        // check method exists
        if(!method_exists($this, $methodResolved)) {
            throw new MethodNotFoundException('Method ['.$methodResolved.'] does not exist on class ['.get_class($this).']');
        }

        $this->view = new View();

        /////////////////////////////////

        $this->handleActionInternal($methodResolved);

        /////////////////////////////////

        $data = $this->getView() != null && $this->getView()->getData() != null?$this->getView()->getData() : array();

        // bind errors to the templateVars
        if($this->errors->hasErrors()) {
            $data = array_merge($data, $this->errors->getErrorsAsArray());
        }

        if($this->getView() != null)
            $this->getView()->setData(array_merge( $this->templateVars, $data ));

        return $this;
    }

    protected function handleActionInternal($method)
    {
        /**
        *  Each controller action is scoped as a DB transaction.
        *  Any normal deadlocks will be automatically retried.
        *  Any 'Exception' thrown from the action method should roll back the transaction;
        *     this is left to the descendants of this class.
        */

        try
        {
            // begin transaction
            if($this->RequestContext->getControls()->isActionExecuting())
                $this->TransactionManager->begin();

            $this->preAction();

            // if (LOG_ENABLE) System::log(self::$logType, 'Executing method ['.$method.']');

            $this->view = $this->$method();

            $this->postAction();

            if($this->errors->hasErrors())
                throw new Exception('Method ['.$method.'] caused errors that were not thrown: ['.$this->errors->toString().']');

            // success, commit transaction
            if($this->RequestContext->getControls()->isActionExecuting() && $this->TransactionManager->isTransactionInProgress())
                $this->TransactionManager->commit();

        } catch(Exception $e) {

            if($this->isCommandLine)
                echo $e->getMessage()."\n";

            $this->Logger->debug($e->getMessage());

            if($this->RequestContext->getControls()->isActionExecuting() && $this->TransactionManager->isTransactionInProgress())
                $this->TransactionManager->rollback();
            throw $e;
        }

    }


    // this is used by the RenderModule when loading data from an element
    public function handleDatasource($name, $method, array $preloadedData, array &$templateVariables)
    {
        $this->name = $name;

        $datasource = ActionUtils::createActionDatasource($name,$method);

        // set the variables for use
        $this->templateVars =& $templateVariables;

        $this->currentDatasource = $datasource;

        // set the current datasource
        $this->currentActionOrDatasource = $datasource;

        $methodResolved = ActionUtils::parseMethod($method);

        $this->currentMethod = $methodResolved;

        //if(LOG_ENABLE) System::log(self::$logType, 'Datasource: ['.$datasource.']');

        // if we did an action and the datasource is already set, use it
        if(self::$actionBoundDatasource == $datasource)
            return $this;

        if(!method_exists($this, $methodResolved))
            throw new Exception('Method ['.$methodResolved.'] does not exist on class ['.get_class($this).']');

        // clear the data first
        $this->setData(null);

        // set preloaded data (from Renderer)
        if(!empty($preloadedData))
            $this->setData($preloadedData);

        $this->preDatasource();

        //if(LOG_ENABLE) System::log(self::$logType, 'Executing method ['.$methodResolved.']');

        $result = null;

        $result = $this->$methodResolved();

        if($result == null)
            $result = array();

        // bind to datasource
        $this->setData($result);

        $this->postDatasource();

        return $this; // for chaining
    }


    /* GETTERS */

    protected function getCurrentActionOrDatasource()   {return $this->currentActionOrDatasource;}
    protected function getCurrentDatasource()           {return $this->currentDatasource;}
    protected function getCurrentAction()               {return $this->currentAction;}
    protected function getCurrentMethod()               {return $this->currentMethod;}
    protected function getName()                        {return $this->name;}


    /* DATASOURCE METHODS */

    protected function setData($data, $datasource = '', $isActionBoundDatasource = false)
    {
        if(empty($datasource) && !empty($this->currentDatasource))
            $datasource = $this->currentDatasource;

        if(!is_array($data) && !empty($data))
            $data = array($data);

        if($isActionBoundDatasource) {
            self::$actionBoundDatasource = $datasource;

            // NOTE: referenceData always overrides data
            if(count($refData = (array)$this->referenceData()) > 0)
                $data = ArrayUtils::arrayMultiMergeKeys($data, $refData);
        }

        self::$data[$datasource] = $data;
    }

    public final function getData($datasource = '')
    {
        if(empty($datasource) && !empty($this->currentDatasource))
            $datasource = $this->currentDatasource;

        if(!isset(self::$data[$datasource]))
            return array();

        return self::$data[$datasource];
    }


    /* ACTION METHODS */
    public final function getView()
    {
        return $this->view;
    }

    protected function getErrors()
    {
        return $this->errors;
    }


    /* CHECK FUNCTIONS */

    protected function checkPermission($permission, $siteSlug = null)
    {
        if (!$this->Permissions->checkPermission($permission, $siteSlug))
            throw new Exception('Permission denied to action ['.$this->currentActionOrDatasource.']');

        //if(LOG_ENABLE) System::log(self::$logType, 'Passed permission check: ['.$this->currentActionOrDatasource.']');
    }

    /* BUTTONS */

    protected function buttonPushed($button = null)
    {
        if($button === null)
            return $this->RequestContext->getControls()->getControl('action_executing') != null;

        $buttonPushed = $this->RequestContext->getControls()->getControl('action_button');
        return $button == $buttonPushed;
    }

    protected function saveButtonPushed()
    {
        return $this->buttonPushed($this->saveKey);
    }


    /* VIEW FUNCTIONS */

    protected function formView()
    {
        // get form view
        $formView = $this->RequestContext->getControls()->getControl('action_form_view');

        if($formView == null || empty($formView)) {
            $formView = $this->RequestContext->getControls()->getControl('view');
        }

        return $formView;
    }

    protected function successView($forceRedirect = false)
    {
        $successView = $this->RequestContext->getControls()->getControl('action_success_view');

        if($successView == null || empty($successView)) {
            $successView = $this->Request->getReferrer();

            if(empty($successView))
                $successView = '/';

            $successView = 'redirect:'.$successView;
        }

        // ignore original_referer
        if($successView != 'original_referer' && substr(trim($successView),0,9) != 'redirect:' && $forceRedirect)
            $successView = 'redirect:/'.$successView;

        return $successView;
    }


    /* TEMPLATE VARIABLES */

    /**
     * Used in datasource methods
     *
     * @param $name
     * @param $value
     * @return void
     */
    protected function setTemplateVariable($name, $value)
    {
        $this->templateVars[$name] = $value;
    }

    /**
     * Used in datasource methods
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getTemplateVariable($name, $default = null)
    {
        return array_key_exists($name, $this->templateVars)?$this->templateVars[$name]:$default;
    }

    /**
     * Used in datasource methods
     *
     * @return array
     */
    protected function getTemplateVariables()
    {
        return $this->templateVars;
    }

    /**
     * Used in datasource methods, throws ControllerException when value is missing
     *
     * @param $name
     * @return mixed
     */
    protected function getRequiredTemplateVariable($name)
    {
        if(!array_key_exists($name, $this->templateVars))
            throw new ControllerException('Required template variable ['.$name.'] is missing');

        return $this->templateVars[$name];
    }

    /**
     * Used in datasource methods
     *
     * @param $name
     * @return void
     */
    protected function removeTemplateVariable($name)
    {
        unset($this->templateVars[$name]);
    }


    protected function passthruTemplateVariable(&$dto, $variableName, $default = null) {
        if($this->getTemplateVariable($variableName, $default) !== null && $this->getTemplateVariable($variableName, $default) != '')
            $dto->setParameter($variableName, $this->getTemplateVariable($variableName, $default));
    }



    /* ACTION DATASOURCE */

    protected function bindToActionDatasource(array $data)
    {
        $datasource = strtolower($this->RequestContext->getControls()->getControl('action_datasource'));

        if(empty($datasource))
            throw new Exception('No action_datasource was set');

        $this->setData($data, $datasource, true);
    }


}
<?php
/**
 * Top level functionality required to handle filter calls.
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
 * @version     $Id: AbstractFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Top level functionality required to handle filter calls.
 *
 * It's not an abstract class, but a common parent that provides it's children with useful filterer methods.
 *
 * @package     CrowdFusion
 */
abstract class AbstractFilterer implements FiltererInterface
{

    protected $locals;
    protected $globals;
    protected $params;
    protected $RequestContext;

    protected $currentMethod;
    protected $currentFilter;

    protected $allowTemplateCode = false;


    /**
     * Autowired constructor.
     *
     * @param RequestContext $RequestContext Autowired
     */
    public function __construct(RequestContext $RequestContext)
    {
        $this->RequestContext = $RequestContext;
    }

    /**
     * Returns true if the filter returns template code to be processed
     *
     * @return bool
     */
    public function isAllowTemplateCode()
    {
        return $this->allowTemplateCode;
    }

    /**
     * Informs the invoking class that the filter result contains template code
     *
     * @return self
     */
    protected function allowTemplateCode()
    {
        $this->allowTemplateCode = true;
        return $this;
    }

    /**
     * Returns an array of globals set for the filterer
     *
     * @return array
     */
    protected function getGlobals()
    {
        return $this->globals;
    }

    /**
     * Returns an array of locals set for the filterer
     *
     * @return array
     */
    protected function getLocals()
    {
        return $this->locals;
    }

    /**
     * Returns the value for a global by {@link $name}
     *
     * @param string $name The name of the global
     * @param mixed $default
     * @return mixed
     */
    protected function getGlobal($name, $default = null)
    {
        return array_key_exists($name, $this->globals)?$this->globals[$name]:$default;
    }

    /**
     * Returns the value for a local by {@link $name}
     *
     * @param string $name The name of the local
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getLocal($name, $default = null)
    {
        return array_key_exists($name, $this->locals)?$this->locals[$name]:$default;
    }

    /**
     * Returns the value for a parameter by {@link $name}
     *
     * @param string $name The name of the parameter
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getParameter($name, $default = null)
    {
        return array_key_exists($name, $this->params)?$this->params[$name]:$default;
    }

    /**
     * Returns the value for a parameter or throws an exception is not found
     *
     * @param string $name The name of the parameter
     *
     * @return mixed
     */
    protected function getRequiredParameter($name)
    {
        if(!array_key_exists($name, $this->params))
            throw new FiltererException('Required parameter ['.$name.'] is missing on filter call ['.$this->currentFilter.']');

        return $this->params[$name];
    }

    /**
     * Returns an array of parameters
     *
     * @return array
     */
    protected function getParameters()
    {
        return $this->params;
    }

    /**
     * Returns the default method. This is left to the child classes to actually implement.
     *
     * @return string
     */
    protected function getDefaultMethod()
    {
    }

    /* HOOKS */

    /**
     * This function is executed before any filter is handled/run.
     * Child classes can use this to setup their filter environment.
     *
     * @param array $params An array of all the params passed to the filterer element
     *
     * @return mixed Can be anything
     */
    protected function preFilter($params)
    {
    }

    /**
     * This function is executed after any filter is handled/run.
     * Child classes can use this to tear down their filter environment.
     *
     * @param array  $params  The parameters passed to the filter
     * @param string &$result The result string from the filter
     *
     * @return void
     */
    protected function postFilter($params, &$result)
    {
    }

    /**
     * This function is used to handle the call to the filterer method.
     * It fires preHandle, the specified filterer method, then postHandle.
     *
     * It's this function that makes it required for all filterer classes to be a child
     * of this class (unless they implement their own version of course.)
     *
     * @param string $filtererName Name of the filter namespace, typically the class name
     * @param string $method       The filterer method to run
     * @param array  $params       An array of parameters for the filterer method
     * @param array  $locals       An array of locals
     * @param array  $globals      An array of globals
     *
     * @return string The result from the filterer method that will be inserted into the template.
     */
    public function handleFilter($filtererName, $method, $params, $locals, $globals)
    {
        $this->allowTemplateCode = false;

        $this->locals  = $locals;
        $this->globals = $globals;
        $this->params  = $params;

        //empty string given for method, use default method
        if(strlen($method) == 0 && $this->getDefaultMethod() != null)
            $methodResolved = $this->getDefaultMethod();
        else if(strlen($method) > 0)
            $methodResolved = ActionUtils::parseMethod($method);
        else
            throw new Exception('No filter method specified and default method does not exist on class ['.get_class($this).']');

        $this->currentMethod = $methodResolved;
        $this->currentFilter = $filtererName.(strlen($method)==0?'':'-'.$method);

        if(!method_exists($this, $methodResolved))
            throw new Exception('Method ['.$methodResolved.'] does not exist on class ['.get_class($this).']');

        $this->preFilter($params);

        $result = $this->$methodResolved($params);

        $this->postFilter($params, $result);

        return $result;
    }

}

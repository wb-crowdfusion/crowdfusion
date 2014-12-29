<?php
/**
 * Instantiator
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
 * @version     $Id: Instantiator.php 2012 2010-02-17 13:34:44Z ryans $
 */
//$GLOBALS['depth'] = 0;
/**
 * Instantiator
 *
 * @package     CrowdFusion
 */
class Instantiator {

    private static $currentlyInstantiating = array();

//    private $objectName = null;
    public $className = null;
    private $definition = null;
    private $applicationContext = null;

    public function __construct($definition, ApplicationContext $applicationContext) {

//      $this->objectName = $objectName;

        if(is_string($definition))
            $this->definition = array('class' => $definition);
        else
            $this->definition = $definition;

        if(empty($this->definition['class']))
            throw new ApplicationContextException('Missing class definition for object : '.print_r($this->definition, true));

        $this->className = $this->definition['class'];
        if (is_null($this->className))
            throw new ApplicationContextException('No class name found: '.$this->className);

        $this->applicationContext = $applicationContext;

        $this->definition = $this->autowireDefinition($this->className, $this->definition);

    }

    public function getNewDefinition()
    {
        return $this->definition;
    }


    protected function autowireDefinition($className, $definition)
    {


//      echo "<pre>".$reflectionClass."</pre>";
//      echo "<pre>";print_r($this->definition);echo "</pre>";

        $autowire = !array_key_exists('autowire', $definition) || $definition['autowire'] == 'true';

        if($autowire) {

            $reflectionClass = new ReflectionClass($className);

//            $definition['doccomment'] = $reflectionClass->getDocComment();


            if(empty($definition['constructor-args'])) {
                $constructorMethod = $reflectionClass->getConstructor();
                if(!empty($constructorMethod)) {

                    $constructorParams = $constructorMethod->getParameters();
                    if(!empty($constructorParams)) {
                        foreach($constructorParams as $param) {
                            $setTarget = $param->getName();

                            if($setTarget == 'ApplicationContext' || $this->applicationContext->objectExists($setTarget) || $this->applicationContext->propertyExists($setTarget)) {
                                // save for future instances
                                $definition['constructor-args'][] = array('autoref' => $setTarget);
                            } else {
                                if ($param->isDefaultValueAvailable()) {
                                    $definition['constructor-args'][] = array('default' => $param->getDefaultValue());
                                } else {
                                    break;
                                }
                            }

                        }
                    }
                }
            }

            // set properties
            $properties = array();
            if(isset($definition['properties'])) {
                $properties = $this->_getTypedValue($definition['properties']);
            }

            $methods = $reflectionClass->getMethods();

            foreach((array)$methods as $method) {
                $methodName = $method->getName();
                if(substr($methodName, 0, 3) != 'set' || strtolower($methodName) == 'set')
                    continue;

                $setTarget = substr($methodName, 3);

                // ignore already set properties
                if(array_key_exists($setTarget, $properties) || array_key_exists(strtolower(substr($setTarget,0,1)).substr($setTarget,1), $properties)) continue;

                $prop = false;
                if($setTarget == 'ApplicationContext' || $this->applicationContext->objectExists($setTarget) || ($prop = $this->applicationContext->propertyExists($setTarget))) {
                    if($prop == true)
                        $setTarget = strtolower(substr($setTarget,0,1)).substr($setTarget,1);

                    // save for future instances
                    $definition['properties'][$setTarget] = array('autoref' => $setTarget);
                }
            }

            // we've already looked into the class structure, no need to autowire again
            $definition['autowire'] = false;
//            error_log('Autowired: '.$className);

        }

        return $definition;
    }


    public function instantiate() {
        if (!class_exists($this->className))
            throw new ApplicationContextException('Class does not exist: '.$this->className);

//        $start = microtime(TRUE);

        $instance = false;
        $class = $this->className;

        if(in_array($class, self::$currentlyInstantiating))
            throw new ObjectCurrentlyInCreationException('Error constructing object of class ['.$class.']: class is currently in construction, possible circular reference');

        try {

            self::$currentlyInstantiating[] = $class;

            // instantiate object
            if (!empty($this->definition['constructor-args'])) {
                $reflectionClass = new ReflectionClass($class);
                $parameters = $this->definition['constructor-args'];
                if(count($parameters) == 0)
                    $instance = new $class();
                else
                    $instance = $reflectionClass->newInstanceArgs($this->_getTypedValue($parameters));
            } else if(!empty($this->definition['factory-method'])) {
                $reflectionClass = new ReflectionClass($class);
                $factoryMethod = $this->definition['factory-method'];
                if(!$reflectionClass->hasMethod($factoryMethod))
                    throw new ApplicationContextException('Cannot create instance of class using factory-method ['.$factoryMethod.'], method does not exist');
                $reflFactoryMethod = $reflectionClass->getMethod($factoryMethod);
                if(!$reflFactoryMethod->isStatic())
                    throw new ApplicationContextException('Cannot use non-static factory-method ['.$factoryMethod.'] to create object');

                $instance = $reflFactoryMethod->invoke(null);
            } else {
                $instance = new $class();
            }

            // set properties
            if(isset($this->definition['properties'])) {
                $properties = $this->_getTypedValue($this->definition['properties']);
                foreach((array)$properties as $name => $value) {
                    $methodName = 'set'.ucfirst($name);
                    $instance->$methodName($value);
                }
            }

            // invoke methods
            if (isset ($this->definition['invokes'])) {
                foreach ($this->definition['invokes'] as $value) {
                    if(empty($value['name']))
                        throw new ApplicationContextException('Unabled to invoke method on class ['.$class.'] without name attribute');
                    call_user_func_array(array(&$instance,$value['name']), (array)$this->_getTypedValue(!empty($value['method-args'])?$value['method-args']:array()));
                }
            }

            // initialize-method
            if (isset ($this->definition['initialize-method']))
                call_user_func(array(&$instance,$this->definition['initialize-method']  ));

        }catch(ObjectCurrentlyInCreationException $cice) {
            throw new ApplicationContextException('Cannot create class ['.$class.']: '.$cice->getMessage());
        }

        $k = array_search($class, self::$currentlyInstantiating);
        unset(self::$currentlyInstantiating[$k]);

//        error_log(str_repeat('    ', $GLOBALS['depth']).$class.': '.((microtime(TRUE) - $start)*1000).'ms');

        return $instance;
    }

    protected function getAutowiredValue($setTarget) {

        if($setTarget == 'ApplicationContext') //magic reference to application context
            return $this->applicationContext;
        if($this->applicationContext->objectExists($setTarget)){
//            ++$GLOBALS['depth'];
            $obj = ($this->applicationContext->object($setTarget));
//            --$GLOBALS['depth'];
            return $obj;
        }else if($this->applicationContext->propertyExists($setTarget)) {
            return ($this->applicationContext->property($setTarget));
        }

        //return false;
        throw new ApplicationContextException('Cannot autowire value ['.$setTarget.']');
    }

    public function _getTypedValue(array $array) {

        # value is a string
        if (array_key_exists('value', $array)) {
//          $value = json_decode($array['value']);
            $value = $array['value'];
            if (strtolower($value) == 'null') {
                $value = NULL;
            } elseif (preg_match ('/^[0-9]+$/', $value)) {
                $value = (int)$value;
            } elseif (in_array(strtolower($value),array('true', 'on', '+', 'yes', 'y'))) {
                $value = TRUE;
            } elseif (in_array(strtolower($value), array('false', 'off', '-', 'no', 'n'))) {
                $value = FALSE;
            } elseif (is_numeric($value)) {
                $value = (float)$value;
            }
        } else if(array_key_exists('autoref', $array)) {
            $objectOrProperty = $array['autoref'];
            $value = $this->getAutowiredValue($objectOrProperty);
        } else if(array_key_exists('ref', $array)) {
            $object = $array['ref'];
            if($object == 'ApplicationContext') //magic reference to application context
                $value =& $this->applicationContext;
            else {
//                ++$GLOBALS['depth'];
                $value = $this->applicationContext->object($object);
//                --$GLOBALS['depth'];
            }
            if ($value == NULL)
                throw new ApplicationContextException('Undefined object: ' . $object);
        } else if(array_key_exists('property', $array)) {
            if(!$this->applicationContext->propertyExists($array['property']))
              throw new ApplicationContextException('Property not found: '.$array['property']);
            $value = $this->applicationContext->property($array['property']);
        } else if(array_key_exists('constant', $array)) {
            $value = constant($array['constant']);
            if(is_null($value))
                throw new ApplicationContextException('Constant not found: '.$array['constant']);
        } else if(array_key_exists('default', $array)) {
            $value = $array['default'];
        } else if(array_key_exists('array', $array)) {
            $value = array_map(array(&$this, '_getTypedValue'), $array['array']); // recurse
        } else if (is_array($array)) {
            $value = array_map(array(&$this, '_getTypedValue'), $array); // recurse
        }

        return $value;
    }


}

<?php
/**
 * Object
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
 * @version     $Id: Object.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Object provides an accessible method for storing fields.
 * Objects can be treated like an array and provide "magic" helpers to allow
 * accessing field as if they were public variables of the class.
 *
 * Read and write access is standardized through the optional
 * set_$field() and get_$field() methods. Regardless of how the field is accessed,
 * it's value will come through these functions.
 *
 * @package     CrowdFusion
 */
abstract class Object implements ArrayAccess, Serializable, JsonSerializable
{
    /**
     * Define the $fields in your object to define what fields will be on the object
     *
     * @var string
     */
    protected $fields = array();

    /**
     * Sets the $fields from the array passed.
     *
     * @param array $fields A key => value array where the key is the name of the field to set.
     */
    public function __construct(array $fields = array())
    {
        $this->setFromArray($fields);
    }

    /**
     * Sets the fields on the object.
     *
     * @param array $fields array of fields returned from SQL select result set
     *
     * @return void
     */
    public function setFromArray(array $fields)
    {
        foreach($fields as $key => $value)
        {
                $this->fields[$key] = $value;
                unset($key);
                unset($value);
        }
    }

    /**
     * Fetches the array of fields on the object
     *
     * @return array array of fields to be used by the modules / same as SQL select result set
     */
    public function toArray()
    {
        return $this->fields;
    }

    /**
     * @link http://php.net/manual/en/class.jsonserializable.php
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Magic method that will call set{@link $link}({@link $value})
     *
     * This method is invoked when setting fields directly on an object.
     *
     * For example:
     *  $object->foo = 'bar' becomes __set('foo', 'bar')
     *
     * @param string $name  Name of the field to set
     * @param string $value Value to set
     *
     * @return void
     */
    public function __set($name, $value)
    {
        call_user_func(array($this, 'set'.$name), $value);
    }

    /**
     * Magic method that calls get{@link $link}()
     *
     * This method is invoked when accessing object params like $object->foo.
     *
     * For example:
     *  $object->foo becomes __get('foo')
     *
     * @param string $name Field name to get
     *
     * @return mixed
     */
    public function __get($name)
    {
        return call_user_func(array($this, 'get'.$name));
    }

    /**
     * Magic method that determines if the field specified by {@link $name} has a value
     *
     * Used for things like isset($object->foo)
     *
     * @param string $name The field name to check
     *
     * @return boolean true the field is not null
     */
    public function __isset($name)
    {
        return call_user_func(array($this, 'get'.$name)) !== null;
    }

    /**
     * Magic method to unset a field.
     *
     * For example when calling things like unset($object->foo), it will call this function with $name = 'foo'
     *
     * @param string $name The field name
     *
     * @return void
     */
    public function __unset($name)
    {
        unset($this->fields[$name]);
    }

    /**
     * Magic method called when a function invoked on the object doesn't exist.
     *
     * Used to handle set____(), get___(), has_____(), and is____()
     *
     * set___() methods will set the value of the key given (see __set above)
     * get___() methods will fetch the value
     * has___() methods will return true if the specified field has a value (is not empty)
     * is____() methods will return true if the specified field's value is not false
     *
     * @param string $name      The name of the method called
     * @param array  $arguments Any arguments passed.
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) == 'get') {
            $fieldName = substr($name, 3);
            if(empty($fieldName))
                throw new Exception('Attempted to retrieve blank property from object');
            return array_key_exists($fieldName, $this->fields)?$this->fields[$fieldName]:null;
        } else if (substr($name, 0, 3) == 'set') {
            $fieldName = substr($name, 3);
            if(empty($fieldName))
                throw new Exception('Attempted to set blank property for object');
            if(empty($arguments))
                throw new Exception('No arguments for method ['.$name.']');
            $this->fields[$fieldName] = $arguments[0];
            return;
        } else if (substr($name, 0, 3) == 'has') {
            $fieldName = substr($name, 3);
            return !empty($this->fields[$fieldName])?true:false;
        } else if (substr($name, 0, 2) == 'is') {
            $fieldName = substr($name, 2);
            return isset($this->fields[$fieldName])?StringUtils::strToBool($this->fields[$fieldName]) !== false:false;
        }

        throw new Exception('Method ['.$name.'] not found on class ['.get_class($this).']');

    }


    /**
     * Magic method.
     *
     * The default behavior for objects is to print their serialized version when converting to a string.
     *
     * @return string serialized object
     */
    public function __toString()
    {
        return get_class($this).' object';//serialize($this->fields);
    }

    /**
     * Magic method. Called when the object is destoryed
     *
     * @return void
     */
    public function __destruct()
    {
        $this->fields = null;
//        unset($this->fields);
    }

    /**
     * Returns a serialized version of our fields
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->fields);
    }

    /**
     * Sets the fields from a serialized string
     *
     * @param string $data Serialized string
     *
     * @return void
     */
    public function unserialize($data)
    {
        $this->fields = unserialize($data);
    }

    /**
     * Used to support ArrayAccess
     *
     * @param string $offset The offset or key
     * @param string $value  The value
     *
     * @see http://www.php.net/ArrayAccess
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $method = 'set'.$offset;
        return $this->$method($value);
    }

    /**
     * Used to support ArrayAccess
     *
     * @param string $offset The offset or key
     *
     * @see http://www.php.net/ArrayAccess
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        $method = 'get'.$offset;
        return $this->$method() !== null;
    }

    /**
     * Used to support ArrayAccess
     *
     * @param string $offset The offset or key
     *
     * @see http://www.php.net/ArrayAccess
     *
     * @return boolean
     */
    public function offsetUnset($offset)
    {
        unset($this->fields[$offset]);
    }

    /**
     * Used to support ArrayAccess
     *
     * @param string $offset The offset or key
     *
     * @see http://www.php.net/ArrayAccess
     *
     * @return boolean
     */
    public function offsetGet($offset)
    {
        $method = 'get'.$offset;
        return $this->$method();
    }


}
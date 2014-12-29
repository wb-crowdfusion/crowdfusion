<?php
/**
 * ModelObject
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
 * @version     $Id: ModelObject.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * The ModelObject class defines model objects that we can use.
 *
 * A model object has a list of fields (defined through getModelSchema())
 * that will be stored using some storage mechanism.
 *
 * @package     CrowdFusion
 */
abstract class ModelObject extends Object
{
    /**
     * Creates the model object. Please use the ModelMapper to set defaults and initialize from an array.
     */
    public function __construct()
    {
//        if (func_num_args() > 0)
//            throw new Exception("Deprecated! Please use ModelMapper.");

        parent::__construct();
    }

    /**
     * This function defines the fields that will be persisted
     * using the storage mechanism (usually the database)
     *
     * Example:
     * <code>
     *      array('Id', 'Name', 'ParentID', ...)
     * </code>
     *
     * @return array An array of fields
     */
    public function getPersistentFields()
    {
        return array_keys($this->getModelSchema());
    }

    /**
     * Defines the schema that will be used to build
     * the model and validate the fields.
     *
     * The format of this array is as follows:
     * <code>
     *  array(
     *      'fieldname' => array(
     *                          'default' => 'default',
     *                          'title' => 'The Title',
     *                          'validation' => array('datatype' => 'int', 'match' => '^regular\sexpres{2}ion', ...options...)
     *                      )
     *      ...
     * )
     * </code>
     *
     * The validation options are described below.
     * All are optional, but at least one must be specified if using the
     * alternate format.
     * KEY          TYPE        Description
     * ----------------------------------------------------
     * datatype     string      int | float | string | slug | slugwithslash | date | boolean | url | html | email | flag
     *                          If set to slug, slugwithslash, url, or email, an implicit regex match expression will
     *                          be used to validate the field value.
     *                              Default: 'string'
     * nullable     boolean     If set to true, the value will be allowed
     *                              to be 'null' or unset when saved.
     *                              Default: false
     * match        regex       The regular expression that the value
     *                              must match (unless it's allowed by another option)
     *                              Default: null
     * allowedtags    string      A comma-separated list of html tag to allow during HTML clean.
     *                              Default: 'b,i,em,strong,a[href],p,br'
     * min          int|float   Set to the minimum string length or minimum scalar amount of the value
     *                              If set to null, then no minimum length will be enforced.
     *                              Default: null
     * max          int|float   Set to the maximum string length or minimum scalar amount of the value.
     *                              If set to null, then no maximum length will be enforced.
     *                              Default: null
     * precision    int         Set to the precision of the scalar value.  This is used as a hint to convert scalar
     *                              values to more precise storage data types.
     *
     * @return array see description above
     */
    public abstract function getModelSchema();

    /**
     * Returns the name of the database table used to store this object
     *
     * @return string
     */
    public abstract function getTableName();

    /**
     * Returns the name of the column that holds the primary key for this model object
     *
     * @return string
     */
    public abstract function getPrimaryKey();


    /**
     * Returns the datatype of the field, the default datatype, string, is set in the getValidation() function.
     *
     * @param string $key The name of the field
     *
     * @return string
     */
    public function getDatatype($key)
    {

        $validation = $this->getValidation($key);

        if(empty($validation['datatype']))
            throw new Exception('No datatype defined for key: '.$key);

        return $validation['datatype'];
    }




    /**
     * Returns the default value of the field.
     *
     * @param string $key The name of the field
     *
     * @return mixed or null if there is no default value
     */
    public function getDefault($key)
    {
        $schema = $this->getModelSchema();

        if (array_key_exists('default', $schema[$key]))
            return $schema[$key]['default'];

        return null;
    }

    /**
     * Returns the field title.
     *
     * @param string $key The name of the field
     *
     * @return string or the field name if there is no title
     */
    public function getFieldTitle($key)
    {
        $schema = $this->getModelSchema();

        if (array_key_exists('title', $schema[$key]))
            return $schema[$key]['title'];

        return $key;
    }

    /**
     * Returns the model schema, which we'll use for validation
     *
     * @param string $field The field name
     *
     * @return array
     */
    public function getValidation($field = null)
    {

        $validation = array();
        $schema = $this->getModelSchema();

        if ($field != null)
            $schema = array($field => $schema[$field]);

        foreach ( $schema as $key => $value ) {
            if (!isset($value['validation']))
                throw new Exception('Validation missing for key: '.$field);
            $validation[$key] = $value['validation'];
        }

        if ($field != null)
            return $validation[$field];

        return $validation;
    }

    /**
     * Returns a ValidationExpression object for the request field
     *
     * @param string $field The field name
     *
     * @return ValidationExpression
     */
    public function getValidationExpression($field)
    {
        return new ValidationExpression($this->getValidation($field));
    }

    /**
     * Returns an array of persistent fields from this model object
     *
     * @return array
     */
    public function toPersistentArray()
    {
        throw new Exception("Deprecated! Please use ModelMapper.");
    }

    /**
     * Determines if the field specified by {@link $name} is a persistent field
     *
     * @param string $name The field name to investigate
     *
     * @return boolean
     */
    public function isPersistentField($name)
    {
        return array_key_exists($name, $this->getPersistentFields());
    }

}

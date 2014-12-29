<?php
/**
 * ValidationExpression
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
 * @version     $Id: ValidationExpression.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * A validation expression is used to validate any ModelObject or object from the schema.XML
 *
 * @package     CrowdFusion
 */
class ValidationExpression // NOTE: DO NOT EXTEND OBJECT, WILL BREAK
{

    protected $validation;

    protected $skipValidation = false;

    protected $failureCode = '';
    protected $failureMessage = '';

    /**
     * Accepts an array or the schemaxml (simpleXML) object or a string to create our expression
     *
     * Options for the array are as follows
     * All are optional, but at least one must be specified if using the
     * array.
     * KEY          TYPE        Description
     * ----------------------------------------------------
     * datatype     string      int | float | string | slug | slugwithslash | date | boolean | url | html | email | flag | binary
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
     *                              Default: null
     *
     * @param string  $array_or_schemaxml an array or the schemaxml (simpleXML) object or regex string
     * @param boolean $defaultToNullable  if true, nullable will default to true (Normally, defaults to false)
     */
    public function __construct($array_or_schemaxml, $defaultToNullable = false)
    {

        $this->validation = array(
            'datatype'  => null,
            'nullable'  => false,
            'match'     => null,
            'min'       => null,
            'max'       => null,
            'precision' => null,
            'callback'  => array());

        if($defaultToNullable)
            $this->validation['nullable'] = true;

        if (is_array($array_or_schemaxml)) {

            foreach ($this->validation as $key => $val) {
                if (array_key_exists($key, $array_or_schemaxml))
                    $this->validation[$key] = $array_or_schemaxml[$key];
            }

        } elseif ($array_or_schemaxml instanceof SchemaXML) {

            // "true" and "false" are special values, changed into their respective values in php
            if ($array_or_schemaxml->validation) {
                foreach ($array_or_schemaxml->validation->attributes() as $name => $value) {

                    switch ( strtolower((string)$value) ) {
                    case 'true':
                        $value = true;
                        break;

                    case 'false':
                        $value = false;
                        break;

                    default:
                        $value = (string)$value;
                        break;
                    }

                    $this->validation[$name] = $value;
                }
            } else {
                $this->skipValidation = true;
            }
        }

        if (!is_array($this->validation['callback'])) {
            if (strpos($this->validation['callback'], '|'))
                $this->validation['callback'] = explode('|', $this->validation['callback']);
            else
                $this->validation['callback'] = (array)$this->validation['callback'];
        }

    }

    /**
     * Returns the validation array
     *
     * @return array
     */
    public function getValidationArray()
    {
        return $this->validation;
    }

    public function getDatatype()
    {
        return $this->validation['datatype'];
    }

    public function setMax($max)
    {
        $this->validation['max'] = $max;
    }

    /**
     * Returns TRUE if the given value passes validation.
     *
     * @param string $value A value to test
     *
     * @return boolean
     */
    public function isValid($value)
    {
        if ($this->skipValidation)
            return true;

        $datatype = $this->validation['datatype'];

        //NULL check, empty strings are considered null
        if(in_array($datatype, array('string', 'url', 'email', 'slug', 'slugwithslash', 'html', 'binary','json'))
          && strlen(trim($value)) == 0)
            $value = null;

        if ($this->validation['nullable'] === false && $value === null && $datatype != 'boolean') {
            $this->failureCode = 'nullable';
            $this->failureMessage = 'cannot be empty';
            return false;
        }

        //Nothing more to validate if the value is null...
        if ($value === null)
            return true;


        //Check date makes sense
        if ($datatype === 'date') {

            //todo: not sure how to check validity of date... it's already a DateTime instance.
            if (false) {
                $this->failureCode = 'invalid';
                $this->failureMessage = 'is an invalid date';
                return false;
            }
        }


        //Validate MIN
        $min = $this->validation['min'];

        if ($min != null) {
            if ($datatype === 'float') {
                if($value < floatval($min)) {
                    $this->failureCode = 'min';
                    $this->failureMessage = 'is less than the minimum value';
                    return false;
                }
            } else if ($datatype === 'int') {
                if($value < intval($min)) {
                    $this->failureCode = 'min';
                    $this->failureMessage = 'is less than the minimum value';
                    return false;
                }
            } else {

                if (is_string($value) && strlen($value) < intval($min)) {
                    $this->failureCode = 'minlength';
                    $this->failureMessage = 'must be at least '.($min).' characters';
                    return false;
                }
            }
        }


        //Validate MAX
        $max = $this->validation['max'];

        if ($max != null) {
            if ($datatype === 'float') {
                if($value > floatval($max)) {
                    $this->failureCode = 'max';
                    $this->failureMessage = 'is more than the maximum value';
                    return false;
                }
            } else if ($datatype === 'int') {
                if($value > intval($max)) {
                    $this->failureCode = 'max';
                    $this->failureMessage = 'is more than the maximum value';
                    return false;
                }
            } else {

                $maxbytes = intval($max);

                if(intval($max) < 255)
                {
                    // count characters

                    if (is_string($value) && StringUtils::charCount($value) > intval($max)) {
                        $this->failureCode = 'maxlength';
                        $this->failureMessage = 'must be a maximum of '.($max).' characters';
                        return false;
                    }

                    $maxbytes = 255;

                }

                // count bytes
                if (is_string($value) && StringUtils::byteCount($value) > intval($maxbytes)) {
                    $this->failureCode = 'maxlength';
                    $this->failureMessage = 'must be a maximum of '.($maxbytes).' bytes';
                    return false;
                }
            }
        }

        if($datatype === 'slug')
        {
            if(!SlugUtils::isSlug($value, false))
            {
                $this->failureCode = 'invalid';
                $this->failureMessage = 'is not a valid slug, cannot contain slashes';
                return false;
            }
        }

        if($datatype === 'slugwithslash')
        {
            if(!SlugUtils::isSlug($value, true))
            {
                $this->failureCode = 'invalid';
                $this->failureMessage = 'is not a valid slug';
                return false;
            }
        }

        if($datatype === 'url')
        {
            if(!URLUtils::isUrl($value))
            {
                $this->failureCode = 'invalid';
                $this->failureMessage = 'is not a valid URL';
                return false;
            }
        }

        if($datatype === 'email')
        {
            if(!EmailUtils::isEmailAddress($value))
            {
                $this->failureCode = 'invalid';
                $this->failureMessage = 'is not a valid email address';
                return false;
            }
        }

        if($datatype === 'json')
        {
            if(!JSONUtils::isValid($value)) //todo: get exact error message here
            {
                $this->failureCode = 'invalid';
                $this->failureMessage = 'is not a valid json string';
                return false;
            }
        }

        //Validate MATCH expression
        $match = $this->validation['match'];

        if ($match != null) {
            // Automatically escape unescaped slashes in the match before running it
            $match = preg_replace('/([^\\\\])\//', '$1\\/', $match);

            if (preg_match('/'.$match.'/s', $value) === 0) {
                $this->failureCode = 'invalid';
                //$this->failureMessage = 'is invalid (' . substr($value, 0, 255) . ')';
                $this->failureMessage = 'is invalid';
                return false;
            }
        }

        // Validate all custom functions
        foreach ( $this->validation['callback'] as $callback ) {
            if (!empty($callback) && call_user_func($callback, $value) === false) {
                $this->failureCode = $callback;
                $this->failureMessage = 'is invalid';
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the last failure code recorded
     *
     * @return string
     */
    public function getFailureCode()
    {
        return $this->failureCode;
    }

    /**
     * Returns the last failure message recorded
     *
     * @return string
     */
    public function getFailureMessage()
    {
        return $this->failureMessage;
    }

    public function toArray()
    {
        return array(
            'datatype' => $this->validation['datatype'],
            'nullable' => $this->validation['nullable'],
            'match' => $this->validation['match'],
            'min' => $this->validation['min'],
            'max' => $this->validation['max'],
            'precision' => $this->validation['precision'],
        );
    }
}

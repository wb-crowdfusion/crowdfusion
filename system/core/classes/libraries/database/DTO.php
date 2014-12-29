<?php
/**
 * DTO
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
 * @version     $Id: DTO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * DTO
 *
 * The DTO class is used to define how objects should be retrieved from the database.
 *
 * The Parameters stored on a DTO are used in DAO objects to build queries
 * and retrieve the proper items from the datasource.
 *
 * @package     CrowdFusion
 */
class DTO
{
    protected $limit = false;
    protected $offset = false;
    protected $orderBy = array();
    protected $parameters = array();

    protected $results;
    protected $totalRecords;

    protected $retrieveAsObjects = false;
    protected $retrieveTotalRecords = false;

    /**
     * Builds a DTO
     *
     * @param array   $parameters An array like ['name' => 'value'] of parameters
     * @param array   $orderBy    An array like ['field' => 'direction'] for ordering data from the database
     * @param integer $limit      If specified, the maximum number of items to be retrieved.
     * @param integer $offset     If specified, the offset for results from the database
     */
    public function __construct($parameters = array(), $orderBy = array(), $limit = false, $offset = false)
    {
        $this->parameters = $parameters;
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * Adds the parameters specified by $params
     *
     * @param array $params An array of parameters to merge. Will over-write existing values in the database.
     *
     * @return DTO $this
     */
    public function mergeParameters(array $params)
    {
        $this->parameters = array_merge($this->parameters, $params);
        return $this;
    }

    /**
     * Sets the parameter specified by $name to $value
     *
     * @param string $name  The name of the parameter
     * @param mixed  $value The value to store
     *
     * @return DTO $this
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Sets the parameters specified by $params
     *
     * @param array $params An array like ['name' => 'value'] of parameters to set.
     *
     * @return DTO $this
     */
    public function setParameters(array $params)
    {
        $this->parameters = $params;

        return $this;
    }

    /**
     * Returns the value stored in the parameter specified
     *
     * @param string $name The name of the param
     *
     * @return mixed Will return the value of the parameter or null
     */
    public function getParameter($name)
    {
        return array_key_exists($name, $this->parameters) ?
                  $this->parameters[$name]
                : null;
    }

    /**
     * Removes the specified parameter
     *
     * @param string $name Parameter name
     *
     * @return DTO $this
     */
    public function removeParameter($name)
    {
        unset($this->parameters[$name]);
        return $this;
    }

    /**
     * Returns an array of all parameters
     *
     * @return array An array like ['name' => 'value'] of all parameters
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Determines if the specified parameter exists.
     *
     * @param string $name Parameter name
     *
     * @return boolean true if the specified parameter exists
     */
    public function hasParameter($name)
    {
        return $this->getParameter($name) !== null;
    }

    /**
     * Sets the specified orderBy
     *
     * @param string $field     The name of the field
     * @param string $direction The direction to sort. Can be ASC or DESC (default to ASC if not specified)
     *
     * @return DTO $this
     */
    public function setOrderBy($field, $direction = null)
    {
        if (empty($field))
            return $this;

        if (count($order = explode(' ', $field)) == 2) {
            $field = $order[0];
            $direction = $order[1];
        }
        $this->orderBy[$field] = empty($direction) ? 'ASC' : $direction;
        return $this;
    }

    /**
     * Returns the OrderBy direction for the field specified
     *
     * @param string $field the field name to lookup
     *
     * @return string Returns the direction (ASC or DESC) for the field specified, or null if it doesn't exist.
     */
    public function getOrderBy($field)
    {
        return array_key_exists($field, $this->orderBy) ?
                  $this->orderBy[$field]
                : null;
    }

    /**
     * Sets all orderBys
     *
     * NOTE: This will completely replace all orderbys that might be on the DTO already
     *
     * @param array $orderBys an array like ['field' => 'direction', ...] for all order bys.
     *
     * @return void
     */
    public function setOrderBys(array $orderBys)
    {
        $this->orderBy = $orderBys;
        return $this;
    }

    /**
     * Returns an array of all orderbys
     *
     * @return array All orderbys
     */
    public function getOrderBys()
    {
        return $this->orderBy;
    }

    /**
     * Sets the limit to the limit specified
     *
     * @param integer $limit The limit
     *
     * @return DTO $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Returns the limit or null if not set
     *
     * @return integer
     */
    public function getLimit()
    {
        return $this->limit!==false ?
                    $this->limit
                : null;
    }

    /**
     * Sets the offset
     *
     * if the offset is greater than the PHP_INT_MAX
     * then the offset will be set to false
     *
     * @param integer $offset The offset
     *
     * @return DTO $this
     */
    public function setOffset($offset)
    {
        if ($offset >= PHP_INT_MAX) {
            $offset = false;
        }

        $this->offset = $offset;
        return $this;
    }

    /**
     * Gets the offset
     *
     * @return integer the offset or null if it's not set
     */
    public function getOffset()
    {
        return $this->offset!==false ?
                    $this->offset
                : null;
    }

    /**
     * Sets the results
     *
     * @param array $array An array of results to store
     *
     * @return DTO $this
     */
    public function setResults(array $array)
    {
        $this->results = $array;
        return $this;
    }

    /**
     * Returns the results
     *
     * @return array The results
     */
    public function getResults()
    {
        return $this->results;
    }


    /**
     * Clears the results
     *
     * @return array The results
     */
    public function clearResults()
    {
        $this->results = null;
        return $this;
    }

    /**
     * Returns the result at the specified {@link $index}
     *
     * @param integer $index The index to get
     *
     * @return mixed The result at the specified index or null if it doesn't exist
     */
    public function getResult($index = 0)
    {
        if($index == 0 && count($this->results) > 0)
            return current($this->results);

        return isset($this->results[$index]) ?
                    $this->results[$index]
                : null;
    }

    /**
     * Sets wether we want to retrieve a total count for the records or not
     *
     * @param boolean $total True if we want to retrieve a count for the total number of records
     *
     * @return DTO $this
     */
    public function setTotalRecords($total)
    {
        $this->totalRecords = $total;
        return $this;
    }

    /**
     * Returns wether we want to retrieve a total count for the records or not
     *
     * @return boolean
     */
    public function getTotalRecords()
    {
        return $this->totalRecords;
    }

    /**
     * Determines if we have any results
     *
     * @return boolean true if we have at least one result
     */
    public function hasResults()
    {
        return $this->getResultsTotal() > 0;
    }

    /**
     * Returns the count of records from the
     * current resultset.
     *
     * @return integer
     */
    public function getResultsTotal()
    {
        return count($this->results);
    }

    /**
     * Returns all the results as objects of the class specified
     *
     * @param string $classname The name of the class to return the objects as
     *
     * @return array An array of the results converted into the new class
     */
    public function getResultsAsObjects($classname)
    {
        $new = array();
        foreach ((array) $this->results as $result) {
            if (is_array($result))
                $new[] = new $classname($result);
            else
                $new[] = $result;
        }
        return $new;
    }

    /**
     * Returns all results as an array
     *
     * @return array an Array of all results, each one being an array
     */
    public function getResultsAsArray()
    {
        $new = array();
        foreach ((array) $this->results as $result) {
            if ($result instanceof Object)
                $new[] = $result->toArray();
            else if (is_array($result))
                return $this->results;
            else
                throw new Exception('Cannot convert to array: '.ClassUtils::getQualifiedType($result));
        }
        return $new;
    }

    /**
     * Returns an array containing the value stored in the column specifed for each result
     *
     * @param string $col The column name to fetch
     *
     * @return array
     */
    public function getColumnOfResults($col)
    {
        $new = array();
        foreach ((array)$this->results as $result) {
            if ($result instanceof Object)
                $new[] = $result->$col;
            else if (is_array($result))
                $new[] = $result[$col];
        }
        return $new;
    }

    /**
     * When called, this will specify that the results for this DTO should be stored as objects
     *
     * @return DTO $this
     */
    public function asObjects()
    {
        $this->retrieveAsObjects = true;
        return $this;
    }

    /**
     * When called, this will specify that the results for this DTO should be stored as arrays
     *
     * @return DTO $this
     */
    public function asArray()
    {
        $this->retrieveAsObjects = false;
        return $this;
    }

    /**
     * Determines if the DTO will retrieve the results as objects.
     * Also acts as a setter for retrieve as objects
     *
     * @param boolean $val If set to true or false, then it will retrieve results as objects or arrays respectively.
     *
     * @return mixed Will return DTO $this if $val is specified, otherwise,
     *              returns boolean that answers the question "Retrieve as objects?"
     */
    public function isRetrieveAsObjects($val = null)
    {
        if (!is_null($val)) {
            $this->retrieveAsObjects = $val;
            return $this;
        }

        return $this->retrieveAsObjects == true;
    }

    /**
     * Determines if the DTO will retrieve a count of total records for the results
     * Also acts as a setter for retrieve total records
     *
     * @param boolean $val If set to true, then it will retrieve a count of total records for the results.
     *                      If unspecified, the function will return the current value for retrieveTotalRecords
     *
     * @return mixed Will return DTO $this if $val is specified, otherwise,
     *              returns boolean that answers the question "Retrieve a count of total records?"
     */
    public function isRetrieveTotalRecords($val = null)
    {
        if (!is_null($val)) {
            $this->retrieveTotalRecords = $val;
            return $this;
        }

        return $this->retrieveTotalRecords;
    }
}
<?php
/**
 * Query represents a single SQL statement.
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
 * @version     $Id: Query.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Object to build and represent a single SQL statement
 *
 * @package     CrowdFusion
 */
class Query
{

    /**
     * SELECT HELPERS
     */

    protected $forUpdate = false;
    protected $selectParams = array();

    const SELECT  = 'SELECT';
    const FROM    = 'FROM';
    const JOIN    = 'JOIN';
    const WHERE   = 'WHERE';
    const ORWHERE = 'ORWHERE';
    const GROUPBY = 'GROUPBY';
    const ORDERBY = 'ORDERBY';
    const LIMIT   = 'LIMIT';
    const OFFSET  = 'OFFSET';

    /**
     * Returns the query
     *
     * @return string
     */
    public function __toString()
    {
        return $this->build();
    }

    /**
     * Creates the SQL query
     *
     * @param string $sql A prefix, or string that will be pre-pended to the generated query.
     *
     * @return string A SQL query string
     */
    public function build($sql='')
    {

        $sql .= "\n";
        if (!empty($this->selectParams[self::SELECT])) {
            $sql = "SELECT ".implode(",\n", $this->selectParams[self::SELECT]);
        }
        if (!empty($this->selectParams[self::FROM])) {
            $sql .= "\nFROM ".implode(",\n", $this->selectParams[self::FROM]);
        }
        if (!empty($this->selectParams[self::JOIN])) {
            $sql .= " ".implode("\n ", $this->selectParams[self::JOIN]);
        }
        if (!empty($this->selectParams[self::WHERE])) {
            $sql .= "\nWHERE (".implode(")\n\t AND (", $this->selectParams[self::WHERE]). ") ";
            $sql .= "\n";
        }
        if (!empty($this->selectParams[self::ORWHERE])) {
            if(!empty($this->selectParams[self::WHERE]))
                $sql .= " AND ";
            else
                $sql .= "\nWHERE ";
            $sql .= " (".implode(" OR ", $this->selectParams[self::ORWHERE]). ") ";
            $sql .= "\n";
        }
        if (!empty($this->selectParams[self::GROUPBY])) {
            $sql .= "\nGROUP BY ".implode(",", $this->selectParams[self::GROUPBY]);
        }
        if (!empty($this->selectParams[self::ORDERBY])) {
            $sql .= "\nORDER BY ".implode(",", $this->selectParams[self::ORDERBY]);
        }

        if (isset($this->selectParams[self::LIMIT])) {
            if ($this->selectParams[self::LIMIT] != 0) {
                $sql .= "\nLIMIT ";
                if (!empty($this->selectParams[self::OFFSET])) {
                    if ($this->selectParams[self::OFFSET] < 0) $this->selectParams[self::OFFSET] = 0;
                    $sql .= $this->selectParams[self::OFFSET] .",";
                }
                $sql .= $this->selectParams[self::LIMIT];
            }
        }

        if($this->forUpdate)
            $sql .= " FOR UPDATE";

        return $sql;
    }

    /**
     * Adds one or more columns to the select part of the query.
     *
     * @param array/string $column If an array, replaces all select columns with the columns specified. If a string, appends that column.
     *
     * @return this
     */
    public function select($column = array())
    {
        $this->setClause(self::SELECT, $column);
        return $this;
    }

    public function forUpdate()
    {
        $this->forUpdate = true;
        return $this;
    }

    public function notForUpdate()
    {
        $this->forUpdate = false;
        return $this;
    }

    /**
     * Adds one or more tables to the FROM part of the query.
     *
     * If given an array, the FROM tables are replaced with the tables in the array.
     * If given a string, the table specified is appended to the list of tables.
     *
     * @param mixed $table Can be a string or an array. See function description
     *
     * @return this
     */
    public function from($table = array())
    {
        $this->setClause(self::FROM, $table);
        return $this;
    }

    /**
     * Adds one or more tables to the JOIN part of the query.
     *
     * If given an array, the JOIN tables are replaced with the tables in the array.
     * If given a string, the table specified is appended to the list of join tables.
     *
     * @param mixed $joinClause Can be a string or an array. See function description.
     *
     * @return this
     */
    public function join($joinClause = array())
    {
        $this->setClause(self::JOIN, $joinClause);
        return $this;
    }

    /**
     * Adds one or more clauses to the WHERE part of the query.
     *
     * If given an array, the WHERE clauses are replaced with the clauses in the array.
     * If given a string, the clause specified is appended to the list of clauses.
     *
     * @param string $whereClause Can be a string or an array. See function description.
     *
     * @return this
     */
    public function where($whereClause = array())
    {
        $this->setClause(self::WHERE, $whereClause);
        return $this;
    }

    /**
     * Adds one or more clauses to the WHERE part of the query, where only one of the
     * clauses must be true.
     *
     * For example:
     * <code>
     *  $query->orWhere(array("apple LIKE '%orange%'", "taste = 'terrible'"));
     *  echo $query; # => "... WHERE (apple LIKE '%orange%' OR taste = 'terrible') ..."
     * </code>
     *
     * If given an array, the ORWHERE clauses are replaced with the clauses in the array.
     * If given a string, the clause specified is appended to the list of ORWHERE clauses.
     *
     * @param string $whereClause Can be a string or an array. See function description.
     *
     * @return this
     */
    public function orWhere($whereClause = array())
    {
        $this->setClause(self::ORWHERE, $whereClause);
        return $this;
    }

    /**
     * Adds one or more clauses to the GROUP BY part of the query.
     *
     * If given an array, the GROUP BY clauses are replaced with the clauses in the array.
     * If given a string, the clause specified is appended to the list of GROUP BY clauses.
     *
     * @param string $groupBy Can be a string or an array. See function description.
     *
     * @return this
     */
    public function groupBy($groupBy = array())
    {
        $this->setClause(self::GROUPBY, $groupBy);
        return $this;
    }

    /**
     * Adds one or more clauses to the ORDER BY part of the query.
     *
     * If given an array, the ORDER BY clauses are replaced with the clauses in the array.
     * If given a string, the clause specified is appended to the list of ORDER BY clauses.
     *
     * @param string $orderBy Can be a string or an array. See function description.
     *
     * @return this
     */
    public function orderBy($orderBy = array())
    {
        $this->setClause(self::ORDERBY, $orderBy);
        return $this;
    }

    /**
     * Sets the LIMIT clause for the query.
     *
     * If a NULL value is specified, the limit is removed from the query.
     *
     * @param string $limit The limit to use. If NULL, the limit is cleared.
     *
     * @return this
     */
    public function limit($limit)
    {
        if(!empty($limit))
            $this->selectParams[self::LIMIT] = $limit;
        else
            unset($this->selectParams[self::LIMIT]);
        return $this;
    }

    /**
     * Sets or clears the offset for the query.
     * If null is specified, the offset is cleared.
     *
     * @param string $offset The offset to use. If NULL, the offset is cleared.
     *
     * @return this
     */
    public function offset($offset)
    {
        if(!empty($offset))
            $this->selectParams[self::OFFSET] = $offset;
        return $this;
    }

    /**
     * The setter used internally in this function to build each clause.
     *
     * If {@link $clause} is an array, it uses the value of clause to set the section.
     * Otherwise, {@link $clause} is appended to the section.
     *
     * @param string $section The section to update
     * @param mixed  $clause  The clause to use.
     *
     * @return void
     */
    protected function setClause($section, $clause)
    {
        if(!isset($this->selectParams[$section]))
            $this->selectParams[$section] = array();

        if(empty($clause))
            unset($this->selectParams[$section]);
        else if(is_array($clause))
            $this->selectParams[$section] = array_merge($this->selectParams[$section], $clause);
        else
            $this->selectParams[$section][] = $clause;

    }

    /**
     * Resets the SELECT columns for the query
     *
     * @return void
     */
    public function clearSelect()
    {
        $this->selectParams[self::SELECT] = array();
    }


}

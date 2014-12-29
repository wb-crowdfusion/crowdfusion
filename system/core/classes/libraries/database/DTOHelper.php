<?php
/**
 * DTOHelper
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
 * @version     $Id: DTOHelper.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * DTOHelper
 *
 * @package     CrowdFusion
 */
class DTOHelper
{

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    /**
     * Sets up the Logger we'll use.
     * This is filled during the autowire process, so you probably won't need to use it.
     *
     * @param LoggerInterface $Logger The Logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

    /**
     * Used internally to log the DTO
     *
     * @param DTO $dto The DTO to log
     *
     * @return void
     */
    public function logDTO($dto)
    {
        $this->Logger->debug($dto);
    }

    /**
     * Returns a filled DTO-object with the results from the
     * query specified in {@link $sql}.
     *
     * {@link $dto} is examined for the following items:
     *      DTO->isRetrieveTotalRecords
     *      DTO->getTotalRecords
     *      DTO->isRetrieveAsObjects
     *      DTO->getOriginalRowCount
     *
     * @param Database $db            The database to query
     * @param string   $sql           The query to run
     * @param DTO      &$dto          The DTO object used for the query
     * @param boolean  $convertObject If true and DTO->isRetrieveAsObjects is true, then the result DTO will hold objects
     *
     * @return DTO a DTO object filled with results from the query
     */
//    protected function readAllUsingDTO( $db, $sql, &$dto, $convertObject = false)
//    {
//        $this->logDTO($dto);
//
//        $shouldCalcRows = false;
//
//        if ($dto->isRetrieveTotalRecords() && $dto->getTotalRecords() == null)
//            $shouldCalcRows = true;
//
//        $rows = $db->readAll($sql, $shouldCalcRows);
//
//        if ($convertObject != false && $dto->isRetrieveAsObjects()) {
//            foreach ($rows as &$row)
//                $row = new $convertObject($row);
//        }
//
//        $dto->setResults($rows);
//
//        if ($dto->isRetrieveTotalRecords() && $dto->getTotalRecords() == null)
//            $dto->setTotalRecords($db->getOriginalRowCount());
//
//        return $dto;
//    }

    /**
     * Extends the {@link $query} with Limits, Offsets, and Orderbys specified in the DTO
     *
     * @param Database $db           The database this query will be used on (used to escape values)
     * @param Query    $query        The query object that we'll extend with the limits, offsets and orderbys
     * @param DTO      $dto          The DTO object that contains our limits, offsets, and orderbys
     * @param array    $defaultSorts An array of the form [name => direction] that defines default sorts (used if none are in the DTO)
     *
     * @return void
     */
    public function buildLimitOffsetOrderbys($db, $query, $dto, array $defaultSorts)
    {

        if ($dto->getLimit() != null)
            $query->LIMIT($dto->getLimit());
        if ($dto->getOffset() != null)
            $query->OFFSET($dto->getOffset());

        $query->ORDERBY();

        $sorts = array();

        $arr = func_num_args()==5?func_get_arg(4):null;
        $arr = is_array($arr)?$arr:array_slice(func_get_args(), 4);


        $dtoSorts = $dto->getOrderBys();

        if ($dtoSorts == null)
            $dtoSorts = $defaultSorts;

        $diff = array_diff(array_keys($dtoSorts), array_keys($arr));
        $merged = array_merge($diff, $arr);
        $sorts = array_unique($merged);

        foreach ($sorts as $name => $column) {
            if (is_int($name)) $name = $column;
            if (isset($dtoSorts[$name])) {
                $direction = $dtoSorts[$name];
                if (strcasecmp($name, 'FIELD') === 0) {
                    if (count($dto->getParameter($direction)) == 0 || array_sum($dto->getParameter($direction)) == 0)
                        continue;
                    $query->ORDERBY('FIELD('.$direction.','.$db->joinQuote($dto->getParameter($direction)).')');
                } else {
                    $query->ORDERBY("$column $direction");
                }
            }
        }

    }

    /**
     * Appends to the {@link $query} a filter for the column specified
     *
     * @param Database $db     The database
     * @param Query    $query  The Query
     * @param DTO      $dto    The DTO
     * @param string   $name   The name of the DTO parameter that contains values for use in the $column
     * @param string   $column A string that will be used in the WHERE query.
     *                          Occurrences of '?' in the string will be replaced with the value stored
     *                          in the DTO under parameter name $name, formatted according to $format
     * @param string   $format Defines how the value should be formatted in the query. The string
     *                          '#s' will be replaced with the value stored
      *                          in the DTO under parameter name $name
     *
     * @return void
     */
    public function buildReplaceFilter($db, $query, $dto, $name, $column = null, $format = '#s')
    {
        if ($dto->getParameter($name) != null) {
            if (empty($column))
                $column = $name;
            $value = $dto->getParameter($name);
            if (is_array($value))
                $query->WHERE(str_replace('?', substr($db->joinQuote($value), 1, -1), $column));
            else
                $query->WHERE(str_replace('?', $db->quote(str_replace('#s', $value, $format)), $column));
        }
    }

    /**
     * Appends a WHERE statement to the {@link $query}.
     * The $column parameter holds a string that looks something like "table.datecolumn > '#s'"
     *
     * The #s will be replaced with a properly formated date from the value stored in the DTO
     * parameter $name.
     *
     * @param Database $db       The database
     * @param Query    $query    The Query
     * @param DTO      $dto      The DTO
     * @param string   $name     The name of the DTO parameter that holds the value
     * @param string   $column   A string that will be used in the WHERE query
     * @param boolean  $endOfDay if true, then the date used will be the end of the day, not the beginning (11:59PM today vs 12:00AM today)
     *
     * @return void
     */
    public function buildDateReplaceFilter($db, $query, $dto, $name, $column = null, $hr = false, $min = false, $sec = false)
    {
        if ($dto->getParameter($name) != null) {
            if (empty($column)) $column = $name;
            $value = $dto->getParameter($name);
            $d = $this->DateFactory->newLocalDate($value);
            if ($hr !== false)
                $d->setTime($hr, $min, $sec);
            $query->WHERE(str_replace('?', $db->quote($d), $column));
        }
    }

    /**
     * Appends a WHERE statement to the {@link $query}.
     * The $column parameter holds a string that looks something like "table.datecolumn"
     *
     * The DTO parameter specified by $name will be compared against the column
     *
     * @param Database $db     The database
     * @param Query    $query  The Query
     * @param DTO      $dto    The DTO
     * @param string   $name   The name of the DTO parameter that holds the value
     * @param string   $column A string that will be used in the WHERE query
     *
     * @return void
     */
    public function buildEqualsFilter($db, $query, $dto, $name, $column = null)
    {
        if ($dto->getParameter($name) != null) {
            if (empty($column)) $column = $name;
            $value = $dto->getParameter($name);
            if (is_array($value))
                if (in_array('NULL', $value))
                    $query->WHERE("$column IS NULL OR $column IN (".$db->joinQuote($value).")");
                else
                    $query->WHERE("$column IN (".$db->joinQuote($value).")");
            else
                if (is_null($value))
                    $query->WHERE("$column IS NULL");
                else
                    $query->WHERE("$column = {$db->quote($value)}");
        }
    }

    /**
     * Appends a WHERE statement to the {@link $query} if the DTO parameter
     * specified by {@link $name} exists and has a value.
     *
     * @param Database $db     The database
     * @param Query    $query  The Query
     * @param DTO      $dto    The DTO
     * @param string   $name   The name of the DTO parameter that if exists, column will be used in the QUERY
     * @param string   $column A string that will be used in the WHERE query
     *
     * @return void
     */
    public function buildExistsFilter($db, $query, $dto, $name, $column = null)
    {
        if ($dto->getParameter($name) != null && $dto->getParameter($name) != false) {
            if (empty($column)) $column = $name;
            $query->WHERE($column);
        }

    }




}
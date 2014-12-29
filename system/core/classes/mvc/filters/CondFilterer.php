<?php
/**
 * CondFilterer
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
 * @version     $Id: CondFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * CondFilterer
 *
 * @package     CrowdFusion
 */
class CondFilterer extends AbstractFilterer
{
    protected $DateFactory;

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    /**
     * Returns true if 'value' is found in array 'array'
     *
     * Expected Params:
     *  value string The item to search for in the array
     *  array array  The array to search
     *
     * @return boolean true if value is in array
     */
    public function inArray()
    {
        $needle = $this->getParameter('value');
        $haystack = $this->getParameter('array');

        if($haystack instanceof Meta){
            $haystack = $haystack->getMetaValue();
        }

        if(is_string($haystack))
            $haystack = StringUtils::smartExplode($haystack);

        return in_array($needle, (array)$haystack);
    }

    /**
     * Returns true if now is within the specified date criteria; false otherwise.
     * The server timezone is used for all dates.
     *
     * Expected Params:
     *  days      string A comma-separated list of lowercase 3-character day abbreviations; today must match at least one of these days; optional
     *  date      string A string in the format of Y-m-d; today must match this date; optional
     *  startTime string A string in the format of H:i:s; now must be equal to or after this time; optional
     *  endTime   string A string in the format of H:i:s or '+X unit' (i.e. +3 hours); now must be before this time; optional
     *
     * @return boolean
     */
    public function isItTimeYet()
    {
        $now = $this->DateFactory->newLocalDate();
        $today = strtolower($now->format("D"));

        $days = $this->getParameter('days');           //sun,mon,tue,wed,thu,fri,sat
        $date = $this->getParameter('date');           //Y-m-d
        $startTime = $this->getParameter('startTime'); //H:i:s
        $endTime = $this->getParameter('endTime');     //H:i:s or +3 hours

        if($days != null && !in_array($today,explode(',',strtolower($days)))) {
            return false;
        }

        if($date != null && $now->format('Y-m-d') != $date) {
            return false;
        }

        if($startTime != null) {

            if($now < $this->DateFactory->newLocalDate($now->format('Y-m-d').' '.$startTime)) {
                return false;
            }

            if($endTime != null) {

                if(substr($endTime,0,1) == '+')
                    $endTime = $startTime.' '.$endTime;

                if($now > $this->DateFactory->newLocalDate($now->format('Y-m-d').' '.$endTime)) {
                    return false;
                }
            }
        }

        return true;
    }
}
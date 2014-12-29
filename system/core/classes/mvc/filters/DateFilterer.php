<?php
/**
 * DateFilterer
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
 * @version     $Id: DateFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * DateFilterer
 *
 * @package     CrowdFusion
 */
class DateFilterer extends AbstractFilterer
{
    protected $DateFactory;

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }


    protected function getDefaultMethod()
    {
        return "format";
    }

    /**
     * Returns a short form of the day of the week (like 'Mon')
     * from the numeric value for the day (like 1)
     *
     * Weeks start on Sunday (0) and end on Saturday (6)
     *
     * Expected Param:
     *  value integer The numeric value for the day of the week
     *
     * @return string
     */
    public function daysOfWeek ()
    {
        return str_replace(Array(0,    1,    2,    3,    4,    5,    6),
                           Array("Sun","Mon","Tue","Wed","Thu","Fri","Sat"),
                           $this->getParameter("value"));
    }

    /**
     * Returns the date in a specified format
     *
     * Expected Params:
     *  value       integer (optional) If specified, we'll use this as the date value. Can also be 'now' to use the current time
     *  nonbreaking string  (optional) If 'true', then the resultant string will contain &nbsp;'s instead of spaces
     *  format      string  The format for the date to use
     *
     * @return string
     */
    protected function format()
    {


        if (($this->getParameter('value') == null || $this->getParameter('value') == "0"))
            return '';
        elseif (($this->getParameter('value') != null) && $this->getParameter('value') instanceof Meta) {
            $dateTime = $this->getParameter('value');
            $dateTime = $dateTime->MetaValue;
        } elseif (($this->getParameter('value') != null) && $this->getParameter('value') instanceof Date)
            $dateTime = $this->getParameter('value');
        elseif (($this->getParameter('value') == null) || $this->getParameter('value') == 'now')
            $dateTime = $this->DateFactory->newLocalDate('@'.time());
        else {

            try {

                $dateTime = $this->DateFactory->newLocalDate($this->getParameter('value'));

            }catch(DateException $e)
            {
                return '';
            }

        }


        if($this->getParameter('storage'))
            $datetime = $this->DateFactory->toStorageDate($dateTime);
        else
            $datetime = $this->DateFactory->toLocalDate($dateTime);

        if ($this->getParameter('nonbreaking') == 'true')
            return str_replace(' ', '&nbsp;', $dateTime->format($this->getParameter('format')));

        return $dateTime->format($this->getParameter('format'));

    }

    /**
     * Displays a short text description of how long ago the specified timestamp was current.
     *
     * Expected Params:
     *  value     LocalDate (optional) If specified and 'strdate' is null, use this as the timestamp
     *  strdate   string    (optional) A string to use to create the timestamp
     *  threshold integer   (optional) If the value is older than now - threshold (in minutes), display the timestamp using format
     *  format    string    (optional) The format for the date to use
     *
     * @return string
     */
    protected function ago()
    {
        $threshold = $this->getParameter('threshold');

        if (($this->getParameter('value') == null || $this->getParameter('value') == "0"))
            return '';
        elseif (($this->getParameter('value') != null) && $this->getParameter('value') instanceof Meta) {
            $dateTime = $this->getParameter('value');
            $dateTime = $dateTime->MetaValue;
        } elseif (($this->getParameter('value') != null) && $this->getParameter('value') instanceof Date)
            $dateTime = $this->getParameter('value');
        elseif (($this->getParameter('value') == null) || $this->getParameter('value') == 'now')
            $dateTime = $this->DateFactory->newLocalDate('@'.time());
        else{

            try {

                $dateTime = $this->DateFactory->newLocalDate($this->getParameter('value'));

            }catch(DateException $e)
            {
                return '';
            }

        }

        $datetime = $this->DateFactory->toLocalDate($dateTime);

        $now = time();

        if($now >= $datetime->toUnix())
        {
            $ago = $now - $datetime->toUnix();
        } else {
            $ago = $datetime->toUnix() - $now;
        }

        if($threshold != null && $ago > (intval($threshold)*60)) {
            return $this->format();
        }

        $text = '';
            if ($ago < 60)
                $text .= 'less than a minute';
            elseif ($ago < 120)
                $text .= 'about a minute';
            elseif ($ago < (60*60))
                $text .= ceil($ago/60) . ' minutes';
            elseif ($ago < (120*60))
                $text .= 'about an hour';
            elseif ($ago < (24*60*60))
                $text .= ceil($ago/(60*60)) . ' hours';
            elseif ($ago < (48*60*60))
                $text .= '1 day';
            else
                $text .= ceil($ago / (24*60*60)).' days';

        if($now >= $datetime->toUnix())
        {
            $text .= ' ago';
        } else {
            $text .= ' from now';
        }

        return $text;
    }

    /**
     * Displays the specified minutes in a logical format
     *
     * Example:
     *  for 'value' = 1586 => '1 days 2 hours ago'
     *
     * Expected Params:
     *  value string The number of minutes ago
     *
     * @return string
     */
    protected function heartbeatAgo()
    {
        if ($this->getParameter('value') != null) {
            $min = $this->getParameter('value');

            if ($min < 60)
                return $min.' min ago';
            elseif ($min < 1440)
                return floor($min / 60).' hrs '.($min % 60 > 0 ? floor($min % 60) .' min ' : '').'ago';
            else
                return floor($min / (24*60)).' days '.($min % (24*60) > 0 ? floor($min % (24*60) / 60).' hrs ' : '').'ago';
        }
    }
}
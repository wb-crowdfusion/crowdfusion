<?php
/**
 * NumberFilterer
 *
 * PHP version 5
 *
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @version     $Id: NumberFilterer.php 2012 2012-01-15 23:34:00Z jbsmith $
 */

class NumberFilterer extends AbstractFilterer
{
    protected function getDefaultMethod()
    {
        return 'format';
    }

    /**
     * wrapper for number_format
     *
     * @return int|string
     */
    protected function format()
    {
        $number = $this->getParameter('number');

        if($number instanceof Meta) {
            $number = $number->getValue();
        }

        if(empty($number) || !is_numeric($number)) {
            return 0;
        }
        $decimals = $this->getParameter('decimals');
        $decimal_sep = $this->getParameter('decimal_separator');
        $thousands_sep = $this->getParameter('thousands_separator');

        return number_format((double)$number, $decimals, $decimal_sep, $thousands_sep);
    }

    /**
     * Return a number within a range.
     *
     * @return integer
     */
    protected function bound()
    {
        $number = $this->getParameter('number');
        $min = $this->getParameter('min');
        $max = $this->getParameter('max');

        if ($number instanceof Meta) {
            $number = $number->getValue();
        }

        return NumberUtils::bound($number, $min, $max);
    }
}
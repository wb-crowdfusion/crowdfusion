<?php

class NumberUtils
{
    /**
     * Returns an integer within a boundary.
     *
     * @param int $number
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function bound($number, $min = 0, $max = PHP_INT_MAX)
    {
        $number = (int) $number;
        $min = (int) $min;
        $max = (int) $max;

        return min(max($number, $min), $max);
    }
}

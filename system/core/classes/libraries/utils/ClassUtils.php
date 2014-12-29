<?php
/**
 * Utilities for dealing with classes
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
 * @version     $Id: ClassUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Utilities for dealing with classes
 *
 * @package     CrowdFusion
 */
class ClassUtils
{
    /**
     * Determines if the specified class {@link $sClass} is a subclass of
     * class {@link $sExpectedParentClass}
     *
     * @param string $sClass               The class to investigate
     * @param string $sExpectedParentClass The parent class to check against
     *
     * @see    http://us3.php.net/manual/en/function.is-subclass-of.php#70373
     * @author Ondra Zizka
     *
     * @return boolean true if the class {@link $sClass} is a subclass of class {@link $sExpectedParentClass}
     */
    public static function isSubclass($sClass, $sExpectedParentClass)
    {
        do
            if (strtolower($sExpectedParentClass) === strtolower($sClass))
                return true;
        while (false != ($sClass = get_parent_class($sClass)));

        return false;
    }


    /**
     * Determines the type of the specified value
     *
     * @param mixed $value Any value
     *
     * @return string The type of the {@link $value} specified
     */
    public static function getQualifiedType($value)
    {
        if (is_null($value))
            return 'NULL';

        if (is_string($value))
            return 'string';
        else if (is_array($value)) {
            if (!empty ($value)) {
                foreach ($value as $val) {
                    $array_type = self::getQualifiedType($val);
                    if ($array_type != null)
                        break;
                }
                return $array_type . '[]';
            } else {
                return 'array';
            }
        } else if (is_int($value))
            return 'int';
        else if (is_bool($value))
            return 'boolean';
        else if (is_float($value))
            return 'float';
        else if (is_object($value))
            return get_class($value);
    }

    /**
     * Compare two items for equivalence. Supports Date objects and all native types.
     *
     * @param mixed $val1 The first value
     * @param mixed $val2 The second value
     *
     * @return boolean Will be true if the items are equivalent.
     */
    public static function compare($val1, $val2)
    {
        if(in_array(self::getQualifiedType($val1), array('int','boolean', 'float', 'NULL')) )
            return $val1 == $val2 ? 0 : (($val1 < $val2) ? -1 : 1);
        // if(self::getQualifiedType($val1) == 'string' )
            // return strcmp($val1, $val2);
        if(self::isSubclass($val1, 'Date'))
            return self::compare($val1->toUnix(), $val2->toUnix());

        return strcmp((string)$val1, (string)$val2);
    }
}
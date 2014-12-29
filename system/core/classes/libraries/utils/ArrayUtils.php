<?php
/**
 * ArrayUtils
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
 * @version     $Id: ArrayUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ArrayUtils
 *
 * @package     CrowdFusion
 */
/**
 * Provides array utility methods
 *
 * @package    CrowdFusion-Libraries
 * @subpackage Utils
 * @version    $Id: ArrayUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Provides array utility methods
 *
 * @package    CrowdFusion-Libraries
 * @subpackage Utils
 */
class ArrayUtils
{

    /**
     * Append a value to the end of an array, even if it's a new array
     *
     * @param array &$array Array to append to
     * @param mixed $value  The value to append
     *
     * @return array Appended
     */
    public static function append(&$array, $value)
    {
        if (is_array($array))
            $array[] = $value;
        else
            $array = array (
                $value
            );

        return $array;
    }

    /**
     * Returns an array that has been "flattened", suitable for use in html forms.
     *
     * For example, an array like ['cache' => 'Craig', 'Fusion' => ['data' => 'fed', 'foo' => 'bar']]
     * becomes ['cache' => 'Craig', 'Fusion[data]' => 'fed', 'Fusion[foo]' => 'bar']
     *
     * @param array $array_to_flatten Array to flatten
     * @param array $parents          [NEVER SPECIFY] Only used when called recursively. Ignore this parameter
     *
     * @return array An array containing only keys and values, suitable to display on forms.
     */
    public static function htmlArrayFlatten($array_to_flatten, $parents = array ())
    {
        // Returns an array that has been "flattened", suitable for use in html forms.

        $flattened = array ();

        foreach ($array_to_flatten as $key => $value) {
            if (is_array($value)) {
                // Ugly hack. We cannot alter our $parents array, but must pass the proper array to the recursive function.
                // PHP provides no easy way to do this.
                $parent_with_child = $parents;
                $parent_with_child[] = $key;
                $flattened = array_merge($flattened, self::htmlArrayFlatten($value, $parent_with_child));
            } else {
                $keystr = '';

                // If we have parents, build the key
                if (count($parents) > 0) {

                    // Extract the common parts
                    if (!isset ($parentstr)) {
                        // First parent is rendered as Parent
                        reset($parents);
                        list (, $parentstr) = each($parents);

                        // Children build the string as Parent[Child][Child]..
                        while (list (, $parent) = each($parents)) {
                            $parentstr .= "[$parent]";
                        }
                    }

                    // Finally, identify the current key to build Parent[Child][...][0]
                    $keystr = $parentstr . "[$key]";

                } else {
                    // With no parents, the key name is just Key
                    $keystr = $key;
                }

                $flattened[$keystr] = $value;
            }

        }

        return $flattened;
    }

    public static function flattenObjects($obj, $callback = false)
    {
        if(is_array($obj))
            $new = $obj;
        else if($obj instanceof ValidationExpression ||
                $obj instanceof MetaDef ||
                $obj instanceof TagDef ||
                $obj instanceof NodeSchema ||
                $obj instanceof Node ||
                $obj instanceof Tag ||
                $obj instanceof Meta ||
                $obj instanceof Site ||
                $obj instanceof Element ||
                $obj instanceof NodePartials)
            $new = $obj->toArray();
        else if(is_object($obj))
            if(method_exists($obj, '__toString'))
                return $callback?call_user_func($callback, (string)$obj):(string)$obj;
            else
                return get_class($obj);
        else
            return $callback?call_user_func($callback, utf8_encode((string)$obj)):utf8_encode((string)$obj);

        foreach ( $new as $key => $value ) {
            $new[$key] = self::flattenObjects($value);
        }

        return $new;
    }

    public static function flattenObjectsUsingKeys($obj, array $keys)
    {
        $array = self::flattenObjects($obj);

        $newArray = array();

        foreach($keys as $key)
        {
            if(array_key_exists($key, $array))
                $newArray[$key] = $array[$key];
            else if(strpos($key, '.') !== FALSE)
            {
                $parts = StringUtils::smartSplit($key, '.', '"', '\\');
                if(!empty($parts))
                {
                    $newArray = array_merge_recursive($newArray, self::breakDownArray($array, $parts));
                }
            }
        }

        return $newArray;
    }


    protected static function breakDownArray($array, $parts)
    {
        if(empty($parts))
            return $array;

        $name = trim(array_shift($parts), '"');
        $newArray = array();

        if(array_key_exists($name, $array))
            $newArray[$name] = self::breakDownArray($array[$name], $parts);
        return $newArray;
    }

    /**
     * Returns a string version of an array in the form [key] => value, [key] => value, ...
     *
     * @param array $array array to render
     *
     * @return string The rendered string
     */
    public static function arrayToStr($array)
    {
        if(!is_array($array))
            return "".$array;

        $string_parts = array ();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $string_parts[] = "[$key] => ( " . self::arrayToStr($value) . " )";
            } else if (is_object($value)) {
                $string_parts[] = "[$key] => ".get_class($value);
            } else {
                $string_parts[] = "[$key] => $value";
            }
        }

        return join(', ', $string_parts);
    }

    /**
     * Given a multidimensional array, extract values for key matching {@link $column} and return resulting array
     *
     * For example in pseudocode:
     * <code>
     * $array = [[key1 => val, key2 => val], [key1 => val1, key2 => val1]];
     * ArrayUtils::arrayMultiColumn($array, 'key1') == [key1 => [val, val1]]
     * </code>
     *
     * @param array  $array  A multi-dimensional array
     * @param string $column The column to extract
     *
     * @return void
     */
    public static function arrayMultiColumn($array, $column)
    {
        $newArray = array ();
        foreach ( $array as $key => $columns) {
            if ((!is_array($columns)  && !$columns instanceof ArrayAccess) || !isset ($columns[$column]))
                return;
            $newArray[$key] = $columns[$column];
        }
        return $newArray;
    }


    /**
     * Performs a recursive array_merge. Keys with the same value will be over-written with the later value
     *
     * Given arguments like:
     * $one = ['key' => ['name' => value], 'key2' => ['name2' => value2] ]
     * $two = ['key' => ['name' => value2], 'key4' => ['name4' => value4] ]
     *
     * Transform them into an array like:
     * [key => ['name' => value2], 'key2' => ['name2' => value2], 'key4' => ['name4' => value4]]
     *
     * @params array Two or more multi-dimensional arrays
     *
     * @return void
     */
    public static function arrayMultiMergeKeys()
    {
        $args = func_get_args();

        $result = array ();
        foreach ($args as $array) {
            foreach ($array as $key => $row) {
                foreach ($row as $name => $value) {
                    $result[$key][$name] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Returns the associated value from the array based on the type of value given
     *
     * @param array      $array_map An array of the type [1 => string, int => string]
     * @param string/int $value     An integer or string to find the associated value for
     *
     * @return string or int If input was a string, the associated integer,
     *                       If was integer, the associated string
     */
    public static function arrayKeyAssoc($array_map, $value)
    {

        if (is_string($value)) {
            if (($val = array_search($value, $array_map)) === false)
                throw new Exception('Value string undefined: ' . $value);
            return $val;
        } else if (is_int($value)) {
                if (!array_key_exists('' . $value, $array_map))
                    throw new Exception('Key value undefined: ' . $value);
                return $array_map['' . $value];
        }

        throw new Exception('Invalid type for arrayKeyAssoc: ' . ClassUtils::getQualifiedType($value));
    }

    /**
     * Sorts an array using another array as a guide for key order
     *
     * @param array $arrayToSort      The associative array to sort and return
     * @param array $arrayKeysInOrder An ordered list of keys in {@link $arrayToSort}
     *
     * @return array A sorted array
     */
    public static function arraySortUsingKeys($arrayToSort, $arrayKeysInOrder)
    {
        $result = array();
        foreach($arrayKeysInOrder as $key)
            if(array_key_exists($key, $arrayToSort))
                $result[] = $arrayToSort[$key];

        return $result;
    }

    /**
     * Function array_insert().
     *
     * Returns the new number of the elements in the array.
     *
     * @param array $array Array (by reference)
     * @param mixed $value New element
     * @param int $offset Position
     * @return int
     */
    public static function arrayInsert(&$array, $value, $offset)
    {
        if (is_array($array)) {
            $array  = array_values($array);
            $offset = intval($offset);
            if ($offset < 0 || $offset >= count($array)) {
                array_push($array, $value);
            } elseif ($offset == 0) {
                array_unshift($array, $value);
            } else {
                $temp  = array_slice($array, 0, $offset);
                array_push($temp, $value);
                $array = array_slice($array, $offset);
                $array = array_merge($temp, $array);
            }
        } else {
            $array = array($value);
        }
        return count($array,COUNT_NORMAL); // vs COUNT_RECURSIVE into multi D arrays
    }

}
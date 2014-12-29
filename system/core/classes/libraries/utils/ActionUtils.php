<?php
/**
 * ActionUtils
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
 * @version     $Id: ActionUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ActionUtils - Provides methods to work with actions or datasources.
 *
 * An Action is a unique string that describes a method that works with
 * data on the server. For example, pages-edit describes the method 'edit' on the 'pages' element.
 *
 * A datasource is a method that returns data from the database.
 * For example, 'pages-items' describes the 'items' method on the 'pages' element.
 *
 * @package     CrowdFusion
 */
class ActionUtils
{
    /**
     * Translates strings like 'my-method' into 'myMethod', which is a valid method.
     *
     * @param string $method The pre-parsed method string
     *
     * @return string The translated method name, valid to call as a function
     */
    public static function parseMethod($method)
    {
        return str_replace(' ', '', ltrim(ucwords(str_replace('-', ' ', 'z' . $method)), 'Z'));
    }

    /**
     * Parses an {@link $actionOrDatasource} into an array like [element, method]
     *
     * @param string $actionOrDatasource The action or datasource to parse
     * @param bool   $allowEmptyMethod   If true, won't throw an exception when the method part is empty
     *
     * @return array an array like [element, method]; If $allowEmptyMethod is true and no method is given, returns
     *               [element, '']
     */
    public static function parseActionDatasource($actionOrDatasource, $allowEmptyMethod = false)
    {
        $result = explode('-', $actionOrDatasource, 2);
        if (count($result) != 2) {
            //no method specified, indicate default method using empty string
            if($allowEmptyMethod === true)
                return array($actionOrDatasource,'');
            else
                throw new ActionDatasourceException('Invalid action or datasource: [' . $actionOrDatasource . ']');
        }

        return $result;
    }


    /**
     * Creates a action or datasource string from an element and method.
     *
     * For example, would translate $element='pages', $method='my-method' into 'pages-my-method'
     *
     * @param string $element The element
     * @param string $method  The method
     *
     * @return string action or datasource string
     */
    public static function createActionDatasource($element, $method)
    {
        return strtolower(str_replace(' ', '', $element)) . '-' . strtolower($method);
    }

}
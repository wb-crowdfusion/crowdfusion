<?php
/**
 * ValueOptionsDef
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
 * @version     $Id: ValueOptionsDef.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ValueOptionsDef represents in an object the ValueOptions items that are defined in the XML.
 *
 * A value_option is expected to have the following properties:
 *  mode
 *  multiple
 *
 * Also contains 1 or more values.
 *  These are stored in an array like
 *      <value value="$key">$value</value>
 *
 * [$key => $value]
 *
 * @package     CrowdFusion
 * @property array $Values
 * @property string $Mode
 * @property boolean $Multiple
 */
class ValueOptionsDef extends Object
{
    public function __construct(array $fields = array())
    {
        parent::__construct($fields);

        $this->fields['Values'] = array();
    }

    public function addValue($key, $value)
    {
        $this->fields['Values'][$key] = $value;
    }

    public function getValues()
    {
        return $this->fields['Values'];
    }
} // END class ValueOptionsDef extends Object
?>
<?php
/**
 * JsonFilterer
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
 * @version     $Id: JsonFilterer.php 706 2011-10-05 15:42:32Z clayhinson $
 */

/**
 * JsonFilterer
 *
 * @package     CrowdFusion
 */
class JsonFilterer extends AbstractFilterer
{



    /**
     * Escapes the specified parameter in 'value' for use in json
     *
     * Expected Param:
     *  value mixed  if is a string, this is what will be converted
     *  index string if specified and value is an array, then value[index] will be converted
     *
     * @return string
     */
    public function encode()
    {
        if (is_null($this->getParameter('value')))
            return '""';

        $value = $this->getParameter('value');
        $index = $this->getParameter('index');

        if($index != null && is_array($value))
            return JSONUtils::encode($value[$index]);

        return JSONUtils::encode($value);
    }

    public function encodeFlat()
    {
        if ($this->getParameter('value') == null)
            return '""';

        $values = $this->getParameter('value');
        return JSONUtils::encodeFlat($values);

    }
}
<?php
/**
 * Security
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
 * @version     $Id: Security.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Security
 *
 * @package     CrowdFusion
 */
class Security implements SecurityInterface
{
    protected $filterLoggedParameters;
    protected $filterInputControlParameters;

    public function __construct($securityFilterLoggedParameters, $securityFilterInputControlParameters)
    {
        $this->filterLoggedParameters = $securityFilterLoggedParameters;
        $this->filterInputControlParameters = $securityFilterInputControlParameters;
    }

    public function filterLoggedParameters($array)
    {
        if(!empty($this->filterLoggedParameters))
            $array = array_diff_key($array, array_flip($this->filterLoggedParameters));

        return $array;
    }


    public function filterInputControlParameters($array)
    {
        if(!empty($this->filterInputControlParameters))
            $array = array_diff_key($array, array_flip($this->filterInputControlParameters));

        return $array;
    }
}
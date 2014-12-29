<?php
/**
 * StorageFacilityFactory
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
 * @version     $Id: StorageFacilityFactory.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Creates and returns StorageFacility objects
 *
 * @package     CrowdFusion
 */
class StorageFacilityFactory
{
    protected $ApplicationContext = null;

    /**
     * [IoC] Sets the application context
     *
     * @param ApplicationContext $ApplicationContext A reference the to the application context.
     *
     * @return void
     */
    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }


    /**
     * Get a storage facility prototype instance from the application context
     *
     */
    public function build(StorageFacilityInfo $sfInfo)
    {
        $storageFacility = $this->ApplicationContext->object($sfInfo->ObjectRef);
        return $storageFacility;
    }

    public function resolveParams(StorageFacilityInfo $sfInfo)
    {
        $params = $sfInfo->getStorageFacilityParams();
        $params->Domain = $this->resolveProperties(URLUtils::resolveVariables($params->Domain));
        $params->BaseURI = $this->resolveProperties(URLUtils::resolveVariables($params->BaseURI));
        $params->BaseStoragePath = $this->resolveProperties(URLUtils::resolveVariables($params->BaseStoragePath));

        return $params;
    }

    protected function resolveProperties($value)
    {
        if(preg_match_all("/\%PROPERTY_([^\%]+)\%/", $value, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $m)
            {
                $value = str_replace($m[0], $this->ApplicationContext->property($m[1]), $value);
            }
        }
        return $value;
    }

}

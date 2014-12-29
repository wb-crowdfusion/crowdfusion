<?php
/**
 * StorageFacilityInfo
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
 * @version     $Id: StorageFacilityInfo.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * StorageFacilityInfo
 *
 * @package     CrowdFusion
 * @property StorageFacilityParams $StorageFacilityParams
 * @property string $ObjectRef
 * @property string $For
 * @property bool $GenerateRewriteRules
 */
class StorageFacilityInfo extends Object
{
    /**
     * Returns the BaseURL to the storage facility
     * starting with the protocol
     *
     * @return string
     */
    public function getBaseURL()
    {
        $params = $this->getStorageFacilityParams();
        return 'http'.($params->isSSL()?'s':'')."://{$params->Domain}".preg_replace("/\/[\/]+/", "/", "{$params->BaseURI}/");
    }

}
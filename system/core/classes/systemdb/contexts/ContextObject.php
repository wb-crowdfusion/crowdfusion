<?php
/**
 * Context
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
 * @version     $Id: Context.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Context
 *
 * @package     CrowdFusion
 * @property string $Slug
 * @property boolean $Enabled
 * @property boolean $SSL
 * @property string $Name
 * @property string $Description
 * @property string $Domain
 * @property string $DomainBaseURI
 * @property string $DomainRedirects
 * @property string $DomainAlias
 * @property string $DeploymentBasePath
 * @property string $ExcludeFinalSlash
 * @property string $BaseURL
 * @property string $LiveBaseURL
 */
class ContextObject extends ModelObject
{
    public function getTableName()
    {
        return 'contexts';
    }

    public function getPrimaryKey()
    {
        return 'ContextID';
    }

    /**
     * Only used by old DB system
     *
     * @deprecated
     *
     * @return array
     */
    public function getModelSchema()
    {
        return array();
    }

    public function setStorageFacilityInfo($for, StorageFacilityInfo $sfInfo)
    {
        $this->fields['StorageFacilityInfo'][$for] = $sfInfo;
    }

    public function getStorageFacilityInfo($for = null)
    {
        if(is_null($for))
            return !empty($this->fields['StorageFacilityInfo'])?$this->fields['StorageFacilityInfo']:array();

        if(!isset($this->fields['StorageFacilityInfo'][$for]))
            return null;

        return $this->fields['StorageFacilityInfo'][$for];
    }

}
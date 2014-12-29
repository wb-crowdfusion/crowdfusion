<?php
/**
 * Element
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
 * @version     $Id: Element.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Element
 *
 * @package     CrowdFusion
 * @property int $ElementID
 * @property int $PluginID
 * @property string $Name
 * @property string $Slug
 * @property string $Description
 * @property string $BaseURL
 * @property boolean $AllowSlugSlashes
 * @property boolean $Anchored
 * @property int $AnchoredSiteID
 * #property Site $AnchoredSite
 * @property string $DefaultOrder
 * @property Date $CreationDate
 * @property Date $ModifiedDate
 * @property NodeSchema $Schema
 * @property array $Aspects
 * @property array $AspectSlugs
 */
class Element extends ModelObject
{

    public function getTableName()
    {
        return 'elements';
    }

    public function getPrimaryKey()
    {
        return 'ElementID';
    }

    public function getModelSchema()
    {
        return array(
            'ElementID'         => array('validation'=> array('datatype'  => 'int',
                                                              'nullable'  => true)),

            'Name'              => array('validation'=> array('datatype'  => 'string',
                                                              'nullable'  => false,
                                                              'min'       => 3,
                                                              'max'       => 64)),

            'Slug'              => array('validation'=> array('datatype'  => 'slug',
                                                              'nullable'  => false)),

            'Description'       => array('validation'=> array('datatype'  => 'string',
                                                              'nullable'  => true,
                                                              'max'       => 21844)),

            'BaseURL'           => array('validation'=> array('datatype'  => 'string',
                                                              'match'     => '^[0-9a-z-]+(/[0-9a-z-]+)*/$',
                                                              'nullable'  => true),
        													  'title'     => 'Base URL'),

			'AllowSlugSlashes'	=> array('validation'=> array('datatype'  => 'boolean'),
        								'default'   => false),

            'AnchoredSiteSlug'    => array('validation'=> array('datatype'  => 'slug'),
                                         'title'     => 'Site'),

            'DefaultOrder'      => array('validation'=> array('datatype'  => 'string',
                                                              'match'     => '^\S+\s(ASC|DESC)$',
                                                              'nullable'  => false),
                                         'default'   => 'Title ASC'),

            'NodeClass'         => array('validation'=> array('datatype'  => 'string',
                                                              'match'     => '^[a-zA-Z]+[0-9a-zA-Z\\\\_]*',
                                                              'nullable'  => true),
        													  'title'     => 'Node Class',
        													  'default'   => 'Node'),

//            'CreationDate'      => array('validation'=> array('datatype'  => 'date',
//                                                              'nullable'  => false),
//                                         'title'     => 'Creation Date',
//                                         'default'   => 'now'),

//            'ModifiedDate'      => array('validation'=> array('datatype'  => 'date',
//                                                              'nullable'  => false),
//                                         'title'     => 'Modified Date',
//                                         'default'   => 'now'),
        );
    }

    public function setAspects($aspects)
    {
        if(empty($aspects))
            return;

        $this->fields['Aspects'] = $aspects;

        foreach($aspects as $aspect)
        {
            $AspectSlugs[] = $aspect->Slug;
        }

        $this->fields['AspectSlugs'] = $AspectSlugs;
    }

    public function hasAspect($aspectSlug)
    {
        return in_array(ltrim($aspectSlug ,'@'), (array)$this->fields['AspectSlugs']);
    }

    public function addAspect(Aspect $aspect)
    {
        $this->fields['Aspects'][] = $aspect;
        $this->fields['AspectSlugs'][] = $aspect->Slug;
    }

//    public function setFromXML(SimpleXMLElement $xml)
//    {
//        $this->Slug = strval($xml->info->attribute('slug'));
//        $this->Name = strval($xml->info->name);
//        $this->Description = strval($xml->info->description);
//        $this->BaseURL = strval($xml->info->base_url);
//        $this->Anchored = StringUtils::strToBool($xml->info->anchored);
//        $this->DefaultOrder = strval($xml->info->default_order);
//    }

    public function setSchema(NodeSchema $schema)
    {
        $this->fields['Schema'] = $schema;
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

<?php
/**
 * CMSNavItem
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
 * @version     $Id: CMSNavItem.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * CMSNavItem
 *
 * @package     CrowdFusion
 * @property int $CMSNavItemID
 * @property int $PluginID
 * @property string $Label
 * @property string $Slug
 * @property int $SortOrder
 * @property string $ParentSlug
 * @property string $Permissions
 * @property string $URI
 * @property string $DoAddLinksFor
 */
class CMSNavItem extends ModelObject
{
    public function getTableName()
    {
        return 'cmsnavitems';
    }

    public function getPrimaryKey()
    {
        return 'CMSNavItemID';
    }

    /**
     * Defines the attributes for this object in the database
     *
     * @return array
     */
    public function getModelSchema()
    {
        return array(
            'CMSNavItemID'      => array('validation'=>array('datatype'=>'int', 'nullable'=>true)),
                'PluginID'      => array('title' => 'Plugin ID', 'validation' => array('datatype'=>'int')),
                'Label'         => array('validation' => array('datatype'=>'string', 'min' => 3)),
                'Slug'          => array('validation' => array('datatype'=>'slug')),
                'SortOrder'     => array('default' => 0, 'validation' => array('datatype'=>'int')),
                'ParentSlug'    => array('validation' => array('datatype'=>'slug', 'nullable' => true)),
                'Permissions'   => array('validation' => array('datatype'=>'string', 'nullable' => true)),
                'URI'           => array('validation' => array('datatype'=>'string', 'nullable' => true)),
                'DoAddLinksFor' => array('title' => 'Create Add Links?', 'default' => '',
                                        'validation' => array('datatype'=>'string', 'max' => 255, 'nullable' => true)),
                'Enabled'       => array('validation'=>array('datatype'=>'boolean', 'nullable'=>false), 'default'=>true)
        );
    }

}
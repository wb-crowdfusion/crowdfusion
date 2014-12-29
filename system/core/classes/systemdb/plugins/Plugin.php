<?php
/**
 * Plugin
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
 * @version     $Id: Plugin.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Plugin
 *
 * @package     CrowdFusion
 * @property int $PluginID
 * @property string $Slug
 * @property string $Version
 * @property string $Status
 * @property boolean $Installed
 * @property boolean $Locked
 * @property int $Priority
 * @property string $Title
 * @property string $Description
 * @property string $Path
 * @property string $Provider
 * @property string $License
 * @property string $Dependencies
 * @property string $CFVersions
 * @property string $Homepage
 * @property string $Md5
 * @property Date $CreationDate
 * @property Date $ModifiedDate
 */
class Plugin extends ModelObject
{
    public function getTableName()
    {
        return 'plugins';
    }

    public function getPrimaryKey()
    {
        return 'PluginID';
    }

    public function getModelSchema()
    {
        return array(
            'PluginID'          => array('validation' => array('datatype'  => 'int', 'nullable'=> true)),
            'Slug'              => array('validation' => array('datatype'  => 'slug')),
            'Version'           => array('validation' => array('datatype'  => 'string', 'max' => 64)),
            'Enabled'           => array('validation' => array('datatype'  => 'bool'),
                                         'default'    => false),
            'Installed'         => array('validation' => array('datatype'  => 'bool'),
                                         'default'    => false),
            'Locked'            => array('validation' => array('datatype'  => 'bool'),
                                         'default'    => false),
            'Priority'          => array('validation' => array('datatype'  => 'int'),
                                         'default'    => 100),
            'Title'             => array('validation' => array('datatype'  => 'string', 'min' => 3, 'max' => 64)),
            'Description'       => array('validation' => array('datatype'  => 'string', 'nullable'=> true, 'max' => 21844)),
            'Path'              => array('validation' => array('datatype'  => 'string', 'max' => 255)),
            'Provider'          => array('validation' => array('datatype'  => 'string', 'nullable'=> true, 'max' => 255)),
            'License'           => array('validation' => array('datatype'  => 'string', 'nullable'=> true, 'max' => 21844)),
            'Dependencies'      => array('validation' => array('datatype'  => 'string', 'nullable'=> true, 'max' => 21844)),
            'CFVersions'        => array('validation' => array('datatype'  => 'string', 'nullable'=> true, 'max' => 255)),
            'Homepage'          => array('validation' => array('datatype'  => 'string', 'nullable'=> true, 'max' => 255)),
            'Md5'               => array('validation' => array('datatype'  => 'string', 'nullable'=> true, 'max' => 32)),

//            'CreationDate'      => array(
//                'title' => 'Creation Date',
//                'default'   => 'now',
//                'validation' => array('datatype'  => 'date')),
            'ModifiedDate'      => array(
                'title' => 'Modified Date',
                'default'   => 'now',
                'validation' => array('datatype'  => 'date'))
        );

    }

    public function compareTo($a, $b)
    {
        $a1 = $a instanceof Plugin ? intval($a->Priority) : intval($a['Priority']);
        $b1 = $b instanceof Plugin ? intval($b->Priority) : intval($b['Priority']);

        if($a1 == $b1) {

            $a2 = $a instanceof Plugin ? $a->Enabled : $a['Enabled'];
            $b2 = $b instanceof Plugin ? $b->Enabled : $b['Enabled'];

            $a2 = empty($a2) ? 1 : 0;
            $b2 = empty($b2) ? 1 : 0;

            if($a2 == $b2)
                return 0;

            return $a2 < $b2 ? -1 : 1;
        }

        return $a1 < $b1 ? -1 : 1;
    }

//    public function setFromXML(SimpleXMLExtended $xml)
//    {
//        if(($id = $xml->attribute('id')) !== null)
//            $this->PluginID = intval($xml->attribute('id'));
//
//        if(($id = $xml->attribute('slug')) !== null)
//            $this->Slug = strval($xml->attribute('slug'));
//
//        if(($id = $xml->attribute('installed')) !== null)
//            $this->Installed = StringUtils::strToBool($xml->attribute('installed'));
//
//        if(($id = $xml->attribute('enabled')) !== null)
//            $this->Enabled = StringUtils::strToBool($xml->attribute('enabled'));
//
//        $this->Path = strval($xml->path);
//        $this->ModifiedDate =
//
//        $this->Version = strval($xml->info->version);
//        $this->Priority = intval($xml->info->priority);
//        $this->Title = strval($xml->info->title);
//        $this->Description = strval($xml->info->description);
//        $this->Provider = strval($xml->info->provider);
//        $this->License = strval($xml->info->license);
//        $this->Dependencies = strval($xml->info->dependencies);
//        $this->CFVersions = strval($xml->info->cfversions);
//        $this->Homepage = strval($xml->info->homepage);
//        $this->Locked = StringUtils::strToBool($xml->info->locked);
//    }

}
<?php
/**
 * Aspect
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
 * @version     $Id: Aspect.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Aspect
 *
 * @package     CrowdFusion
 * @property int $AspectID
 * @property int $PluginID
 * @property string $Name
 * @property string $Slug
 * @property string $Description
 * @property string $XMLSchema
 * @property Date $CreationDate
 * @property Date $ModifiedDate
 * @property NodeSchema $Schema
 */
class Aspect extends ModelObject
{
    public function getTableName()
    {
        return 'aspects';
    }

    public function getPrimaryKey()
    {
        return 'AspectID';
    }

    public function getModelSchema()
    {
        return array(
            'AspectID'      => array('validation' => array('datatype'=> 'int', 'nullable'=> true)),
            'PluginID'      => array('validation' => array('datatype'=> 'int')),
            'Name'          => array('validation' => array('datatype'=> 'string', 'min' => 3, 'max' => 64)),
            'Slug'          => array('validation' => array('datatype'=> 'slug')),
            'Description'   => array('validation' => array('datatype'=> 'string', 'nullable'=> true)),
            'XMLSchema'     => array('validation' => array('datatype'=> 'string', 'nullable' => true)),
            'ElementMode'   => array('validation' => array('datatype'=> 'string', 'match' => 'many|one|anchored', 'nullable' => false),
                                        'default' => 'many'),
            'Md5'           => array('validation' => array('datatype'=> 'string', 'nullable'=> false, 'max' => 32)),
//            'CreationDate'  => array(
//                'title' => 'Creation Date',
//                'validation' => array('datatype'=>'date'),
//                'default' => 'now'),
            'ModifiedDate'  => array(
                'title' => 'Modified Date',
                'validation' => array('datatype'=>'date'),
                'default' => 'now')
        );

    }

    public function setSchema(NodeSchema $schema)
    {
        $this->fields['Schema'] = $schema;
    }

    public function setFromXML(SimpleXMLExtended $xml)
    {
        $this->Name = strval($xml->info->name);
        $this->Description = strval($xml->info->description);
        $this->ElementMode = strval($xml->info->elementmode);
        if (!empty($xml->md5)) {
            $this->Md5 = strval($xml->md5);
        } else {
            $this->Md5 = '';
        }

        $this->XMLSchema = '';
        if(!empty($xml->meta_defs))
            $this->XMLSchema .= strval($xml->meta_defs->asPrettyXML());
        if(!empty($xml->tag_defs))
            $this->XMLSchema .= strval($xml->tag_defs->asPrettyXML());
        if(!empty($xml->section_defs))
            $this->XMLSchema .= strval($xml->section_defs->asPrettyXML());
    }

}
<?php
/**
 * NodeSchema
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
 * @version     $Id: NodeSchema.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeSchema
 *
 * @package     CrowdFusion
 * @property array $TagDefs
 * @property array $MetaDefs
 * @property array $MetaStorageDatatypes
 * @property TagDef $TreeOriginTagDef
 */
class NodeSchema extends Object
{

//    public function __construct()
//    {
//        parent::__construct();
//        $this->fields['SectionDefs'] = array();
//    }
//
//    public function getSectionDefs()
//    {
//        return $this->fields['SectionDefs'];
//    }
//
//    public function addSectionDef(SectionDef $section)
//    {
//        if(array_key_exists($section->Id, $this->fields['SectionDefs']))
//            throw new SchemaException('Section with id ['.$section->Id.'] already exists in schema');
//
//        $this->fields['SectionDefs'][$section->Id] = $section;
//
//        return $this;
//    }
//
//    public function getSectionDef($type)
//    {
//        if(array_key_exists($type, $this->fields['SectionDefs']))
//            return $this->fields['SectionDefs'][$type];
//
//        throw new SchemaException('Section definition not found for id ['.$type.']');
//    }
//
//
//    public function hasSectionDef($type)
//    {
//        return array_key_exists($type, $this->fields['SectionDefs']);
//    }

    public function __construct()
    {
        $this->fields['TagDefs'] = array();
        $this->fields['MetaDefs'] = array();
        $this->fields['MetaStorageDatatypes'] = array();
    }

//    protected $tags     = array();
//    protected $metas    = array();

    public function getTagDefs()
    {
        return $this->fields['TagDefs'];
    }

    public function getMetaDefs()
    {
        return $this->fields['MetaDefs'];
    }

    public function getMetaStorageDatatypes()
    {
        return $this->fields['MetaStorageDatatypes'];
    }

    public function addTagDef(TagDef $tag)
    {
        if(array_key_exists($tag->Id, $this->fields['TagDefs']))
            throw new SchemaException('Tag with id ['.$tag->Id.'] already exists in schema');

        if($tag->isTreeorigin())
            $this->fields['TreeOriginTagDef'] = $tag;

        $this->fields['TagDefs'][$tag->Id] = $tag;

        return $this;
    }

    public function addMetaDef(MetaDef $meta)
    {
        if(array_key_exists($meta->Id, $this->fields['MetaDefs']))
            throw new SchemaException('Meta with id ['.$meta->Id.'] already exists in schema');

        $this->fields['MetaDefs'][$meta->Id] = $meta;

        if(!in_array($meta->Datatype, $this->fields['MetaStorageDatatypes']))
            $this->fields['MetaStorageDatatypes'][] = $meta->Datatype;

        return $this;
    }

    public function getTagDef($role)
    {
        $role = ltrim($role, '#');
        if(array_key_exists($role, $this->fields['TagDefs']))
            return $this->fields['TagDefs'][$role];

        throw new SchemaException('Tag definition not found for role ['.$role.']');
    }

    public function getMetaDef($id)
    {
        $id = ltrim($id, '#');
        if(array_key_exists($id, $this->fields['MetaDefs']))
            return $this->fields['MetaDefs'][$id];

        throw new SchemaException('Meta definition not found for id ['.$id.']');
    }

    public function hasTagDef($role)
    {
        $role = ltrim($role, '#');
        return array_key_exists($role, $this->fields['TagDefs']);
    }

    public function hasMetaDef($id)
    {
        $id = ltrim($id, '#');
        return array_key_exists($id, $this->fields['MetaDefs']);
    }

}
<?php
/**
 * SchemaXML
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
 * @version     $Id: SchemaXML.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SchemaXML
 *
 * @package     CrowdFusion
 */
class SchemaXML extends SimpleXMLExtended {

//    public function getFieldsWithValidation() {
//        return $this->xpath('field_defs/field/validation/parent::*');
//    }

    public function getMetasWithValidation() {
        return $this->xpath('meta_defs/meta/validation/parent::*');
    }

    public function getTagsWithValidation() {
        return $this->xpath('tag_defs/tag/validation/parent::*');
    }

    public function getTags() {
        return $this->xpath('tag_defs/tag');
    }

    public function getMetas()
    {
        return $this->xpath('meta_defs/meta');
    }

//    public function getSections() {
//        return $this->xpath('section_defs/section');
//    }

    public function getAllElementsWithValidation() {
        // Returns all elements that have validation blocks
        return $this->xpath("//validation/parent::*");
    }

//    public function getSection($type) {
//        $p = $this->xpathOne("section_defs/section[@id='{$type}']");
//        if(empty($p)) throw new Exception('Section definition for type ['.$type.'] not found');
//        return $p;
//    }

    public function getTag($id) {
        $p = $this->xpathOne("tag_defs/tag[@id='{$id}']");
        if(empty($p)) throw new Exception('Tag definition for id ['.$id.'] not found');
        return $p;
    }

    public function getMeta($id) {
        $p = $this->xpathOne("meta_defs/meta[@id='{$id}']");
        if(empty($p)) throw new Exception('Meta definition for id ['.$id.'] not found');
        return $p;
    }

//    public function getField($id) {
//        $p = $this->xpathOne("field_defs/field[@id='{$id}']");
//        if(empty($p)) throw new Exception('Field definition for id ['.$id.'] not found');
//        return $p;
//    }


    public function getByID($id) {
        $p = $this->xpathOne("*/*[@id='{$id}']");
        if(empty($p)) throw new Exception('Schema Definition for id ['.$id.'] not found');
        return $p;
    }

//    public function hasField($id) {
//        // Returns true if field exists
//        $element = $this->xpathOne("field_defs/field[@id='{$id}']");
//        return !empty($element);
//    }

    public function hasMeta($id) {
        $element = $this->xpathOne("meta_defs/meta[@id='{$id}']");
        return !empty($element);
    }

    public function hasTag($id) {
        $element = $this->xpathOne("tag_defs/tag[@id='{$id}']");
        return !empty($element);
    }

//    public function hasSection($type) {
//        $element = $this->xpathOne("section_defs/section[@id='{$type}']");
//        return !empty($element);
//    }

    public function isTag() {
        return $this->getName() == 'tag';
    }
    public function isMeta() {
        return $this->getName() == 'meta';
    }
//    public function isField() {
//        return $this->getName() == 'field';
//    }

//    public function isSection() {
//        return $this->getName() == 'section';
//    }

}


?>

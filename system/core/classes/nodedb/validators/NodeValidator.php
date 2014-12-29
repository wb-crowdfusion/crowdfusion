<?php
/**
 * NodeValidator
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
 * @version     $Id: NodeValidator.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeValidator
 *
 * @package     CrowdFusion
 */
class NodeValidator extends AbstractValidator
{
    public function setTagsHelper(TagsHelper $TagsHelper)
    {
        $this->TagsHelper = $TagsHelper;
    }

    public function setNodeDBMeta(NodeDBMeta $NodeDBMeta)
    {
        $this->NodeDBMeta = $NodeDBMeta;
    }

    public function setNodeLookupDAO(NodeLookupDAO $NodeLookupDAO)
    {
        $this->NodeLookupDAO = $NodeLookupDAO;
    }

    public function setNodeEvents(NodeEvents $NodeEvents)
    {
        $this->NodeEvents = $NodeEvents;
    }

    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

    protected function add(Node $node)
    {
        $errors =& $this->getErrors();
        $nodeRef =& $node->getResolvedNodeRef();

        $this->NodeEvents->fireValidationEvents(__FUNCTION__, $errors, $nodeRef, $node);

        $nodeRef =& $node->getResolvedNodeRef();

        $this->validateNode($node, 'add');

        $errors->throwOnError();

        if(!$nodeRef->isFullyQualified())
            $this->getErrors()->reject('Cannot add node without Slug')->throwOnError();

        if($this->NodeLookupDAO->refExists($nodeRef))
            $this->getErrors()->rejectField('exists', $this->resolveField($nodeRef, 'Slug'), 'Slug',
                                            $node->getSlug(), $node->getNodeRef()->getElement()->getName().' record with slug ['.$node->getSlug().'] already exists.');

    }

    protected function edit(Node $node)
    {
        if (($existingNode = $this->NodeLookupDAO->getByNodeRef($node->getNodeRef())) == false) {
            $this->getErrors()->reject('Record not found for NodeRef: '.$node->getNodeRef()->getRefURL())->throwOnError();
        }

        $errors =& $this->getErrors();
        $nodeRef =& $node->getNodeRef();

        $this->NodeEvents->fireValidationEvents(__FUNCTION__, $errors, $nodeRef, $node);

        // validate object
        $this->validateNode($node);

        $errors->throwOnError();

        if(!$node->getNodeRef()->isFullyQualified())
            $this->getErrors()->reject('Cannot edit node without fully qualified NodeRef')->throwOnError();

    }

    protected function rename(NodeRef $nodeRef, NodeRef $newNodeRef)
    {
        if ($this->NodeLookupDAO->refExists($newNodeRef))
            $this->getErrors()->rejectField('exists', $this->resolveField($nodeRef, 'Slug'), 'Slug',
                                            $nodeRef->getSlug(), $nodeRef->getElement()->getName().' record with slug ['.$newNodeRef->getSlug().'] already exists.');

        $errors = $this->getErrors();

        $this->NodeEvents->fireValidationEvents(__FUNCTION__, $errors, $nodeRef, $newNodeRef);
    }

    protected function delete(NodeRef $nodeRef)
    {
        if(!$nodeRef->isFullyQualified())
            $this->getErrors()->reject('Cannot delete node without Slug')->throwOnError();

        if (!$this->NodeLookupDAO->refExists($nodeRef)) {
            $this->getErrors()->reject('Record not found for NodeRef: '.$nodeRef->getRefURL());
            return;
        }
        $errors = $this->getErrors();

        $this->NodeEvents->fireValidationEvents(__FUNCTION__, $errors, $nodeRef);
    }






    // ====================
    // = VALIDATE METHODS =
    // ====================
    public function resolveField( NodeRef $nodeRef, $fieldId )
    {
        $fields = array($nodeRef->getElement()->getSlug());

        $fields[] = $fieldId;

        return join($fields, '.');
    }




    public function validateNode(Node $node, $mode = 'edit') {
        // Validates all fields, metas, and tags defined in the current scope

        $this->Logger->debug('Validating node ['.$node->getNodeRef().'] with partials ['.$node->getNodePartials().']');

        // Fields
        // Check the model schema for NodeDBMeta to check fields
        foreach ( $this->NodeDBMeta->getModelSchema() as $fieldName => $props ) {

            $value         = $node->$fieldName;
            $fieldResolved = $this->resolveField($node->getNodeRef(), $fieldName);
            $title         = isset($props['title']) ? $props['title'] : StringUtils::titleize($fieldName);
            $validation    = $props['validation'];

            if($fieldName == 'Slug' && $node->getNodeRef()->getElement()->isAllowSlugSlashes())
                $validation['datatype'] = 'slugwithslash';

            if($fieldName != 'Slug' || ($fieldName == 'Slug' && !$this->getErrors()->hasFieldError($this->resolveField($node->getNodeRef(), 'Title'))))
                $this->getErrors()->rejectIfInvalid(
                    $fieldResolved,
                    'field',
                    $title,
                    $value,
                    new ValidationExpression($validation));
        }


        // SCHEMA VALIDATION
        $schema = $node->getNodeRef()->getElement()->getSchema();

        // Validate tags scope
        // Find all meta tags and validate them against the partials string
        $this->validateMetaPartials($node->getNodePartials()->getMetaPartials(), $node->getMetas(), $schema);

        // Validate OutTag scope
        $node->setOutTags($this->validateTagPartials('out', $node->getNodePartials()->getOutPartials(), $node->getOutTags(), $schema));

        // Validate InTag scope
        $node->setInTags($this->validateTagPartials('in', $node->getNodePartials()->getInPartials(), $node->getInTags(), $schema));

        // Validate Sections scope
        //$this->validateSectionPartials($node->getNodePartials()->getSectionPartials(), $node->getSections(), $schema);

        // Validate metas
        $this->validateMetas($node, $node, $schema, $mode);

        // Validate tags
        $this->validateTags($node, $node, $schema);

        // Sections
//        if (is_array($node->getSections())) {
//            $section_type_counts = array();
//            foreach( $node->getSections() as $key => $section ) {
//                $section_type = $section->getSectionType();
//                $section_def  = $schema->getSectionDef($section_type);
//
//                @$section_type_counts[$section_type]++;
//
//                $section_title = $section_def->Title;
//                if ($section_def->Max == '*' || (integer)$section_def->Max > 1) {
//                    $section_title .= " #" . $section_type_counts[$section_type];
//                }
//
//                // Validate Schema Metas
//                $this->validateMetas($node, $section, $section_def);
//
//                // Validate Schema Tags
//                $this->validateTags($node, $section, $section_def);
//            }
//        }

        return $this->getErrors();
    }

    protected function validateMetas(Node $node, Node $obj, NodeSchema $schema, $mode)
    {
        $processed_metas = array();
        $metas = $obj->getMetas();

        // Check all metas passed against the meta defs.
        foreach ( $metas as $meta ) {
            // Ensure the meta has a meta_def
            $meta_def = $schema->getMetaDef($meta->getMetaName());

            $this->getErrors()->rejectIfInvalid($this->resolveField($node->getNodeRef(), $meta->getMetaName(), $obj instanceof Section?$obj:null), 'meta', $meta_def->Title, $meta->getValue(), $meta_def->Validation);

            $processed_metas[] = $meta->getMetaName();
        }

        $meta_defs = $schema->getMetaDefs();
        // Check all meta defs that haven't been processed
        // These meta defs must only match partials that we have.
        $meta_partials = PartialUtils::unserializeMetaPartials($node->getNodePartials()->getMetaPartials());
        $re_meta_partials = PartialUtils::unserializeMetaPartials($node->getNodePartials()->getRestrictedMetaPartials());

        foreach ( $meta_defs as $meta_def ) {
            if (in_array($meta_def->Id, $processed_metas))
                continue;

            if ($mode == 'edit' && !$this->metaInScope($schema, new Meta($meta_def->Id, ''), $meta_partials, $re_meta_partials))
                continue;

            // Validate the meta def with a null value
            $this->getErrors()->rejectIfInvalid($this->resolveField($node->getNodeRef(), $meta_def->Id, ($obj instanceof Section) ? $obj : null),
                                                'meta', $meta_def->Title, null, $meta_def->Validation);

            $processed_metas[] = $meta_def->Id;
        }

    }

    protected function validateTags(Node $node, Node $obj, NodeSchema $schema)
    {
        $in_tags  = array();
        $out_tags = array();
        $tag_defs = $schema->getTagDefs();

        $tag_strings = array();

        // Validate each tag def
        foreach ( $tag_defs as $tag_def ) {
            $allowed_values = array();
            $value_multiple = false;
            $value_mode     = "none";
            $value_options  = $tag_def->ValueOptions;

            if ($value_options) {
                $value_multiple = $value_options->isMultiple();
                $value_mode     = strtolower((string)$value_options->Mode);

                if ($value_mode == 'predefined') {
                    // Extract values from the value tags
                    foreach( $value_options->getValues() as $value => $title ) {
                        $allowed_values[] = (string)$value;
                    }
                }
            } else {
                $allowed_values[] = $tag_def->Partial->getTagValue();
            }

            if (strtolower($tag_def->Direction) == 'in') {
                $tags = $obj->getInTags($tag_def->Partial->getTagRole());
                //$tag_strings = &$in_tags;
            } else {
                $tags = $obj->getOutTags($tag_def->Partial->getTagRole());
                //$tag_strings = &$out_tags;
            }


            // remove duplicates from tags
            foreach($tags as $o => $tag) {
                foreach($tags as $i => $dtag) {
                    if($o != $i && $tag->matchExact($dtag))
                    {
                        unset($tags[$o]);
                    }
                }
            }

            // Validate multiple across the entire tag
            if (!$tag_def->isMultiple() && count($tags) > 1)
                $this->getErrors()->reject('Multiple tags not supported for tag role: '.$tag_def->Id);

            foreach( $tags as $tag ) {
                // Validate Tag direction
                if (strcasecmp($tag_def->Direction, $tag->getTagDirection()) !== 0) {
                    $this->getErrors()->reject('Invalid tag direction. Should be an ' . $tag_def->Direction . ' tag. Tag: ' . $tag);
                    break; // Don't even attempt to validate a tag in the wrong direction.
                }

                if (!$this->TagsHelper->matchPartial($tag_def->Partial, $tag))
                    $this->getErrors()->reject('Invalid tag ['.$tag->toString().'] does not match tag definition partial ['.$tag_def->Partial.']');

                if ($value_mode == 'none' && !empty($tag->TagValue) ) {
                    $this->getErrors()->reject('Tag has value where none is allowed:' .$tag->toString());
                }

                if ($value_mode == 'predefined' && !in_array($tag->TagValue, $allowed_values)) {
                    $this->getErrors()->reject('Value of "' . $tag->TagValue . '" not allowed for tag: ' . $tag->toString());
                }

                if ($value_mode != 'none') {

                    $key = $tag->TagElement.':'.$tag->TagSlug;

                    @$values_per_slug[$key]++;
                    if (!$value_multiple && $values_per_slug[$key] > 1) {
                        $this->getErrors()->reject('Multiple values not supported for tag partial: ' . $tag_def->Partial->toString());
                    }

                }

                $tag_strings[] = $tag->toString();
            }
        }

        // Find any extra tags that haven't been validated
        foreach( $obj->getOutTags() as $tag ) {
            if (!is_object($tag))
                $tag = new Tag($tag);
            if (!in_array($tag->toString(), $tag_strings)) {
                $this->getErrors()->reject("Attached out tag doesn't exist in tag definitions: " . $tag->toString());
            }
        }

        foreach( $obj->getInTags() as $tag ) {
            if (!is_object($tag))
                $tag = new Tag($tag);
            if (!in_array($tag->toString(), $tag_strings)) {
                $this->getErrors()->reject("Attached in tag doesn't exist in TagDefs: " . $tag->toString());
            }
        }
    }

    protected function validateTagPartials($direction, $partials_string, $tags, NodeSchema $schema)
    {
        if (empty($partials_string)) {
            if (count($tags) > 0) {
                $this->Logger->debug('Removing all out-of-scope tags.');
                return array();//$this->getErrors()->reject(ucfirst($direction) . " tags specified, even though none are in scope.");
            }
        } else {

            switch ( $direction ) {
                case 'in':
                    $partials = PartialUtils::unserializeInPartials($partials_string);
                    break;
                case 'out':
                    $partials = PartialUtils::unserializeOutPartials($partials_string);
                    break;
                default:
                    throw new Exception("Unknown direction $direction for partials.");
            }


            $all = false;
            $allfieldlike = false;

            if($partials == 'all' || ($x = array_search('all', $partials)) !== false)
            {
                $all = true;
            } else if(($x = array_search('fields', $partials)) !== false)
            {
                $allfieldlike = true;
                unset($partials[$x]);
            }

            foreach ($tags as $k => $tag) {
                $id = $tag->getTagRole();
                if(empty($id))
                    throw new Exception(ucfirst($direction) ." Tag passed without a TagRole");

                $tagDef = $schema->getTagDef($id);

                if($all)
                    continue;

                if($allfieldlike && $tagDef->isFieldlike())
                    continue;

                $inScope = false;
                foreach( $partials as $partial ) {
                    if($this->TagsHelper->matchPartial($partial, $tag)){
                        $inScope = true;
                        break;
                    }
                }
                if(!$inScope){
                    $this->Logger->debug('Silently removing out-of-scope tag: '.$tag->toString());
                    unset($tags[$k]);
                }
//                    $this->getErrors()->reject(ucfirst($direction) ." Tag: " . $tag->toString() . ' is out of scope.');
            }

        }

        return $tags;
    }

    protected function validateMetaPartials($partials_string, $tags, NodeSchema $schema)
    {
        if (empty($partials_string)) {
            if (count($tags) > 0)
                $this->getErrors()->reject("Meta specified, even though none are in scope.");
        } else {

            $partials = PartialUtils::unserializeMetaPartials($partials_string);

            foreach ($tags as $tag) {
                if(!$this->metaInScope($schema, $tag, $partials))
                    $this->getErrors()->reject("Meta: " . $tag->toString() . ' is out of scope.');
            }

        }
    }


    protected function metaInScope($schema, $meta, $partials, $re_partials = array())
    {
       foreach( (array)$re_partials as $partial ) {
            if($partial->match($meta)){
                return false;
            }
        }

        if($partials == 'all')
            return true;

        $id = $meta->getMetaName();
        if(empty($id))
            throw new Exception("Meta passed without a MetaName");

        $metaDef = $schema->getMetaDef($id);

        $inScope = false;
        foreach( $partials as $partial ) {
            if($partial->match($meta)){
                $inScope = true;
                break;
            }
        }
        if(!$inScope)
            return false;

        return true;
    }


//    protected function validateSectionPartials($partials_string, $sections)
//    {
//        if (empty($partials_string)) {
//            if (count($sections) > 0)
//                $this->getErrors()->reject("Sections specified, even though none are in scope.");
//        } else if($partials_string != 'all') {
//
//            $partials = PartialUtils::unserializeSectionPartials($partials_string);
//
//            foreach ($sections as $section) {
//
//                $id = $section->getSectionType();
//                if(empty($id))
//                    throw new Exception("Section passed without a SectionType");
//
//                $inScope = false;
//                foreach( $partials as $partial ) {
//                    if($partial->match($section)){
//                        $inScope = true;
//                        break;
//                    }
//                }
//                if(!$inScope)
//                    $this->getErrors()->reject("Sections of type [" . $id . '] are out of scope.');
//            }
//
//        }
//    }

}

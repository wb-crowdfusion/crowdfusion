<?php
/**
 * NodeBinder
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
 * @version     $Id: NodeBinder.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeBinder
 *
 * @package     CrowdFusion
 */
class NodeBinder
{
    /**
     * [IoC] Inject NodeValidator
     *
     * @param ValidatorInterface $NodeValidator injected
     *
     * @return void
     */
    public function setNodeValidator(ValidatorInterface $NodeValidator)
    {
        $this->NodeValidator = $NodeValidator;
    }

    /**
     * [IoC] Inject InputClean
     *
     * @param InputCleanInterface $InputClean injected
     *
     * @return void
     */
    public function setInputClean(InputCleanInterface $InputClean)
    {
        $this->InputClean = $InputClean;
    }

    /**
     * [IoC] Inject NodeDBMeta
     *
     * @param NodeDBMeta $NodeDBMeta injected
     *
     * @return void
     */
    public function setNodeDBMeta(NodeDBMeta $NodeDBMeta)
    {
        $this->NodeDBMeta = $NodeDBMeta;
    }

    /**
     * [IoC] Inject DateFactory
     *
     * @param DateFactory $DateFactory injected
     *
     * @return void
     */
    public function setDateFactory($DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    /**
     * [IoC] Inject AspectService
     *
     * @param AspectService $AspectService injected
     *
     * @return void
     */
    public function setAspectService(AspectService $AspectService)
    {
        $this->AspectService = $AspectService;
    }

    /**
     * [IoC] Inject Events
     *
     * @param Events $Events injected
     *
     * @return void
     */
    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    public function setTypeConverter(TypeConverter $TypeConverter)
    {
        $this->TypeConverter = $TypeConverter;
    }

    /**
     * Binds the values from {@link $fields} and {@link $rawFields} to the {@link &$node}
     * Any errors from this process are stored in {@link &$errors}
     *
     * @param Node   &$node
     * @param Errors &$errors
     * @param array  $fields
     * @param array  $rawFields
     *
     * @return void
     */
    public function bindPersistentFields(Node &$node, Errors &$errors, array $fields, array $rawFields)
    {
        $pFields = array_intersect_key($fields,array_flip($this->NodeDBMeta->getPersistentFields()));

        foreach($pFields as $key => $value) {
//            $validation = $this->NodeDBMeta->getValidation($key);

            try {
                $pFields[$key] = $this->TypeConverter->convertFromString($this->NodeDBMeta->getValidationExpression($key), $fields[$key], $rawFields[$key]);
            } catch(TypeConversionException $tce)
            {
                $errors->addFieldError('invalid', $key, 'field', $this->NodeDBMeta->getFieldTitle($key), $fields[$key], $tce->getMessage());
            }
        }

        $node->setFromArray($pFields);

    }

    public function fireAddBindEvents(Node &$node, Errors &$errors, array $fields, array $rawFields)
    {
        $this->fireBindEvents('add', $node, $errors, $fields, $rawFields);
    }

    public function fireEditBindEvents(Node &$node, Errors &$errors, array $fields, array $rawFields)
    {
        $this->fireBindEvents('edit', $node, $errors, $fields, $rawFields);
    }

    protected function fireBindEvents($action, Node &$node, Errors &$errors, array $fields, array $rawFields)
    {
        if(isset($fields['OutTags_partials']))
            foreach(StringUtils::smartSplit($fields['OutTags_partials'], ',', '"', '\\"') as $partial)
                $node->getNodePartials()->increaseOutPartials($partial);

        if(isset($fields['InTags_partials']))
            foreach(StringUtils::smartSplit($fields['InTags_partials'], ',', '"', '\\"') as $partial)
                $node->getNodePartials()->increaseInPartials($partial);

        foreach((array)$node->getNodeRef()->getElement()->getAspects() as $aspect) {

            $this->Events->trigger('Node.@'.$aspect->Slug.'.bind', $action, $node, $errors, $fields, $rawFields);

            $schema = $aspect->getSchema();

            foreach($schema->getMetaDefs() as $metaDef) {

                $bound = $this->Events->trigger('Node.@'.$aspect->Slug.'.meta.#'.$metaDef->Id.'.bind', $action, $node, $errors, $fields, $rawFields);
                if(!$bound)
                    $this->bindMeta($node, $errors, $fields, $rawFields, $metaDef->Id);

            }

            foreach($schema->getTagDefs() as $tagDef) {
                if($tagDef->Direction == 'in') {

                    $bound = $this->Events->trigger('Node.@'.$aspect->Slug.'.intags.#'.$tagDef->Id.'.bind', $action, $node, $errors, $fields, $rawFields);
                    if(!$bound && $tagDef->isFieldlike())
                        $this->bindInTags($node, $errors, $fields, $rawFields, $tagDef->Id);

                } else {

                    $bound = $this->Events->trigger('Node.@'.$aspect->Slug.'.outtags.#'.$tagDef->Id.'.bind', $action, $node, $errors, $fields, $rawFields);
                    if(!$bound && $tagDef->isFieldlike())
                        $this->bindOutTags($node, $errors, $fields, $rawFields, $tagDef->Id);

                }
            }


        }

        $errors->throwOnError();
    }





    public function bindAllForNode(Node &$node, Errors &$errors, array $fields, array $rawFields)
    {
        $schema = $node->getNodeRef()->getElement()->getSchema();

//        foreach($schema->getSectionDefs() as $sectionDef)
//            $this->bindSections($node, $errors, $fields, $rawFields, $sectionDef->Id);

        $this->bindAll($node, $schema, $errors, $fields, $rawFields);
    }

    public function bindAllMetaForNode(Node &$node, Errors &$errors, array $fields, array $rawFields)
    {
        $schema = $node->getNodeRef()->getElement()->getSchema();

        foreach($schema->getMetaDefs() as $metaDef)
            $this->bindMeta($node, $errors, $fields, $rawFields, $metaDef->Id);
    }


    public function bindAllTagsForNode(Node &$node, Errors &$errors, array $fields, array $rawFields, $increasePartials = false)
    {
        $schema = $node->getNodeRef()->getElement()->getSchema();

        foreach($schema->getTagDefs() as $tagDef) {
            if($tagDef->Direction == 'in')
                $this->bindInTags($node, $errors, $fields, $rawFields, $tagDef->Id, $increasePartials);
            else
                $this->bindOutTags($node, $errors, $fields, $rawFields, $tagDef->Id, $increasePartials);
        }
    }

    public function bindAllForAspect($aspect, Node &$node, Errors &$errors, array $fields, array $rawFields)
    {
        $schema = $this->AspectService->getBySlug($aspect)->getSchema();

//        foreach($schema->getSectionDefs() as $sectionDef)
//            $this->bindSections($node, $errors, $fields, $rawFields, $sectionDef->Id);

        $this->bindAll($node, $schema, $errors, $fields, $rawFields);
    }

    protected function bindAll(Node &$node, NodeSchema &$schema, Errors &$errors, array $fields, array $rawFields )
    {
        foreach($schema->getTagDefs() as $tagDef) {
            if($tagDef->Direction == 'in')
                $this->bindInTags($node, $errors, $fields, $rawFields, $tagDef->Id);
            else
                $this->bindOutTags($node, $errors, $fields, $rawFields, $tagDef->Id);
        }

        foreach($schema->getMetaDefs() as $metaDef)
            $this->bindMeta($node, $errors, $fields, $rawFields, $metaDef->Id);

    }

//    public function bindSections(Node &$node, Errors &$errors, array $fields, array $rawFields, $sectionType)
//    {
//        $schema = $node->getNodeRef()->getElement()->getSchema();
//        $sectionsArray = array();
//
//        $hashed = '#'.ltrim($sectionType, '#');
//        if(array_key_exists(NodeMapper::SECTIONS, $fields) && is_array($fields[NodeMapper::SECTIONS])
//           && array_key_exists($hashed, $fields[NodeMapper::SECTIONS]) && is_array($fields[NodeMapper::SECTIONS][$hashed])
//        ) {
//
//            $sections = $fields[NodeMapper::SECTIONS][$hashed];
//            $rawSections = $rawFields[NodeMapper::SECTIONS][$hashed];
//            $sectionSchema = $schema->getSectionDef($sectionType);
//
//            foreach((array)$sections as $k => $rawSection) {
//
//                $section = new Section($node->getNodeRef(), $sectionType, $rawSection);
//
//                $this->bindAll($section, $sectionSchema, $errors, $rawSection, $rawSections[$k]);
//
//                $sectionsArray[] = $section;
//            }
//
//        }
//
//        $node->replaceSections($sectionType, $sectionsArray);
//
//    }

    public function bindOutTags(Node &$node, Errors &$errors, array $fields, array $rawFields, $tagRole, $increasePartials = false)
    {
        $tagsArray = array();
        $hashed = '#'.ltrim($tagRole, '#');
        if(array_key_exists('OutTags', $fields) && is_array($fields['OutTags'])
           && array_key_exists($hashed, $fields['OutTags']) && is_array($fields['OutTags'][$hashed]) )
        {

            $tags = $fields['OutTags'][$hashed];
            //$tags = $fields[$hashed];

            foreach((array)$tags as $k => $tag) {
                $tagsArray[] = new Tag($tag);
            }
        }
        if($increasePartials)
            $node->replaceOutTags($tagRole, $tagsArray);
        else
            $node->replaceOutTagsInternal($tagRole, $tagsArray);


    }

    public function bindInTags(Node &$node, Errors &$errors, array $fields, array $rawFields, $tagRole, $increasePartials = false)
    {
        $tagsArray = array();
        $hashed = '#'.ltrim($tagRole, '#');
        if(array_key_exists('InTags', $fields) && is_array($fields['InTags'])
           && array_key_exists($hashed, $fields['InTags']) && is_array($fields['InTags'][$hashed])
        ) {

            $tags = $fields['InTags'][$hashed];
//            $tags = $fields[$hashed];

            foreach((array)$tags as $k => $tag) {
                $tagsArray[] = new Tag($tag);
            }

        }
        if($increasePartials)
            $node->replaceInTags($tagRole, $tagsArray);
        else
            $node->replaceInTagsInternal($tagRole, $tagsArray);

    }

    public function bindMeta(Node &$node, Errors &$errors, array $fields, array $rawFields, $name)
    {
        $schema = $node->getSchema();
//        if(array_key_exists('Data', $fields) && is_array($fields['Data'])
//           && array_key_exists(NodeMapper::METAS, $fields['Data']) && is_array($fields['Data'][NodeMapper::METAS])
//           && array_key_exists($name, $fields['Data'][NodeMapper::METAS]) && is_array($fields['Data'][NodeMapper::METAS][$name])
//        ) {
        $hashed = '#'.ltrim($name, '#');

        if(array_key_exists($hashed, $fields))
        {

//            $meta = $fields['Data'][NodeMapper::METAS][$name];
//            $rawMeta = $rawFields['Data'][NodeMapper::METAS][$name];
            $meta = $fields[$hashed];
            $rawMeta = $rawFields[$hashed];

            $node->setMeta($name, $this->convertMeta($node, $schema, $errors, $name, $meta, $rawMeta));

        }
    }

    protected function convertMeta(Node $node, NodeSchema &$schema, Errors &$errors, $name, $value, $rawValue)
    {

        $meta_def = $schema->getMetaDef($name);

        $validation = $meta_def->Validation->getValidationArray();
        $key = $meta_def->Id;
        $fieldTitle = $meta_def->Title;

        try {
            $value = $this->TypeConverter->convertFromString($meta_def->Validation, $value, $rawValue);
        }catch(TypeConversionException $tce)
        {
            $errors->addFieldError('invalid', $this->NodeValidator->resolveField($node->getNodeRef(), $key), 'field', $fieldTitle, $value, $tce->getMessage());
        }

        return $value;
    }


}
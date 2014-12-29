<?php
/**
 * NodeSchemaValidator
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
 * @version     $Id: NodeSchemaValidator.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeSchemaValidator
 *
 * @package     CrowdFusion
 */
class NodeSchemaValidator
{
    protected $allRoles;
    protected $treeOrigin;

    public function validate(SchemaXML $schema)
    {

        $this->allRoles = array();
        $this->treeOrigin = false;

        // Validates the xml schema
        if ($schema->getName() != 'schema')
            throw new SchemaException("Required root tag: 'schema' not found.");

        // TAGs
        $this->validateTags($schema->getTags());

        // Metas
        $this->validateMetas($schema->getMetas());

        // Sections
//        $this->validateSections($schema->getSections());

    }

    protected function validateTags(array $tags)
    {
        foreach ( $tags as $tag ) {

            $role = 'unknown role';
            try {
                // Required attributes
    //            foreach ( array('id') as $attr ) {
    //                if ($tag->attribute($attr) == null)
    //                    throw new SchemaException("Required attribute '{$attr}' not found for tag: " . $tag->asPrettyXML());
    //            }

    //            if (!SlugUtils::isSlug($tag->attribute('id')))
    //                throw new SchemaException("Tag ID is in an invalid format. Must be in valid slug format. Tag: ". $tag->attribute('id'));

                // Required children
                foreach ( array('title', 'partial') as $required_child ) {
                    if (!$tag->$required_child)
                        throw new SchemaException("Required child element '{$required_child}' not found for tag: " . $tag->attribute('id'));
                }

                // Validate the values for the attributes
                $attr_vals = $this->tagAttributeValues();
                foreach ( $attr_vals as $name => $regex ) {
                    if ($tag->attribute($name))
                        if (!preg_match("/{$regex}/i", $tag->attribute($name)))
                            throw new SchemaException("Tag attribute {$name} is not in the expected format.".
                                                        " Must Match Regex: {$regex}".
                                                        " Invalid: '" . $tag->attribute($name) . "' in tag: " . $tag->attribute('id'));
                }

                // Validate the partial
                $partial = $tag->partial;
                if ($partial->attribute('element') == null && $partial->attribute('aspect') == null)
                    throw new SchemaException("Tag partials require an 'element' or 'aspect' attribute. Tag: " . $tag->attribute('id'));

                if ($partial->attribute('role') == null)
                    throw new SchemaException("'role' is required for the partial. Tag: " . $tag->attribute('id'));

                $role = ltrim($partial->attribute('role'), '#');
                if(in_array($role, $this->allRoles))
                    throw new SchemaException("Tag role [".$role."] already used in schema");

                $this->allRoles[] = $role;

                // Validate we have some text for the title
                if (strlen((string)$tag->title) == 0)
                    throw new SchemaException("Tag title must have a value. Tag: " . $tag->attribute('id'));


                $treeorigin = $tag->attribute('treeorigin');
                if(!empty($treeorigin))
                {
                    if($this->treeOrigin != false)
                        throw new SchemaException("Cannot have more than 1 tree origin tag definition in schema");

                    $this->treeOrigin = $treeorigin;
                }

                // Validate the <value_options> tag
                if ($partial->attribute('value') != null && $tag->value_options)
                    throw new SchemaException("value_options cannot be specified if <partial> has value. Tag: " . $tag->attribute('id'));

                $value_options = $tag->value_options;
                if ($value_options) {
                    $value_options_attrs = $this->tagValueOptionsAttributesValues();
                    foreach ( $value_options_attrs as $attr => $regex ) {
                        if (!preg_match("/{$regex}/i", $value_options->attribute($attr)))
                            throw new SchemaException("value_options attribute '{$attr}' is invalid. Must Match Regex: {$regex} Tag: " .
                                                        $tag->attribute('id'));
                    }

                    foreach ( $value_options->value as $value ) {
                        if ($value->attribute('value') == null || strlen((string)$value) < 1)
                            throw new SchemaException("value tag is invalid: " . $value->asPrettyXML(). " Tag: " . $tag->attribute('id'));
                    }
                }

                // Check that the <validation> tag is sane, if it exists
                if ($tag->validation)
                    $this->validationCheck($tag->validation);


            } catch(SchemaException $e)
            {
                throw new SchemaException("Unable to parse tag definition [$role]:\n".$e->getMessage());
            }
        }
    }

    protected function validateMetas(array $metas)
    {
        foreach ( $metas as $meta ) {

            $role = 'unknown id';
            try {
                // Required attributes
                foreach ( array('id') as $attr ) {
                    if ($meta->attribute($attr) == null)
                        throw new SchemaException("Required attribute '{$attr}' not found for meta: " . htmlentities($meta->asPrettyXML()));
                }

                $role = ltrim($meta->attribute('id'), '#');
                if (!SlugUtils::isSlug($role))
                    throw new SchemaException("Meta ID is in an invalid format. Must be in valid slug format. Meta: ". $meta->attribute('id'));

                if(in_array($role, $this->allRoles))
                    throw new SchemaException("Meta id [".$role."] already used in schema");

                $this->allRoles[] = $role;

                // Required children
                foreach ( array('title', 'validation') as $required_child ) {
                    if (!$meta->$required_child)
                        throw new SchemaException("Required child element '{$required_child}' not found for meta: " . $meta->attribute('id'));
                }

                // Validate we have some text for the title
                if (strlen((string)$meta->title) == 0)
                    throw new SchemaException("Meta title must have a value. Meta: " . $meta->attribute('id'));

                // Check that the <validation> tag is sane, if it exists
                $this->validationCheck($meta->validation);

                if($meta->validation->attribute('datatype') == 'boolean' && empty($meta->default))
                    throw new SchemaException('Meta boolean datatype must specify a default value: '.$meta->attribute('id'));

            } catch(SchemaException $e)
            {
                throw new SchemaException("Unable to parse meta definition [$role]:\n".$e->getMessage());
            }
        }
    }

//    protected function validateSections(array $sections)
//    {
//        foreach ( $sections as $section ) {
//            $role = 'unknown id';
//            try {
//
//                // Required attributes
//                foreach ( array('id') as $attr ) {
//                    if ($section->attribute($attr) == null)
//                        throw new SchemaException("Required attribute '{$attr}' not found for section: " . $section->asPrettyXML());
//                }
//
//                if (!SlugUtils::isSlug($section->attribute('id')))
//                    throw new SchemaException("Section ID is in an invalid format. Must be in valid slug format. Section: ". $section->attribute('id'));
//
//                $role = $section->attribute('id');
//                if(in_array($role, $this->allRoles))
//                    throw new SchemaException("Section id [".$role."] already used in schema");
//
//                $this->allRoles[] = $role;
//
//                $section_attrs = $this->sectionAttributeValues();
//                foreach ( $section_attrs as $attr => $regex ) {
//                    if (!preg_match("/{$regex}/i", $section->attribute($attr)))
//                        throw new SchemaException("Section attribute '{$attr}' is invalid.".
//                                                  " Must Match Regex: {$regex} Section: " . $section->attribute('id'));
//                }
//
//                // Required children
//                foreach ( array('title') as $required_child ) {
//                    if (!$section->$required_child)
//                        throw new SchemaException("Required child element '{$required_child}' not found for section: " . $section->attribute('id'));
//                }
//
//                $this->validateTags($section->getTags());
//                $this->validateMetas($section->getMetas());
//            } catch(SchemaException $e)
//            {
//                throw new SchemaException("Unable to parse section definition [$role]:\n".$e->getMessage());
//            }
//        }
//    }

    protected function validationCheck(SchemaXML $validation)
    {
        // Required attributes
        foreach ( array('datatype') as $attr ) {
            if ($validation->attribute($attr) == null)
                throw new SchemaException("Required attribute '{$attr}' not found for validation: " . $validation->asPrettyXML());
        }

        $num_attr = 0;
        $validation_options = $this->validationValues();

        foreach ( $validation_options as $attr => $regex ) {
            if ($validation->attribute($attr)) {
                $num_attr++;
                if (!preg_match("/{$regex}/i", $validation->attribute($attr)))
                    throw new SchemaException("validation attribute '{$attr}' is invalid. Must match regex: " . htmlentities($regex));
            }
        }

//        if ($num_attr == 0)
//            throw new SchemaException("At least one validation attribute is required");
    }

//    protected function sectionAttributeValues()
//    {
//        return array('sortable' => '^(true|false)?$',
//                     'min'      => '^(\d+)?$',
//                     'max'      => '^(\d+|\*)?$');
//    }

    protected function tagAttributeValues()
    {
        // Seperated into it's own function in case we want to override
        return array('multiple'   => '^true|false$',
                     'quickadd'   => '^true|false$',
                     'sortable'   => '^true|false$',
                     'treeorigin' => '^true|false$',
                     'fieldlike'  => '^true|false$',
                     'direction'  => '^in|out$');
    }

    protected function tagValueOptionsAttributesValues()
    {
        return array('mode'     => '^none|predefined|typein$',
                     'multiple' => '^true|false$');
    }

    protected function validationValues()
    {
        return array('datatype'  => '^(int|float|string|slug|slugwithslash|date|boolean|url|html|email|flag|binary|json)$',
                     'unix'      => '^(true|false)?$',
                     'dateonly'  => '^(true|false)?$',
                     'nullable'  => '^(true|false)?$',
                     'match'     => '^.*$',
                     'min' => '^(\-)?([\d\.]+)?$',
                     'max' => '^(\-)?([\d\.]+)?(k)?$');
    }
}

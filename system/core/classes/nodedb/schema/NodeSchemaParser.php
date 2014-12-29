<?php
/**
 * NodeSchemaParser
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
 * @version     $Id: NodeSchemaParser.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeSchemaParser
 *
 * @package     CrowdFusion
 */
class NodeSchemaParser
{
    protected $schema;

    protected $SimpleXMLParser;
    protected $validator;
    protected $className = 'SchemaXML';

    public function __construct(SimpleXMLParserInterface $SimpleXMLParser, NodeSchemaValidator $NodeSchemaValidator)
    {
        $this->SimpleXMLParser = $SimpleXMLParser;
        $this->validator = $NodeSchemaValidator;
    }

    public function parse($string)
    {
        $xml = $this->SimpleXMLParser->parseXMLString($string, $this->className);

        $this->validator->validate($xml);

        $schema = new NodeSchema;

        $tags = $this->getTags($xml);
        foreach ( $tags as $tag )
            $schema->addTagDef($tag);

        $metas = $this->getMetas($xml);
        foreach ( $metas as $meta )
            $schema->addMetaDef($meta);

//        $sections = $this->getSections($xml);
//        foreach ( $sections as $section )
//            $schema->addSectionDef($section);

        return $schema;
    }

    protected function getXML($xml)
    {
        if ($xml == null)
            throw new Exception('No schemaXML to analyze.');

        return $xml;
    }

    protected function fillObject($xml, &$object, array $attributes, array $children)
    {
        // Attributes
        foreach ( $attributes as $attr ) {
            if ($xml->attribute($attr)){
                $prop = ucfirst($attr);
                $object->$prop = $xml->attribute($attr);
            }
        }

        // Children
        foreach ( $children as $child ) {
            if ($xml->$child){
                $prop = ucfirst($child);
                $object->$prop = (string)$xml->$child;
            }
        }

        return $object;
    }

    protected function getTags($xml = null)
    {
        $tags = array();
        $xml  = $this->getXML($xml);

        foreach ( $xml->getTags() as $tag_xml ) {
            // Build the TagDef object
            $tag = new TagDef;
            $this->fillObject($tag_xml, $tag, array('sortable', 'quickadd', 'multiple', 'direction', 'filter', 'fieldlike', 'treeorigin'), array('title'));
            $tag->Validation = new ValidationExpression($tag_xml);

            if(empty($tag->Direction))
                $tag->Direction = 'out';

            if(empty($tag->Fieldlike))
                $tag->Fieldlike = true;

            // Attach the partial to the tag
            $partial = array();
            $partial_xml = $tag_xml->partial;

            // Partial attributes
            $partial_attributes = array('TagElement'     => 'element',
                                        'TagAspect'      => 'aspect',
                                        'TagRole'        => 'role',
                                        'TagRoleDisplay' => 'roledisplay',
                                        'TagValue'       => 'value');
            foreach ( $partial_attributes as $key => $attr ) {
                $partial[$key] = ltrim((string)$partial_xml->attribute($attr), '#@');
            }

            // $tag->partial = $partial;
            $tag->Partial = new TagPartial($partial);

            // tag id = tag role
            $tag->Id = $tag->Partial->getTagRole();

            // Add the value options defs
            if ($tag_xml->value_options) {
                $vo = new ValueOptionsDef;

                foreach ( array('mode', 'multiple') as $attr ){
                    $prop = ucfirst($attr);
                    $vo->$prop = $tag_xml->value_options->attribute($attr);
                }

                foreach ( $tag_xml->value_options->value as $value ) {
                    $vo->addValue($value->attribute('value'), (string)$value);
                }

                $tag->ValueOptions = $vo;
            }

            $tags[] = $tag;
        }

        // Return all tags
        return $tags;
    }

    protected function getMetas($xml = null)
    {
        $metas = array();
        $xml = $this->getXML($xml);

        foreach ( $xml->getMetas() as $meta_xml ) {
            // Build the MetaDef object
            $meta = new MetaDef;
            $this->fillObject($meta_xml, $meta, array('id'/*, 'fieldlike'*/), array('title', 'default'));

            $meta->Validation = new ValidationExpression($meta_xml, $defaultToNullable = true);

            $meta->Datatype = $this->deriveMetaDatatype($meta->Validation);

            if(!empty($meta->Id))
                $meta->Id = ltrim($meta->Id, '#');

            if(empty($meta->Datatype))
                throw new Exception("Invalid meta definition for id [{$meta->Id}]: missing datatype");

//            if(empty($meta->Fieldlike))
//                $meta->Fieldlike = true;

            $metas[] = $meta;
        }

        // Return all metas
        return $metas;
    }

    protected function deriveMetaDatatype(ValidationExpression $ve)
    {
        $v = $ve->getValidationArray();

        $datatype = $v['datatype'];

        switch($datatype)
        {
            case 'flag':
                return 'flag';

            case 'boolean':
                return 'tiny';

            case 'date':
                if (isset($v['unix']) && !StringUtils::strToBool($v['unix'])) {
                    return 'datetime';
                }
                // If unix attribute is not specified, assume they want date (timestamp) for backward-compatibility
                return 'date';

            case 'int':
                $min = isset($v['min'])?(int)$v['min']:0;
                $max = isset($v['max'])?(int)$v['max']:PHP_INT_MAX;

                if($min < 0)
                {
                    if($min >= -128 && $max <= 127)
                        return 'tiny-signed';
                    else if($min >= -2147483648 && $max <= 2147483647)
                        return 'int-signed';

                    return 'long-signed';
                }

                if($max < 255)
                    return 'tiny';
                else if($max <= 4294967295)
                    return 'int';

                return 'long';

            case 'float':
                return 'float';

            case 'slug':
            case 'slugwithslash':
            case 'email':
                $ve->setMax(255); // set max
                return 'varchar';

            case 'json':
                $ve->setMax(262144); //set max to 256K
                return 'mediumtext';

            case 'string':
            case 'html':
            case 'url':

                // NOTE: MySQL columns are bytes, string lengths are characters
                $max = 255; // default is 255
                if(isset($v['max']))
                  $max = (int)FileSystemUtils::iniValueInBytes($v['max']);

                if($max == 65536)
                    $max = 65535;

                if($max > 262144) // max is 256K
                    $max = 262144;

                $ve->setMax($max); // set max

                if($max <= 255) // 255 or less is VARCHAR
                    return 'varchar';

                if($max <= 65535) // 64K or less is TEXT
                    return 'text';

                if($max <= 262144) // 256K or less is MEDIUMTEXT
                    return 'mediumtext';

            case 'binary':
                $max = 262144; // default is 256K
                if(isset($v['max']))
                  $max = (int)FileSystemUtils::iniValueInBytes($v['max']);

                if($max == 65536)
                    $max = 65535;

                if($max > 262144) // max is 256K
                    $max = 262144;

                $ve->setMax($max); // set max

                if($max <= 65535) // 64K or less, store as BLOB
                    return 'blob';

                if($max <= 262144) // 256K or less, store as MEDIUMBLOB
                    return 'mediumblob';
        }

    }

//    public function getSections($xml = null)
//    {
//        $sections = array();
//        $xml      = $this->getXML($xml);
//
//        foreach ( $xml->getSections() as $section_xml ) {
//            // Build the SectionDef object
//            $section = new SectionDef;
//            $this->fillObject($section_xml, $section, array('id', 'sortable', 'min', 'max'), array('title'));
//
//            $tags = $this->getTags($section_xml);
//            foreach ( $tags as $tag )
//            {
//                if(!$tag->isFieldlike())
//                    throw new SchemaException('All tags in sections must be fieldlike, see section ['.$section->Id.']');
//
//                $section->addTagDef($tag);
//            }
//
//
//            $metas = $this->getMetas($section_xml);
//            foreach ( $metas as $meta )
//            {
//                //if(!$meta->isFieldlike())
//                    //throw new SchemaException('All meta in sections must be fieldlike, see section ['.$section->Id.']');
//
//                $section->addMetaDef($meta);
//            }
//
//            // Append it to our sections list
//            $sections[] = $section;
//        }
//
//        // Return all sections
//        return $sections;
//    }

} // END class NodeSchemaParser
?>

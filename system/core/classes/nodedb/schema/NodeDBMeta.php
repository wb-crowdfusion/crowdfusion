<?php
/**
 * NodeDBMeta
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
 * @version     $Id: NodeDBMeta.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeDBMeta
 *
 * @package     CrowdFusion
 */
class NodeDBMeta
{
    protected $selectFields;

    public function getModelSchema()
    {
        return array(
            'Title'          => array('validation' => array('datatype'=>'string', 'min' => 1, 'max' => 255)),
            'Slug'           => array('validation' => array('datatype'=>'slug', 'max' => 255)),
            'ElementID'      => array('validation' => array('datatype'=>'int')),
            'Status'         => array(
                'default'    => 'draft',
                'validation' => array('datatype'=>'string', 'match' => '^(published|draft|deleted)$')),
            'ActiveDate'     => array(
                'title'      => 'Active Date',
                'default'    => 'now',
                'validation' => array('datatype' => 'date')),
            'CreationDate'   => array(
                'title'      => 'Creation Date',
                'default'    => 'now',
                'validation' => array('datatype' => 'date')),
            'ModifiedDate'   => array(
                'title'      => 'Modified Date',
                'default'    => 'now',
                'validation' => array('datatype' => 'date')),

            'SortOrder'      => array('validation' => array('datatype'=> 'string', 'nullable'=> true, 'max'=>255)),
            'TreeID'         => array('validation' => array('datatype'=> 'string', 'nullable'=> true, 'max'=>255)),
        );

    }

    public static function getMetaStorageDatatypes()
    {
        return array('flag', 'tiny', 'int', 'long', 'tiny-signed', 'int-signed', 'long-signed', 'float', 'date', 'datetime', 'varchar', 'text', 'mediumtext', 'blob', 'mediumblob');
    }

    public function getDatatype($key)
    {

        $validation = $this->getValidation($key);

        if(empty($validation['datatype']))
            throw new Exception('No datatype defined for key: '.$key);

        return $validation['datatype'];
    }

    public function getDefault($key)
    {
        $schema = $this->getModelSchema();

        if(array_key_exists('default',$schema[$key]))
            return $schema[$key]['default'];

        return null;
    }

    public function getFieldTitle($key)
    {
        $schema = $this->getModelSchema();

        if(array_key_exists('title',$schema[$key]))
            return $schema[$key]['title'];

        return $key;
    }


    /**
     * Returns a ValidationExpression object for the request field
     *
     * @param string $field The field name
     *
     * @return ValidationExpression
     */
    public function getValidation($field = null)
    {

        $validation = array();
        $schema = $this->getModelSchema();

        if($field != null)
            $schema = array($field => $schema[$field]);

        foreach ( $schema as $key => $value ) {
            if(!isset($value['validation']))
                throw new Exception('Validation missing for key: '.$field);
            $validation[$key] = $value['validation'];
        }

        if($field != null)
            return $validation[$field];

        return $validation;
    }

    public function getValidationExpression($field)
    {
        return new ValidationExpression($this->getValidation($field));
    }

    public function getPersistentFields()
    {
        return array_keys($this->getModelSchema());
    }

    public function getSelectFields()
    {
        if(!$this->selectFields)
        {
            $fields = $this->getPersistentFields();
            unset($fields[array_search('ElementID', $fields)]);
            $this->selectFields = $fields;
        }
        return $this->selectFields;
    }

    public function getTableName(NodeRef $nodeRef)
    {
        return str_replace('-', '_', 'n-'.$nodeRef->getElement()->Slug);
    }

    public function getPrimaryKey(NodeRef $nodeRef)
    {
        return 'Table'.StringUtils::camelize($nodeRef->getElement()->Slug.'ID');
    }

    public function getOutTagsTable(NodeRef $nodeRef)
    {
        return $this->getTableName($nodeRef).'_outtags';
    }

    public function getInTagsTable(NodeRef $nodeRef)
    {
        return $this->getTableName($nodeRef).'_intags';
    }

    public function getMetaTable(NodeRef $nodeRef, $datatype)
    {
        if(empty($datatype) || !in_array($datatype, $this->getMetaStorageDatatypes()))
            throw new NodeException('Cannot retrieve meta table without valid datatype');


        return $this->getTableName($nodeRef).'_meta_'.str_replace('-', '_', $datatype);
    }

    public function getMetaDatatypeColumn($datatype)
    {
        if($datatype != 'flag'){

            $datatypeCol = $datatype;
            if(($dashPos = strpos($datatypeCol, '-')) !== FALSE)
                $datatypeCol = substr($datatypeCol, 0, $dashPos);

            return $datatypeCol;
        }

        return null;
    }

    public function getSectionsTable(NodeRef $nodeRef)
    {
        return $this->getTableName($nodeRef).'_sections';
    }

}

<?php
/**
 * NodeMapper
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
 * @version     $Id: NodeMapper.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeMapper
 *
 * @package     CrowdFusion
 */
class NodeMapper
{

//    const IN_TAGS = 'InTags';
//    const OUT_TAGS = 'OutTags';
//    const METAS = 'Metas';

    protected $Events;
    protected $NodeDBMeta;
    protected $DateFactory;
    protected $TypeConverter;

    protected $now;

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    public function setNodeDBMeta(NodeDBMeta $NodeDBMeta)
    {
        $this->NodeDBMeta = $NodeDBMeta;
    }

    public function setTypeConverter(TypeConverterInterface $TypeConverter)
    {
        $this->TypeConverter = $TypeConverter;
    }

    public function setDateFactory($DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    public function __construct()
    {

    }

    protected function getNow()
    {
        if(is_null($this->now))
            $this->now = $this->DateFactory->newLocalDate();
        return $this->now;
    }


    public function persistentArrayToNode($fields)
    {
        if(empty($fields['NodeRef']))
            throw new Exception('Cannot create Node from array without NodeRef');

        $node = $fields['NodeRef']->generateNode();

        $cFields = array_intersect_key($fields,array_flip($this->NodeDBMeta->getPersistentFields()));

        foreach($cFields as $key => $value) {
            $validation = $this->NodeDBMeta->getValidationExpression($key);
            $fields[$key] = $this->TypeConverter->convertFromString($validation, $value, null, true);
            if($validation->getDatatype() == 'date')
                    $fields[$key.'Unix'] = $fields[$key]->toUnix();
            unset($key);
            unset($value);
        }

        if($fields['Status'] == 'published' && $fields['ActiveDate']->toUnix() < $this->getNow()->toUnix())
            $fields['IsActive'] = true;

        if(!empty($fields['TreeID']))
            $fields['Depth'] = strlen($fields['TreeID']) / 4;

        $node->setFromArray($fields);
        unset($fields);

        return $node;
    }

    public function nodeToPersistentArray(Node $obj)
    {

        $persistent = array_intersect_key($obj->toArray(), array_flip($this->NodeDBMeta->getPersistentFields()));
        $persistent['ElementID'] = $obj->getNodeRef()->getElement()->getElementID();

        return $persistent;

    }



    public function defaultsOnNode(Node &$node)
    {
        foreach($this->NodeDBMeta->getPersistentFields() as $key) {
            $default = $this->NodeDBMeta->getDefault($key);

            if($default != null && !isset($node->$key))
                $node->$key = $this->TypeConverter->convertFromString($this->NodeDBMeta->getValidationExpression($key), $default);
        }

        $metaDefs = $node->getNodeRef()->getElement()->getSchema()->getMetaDefs();

        foreach($metaDefs as $metaDef)
        {
            $default = $metaDef->Default;

            if($default != null && !$node->hasMeta($metaDef->Id))
                $node->setMeta($metaDef->Id, $this->TypeConverter->convertFromString($metaDef->Validation, $default));
        }
    }

    public function populateNodeCheaters(&$node)
    {
        if($node->hasCheaters())
            return $node;

        $nodeRef = $node->getNodeRef();
        $node['RecordLink'] = $nodeRef->getRecordLink();
        $node['RecordLinkURI'] = $nodeRef->getRecordLinkURI();
        $node['RefURL'] = $nodeRef->getRefURL();

        if(!empty($node['ActiveDate']) && is_object($node['ActiveDate']) && $node['Status'] == 'published' && $node['ActiveDate']->toUnix() < $this->getNow()->toUnix())
            $node['IsActive'] = true;

        if(!empty($node['TreeID']))
            $node['Depth'] = strlen($node['TreeID']) / 4;

        $node['Element'] = $nodeRef->getElement();
        $node['Site'] = $nodeRef->getSite();

        $this->cheaters = array();

        $this->recurseTags($node);

//        $node->setFromArray($this->cheaters);

        $node['Cheaters'] = $this->cheaters;

        return $node;
    }

    public function nodeToInputArray(Node $node)
    {
        $row = array();

        foreach($this->NodeDBMeta->getPersistentFields() as $name)
            $row[$name] = (string)$node[$name];

        $row['ElementSlug'] = $node['NodeRef']->getElement()->getSlug();
        $row['SiteSlug'] = $node['NodeRef']->getSite()->getSlug();
        unset($row['ElementID']);

        //unset($row[self::METAS]);

        $meta = $node->getMetas();
        foreach($meta as $name => $value)
            $row['#'.$name] = (string)$value['MetaValue'];

        $tags = $node->getOutTags();
        if(!empty($tags)) {
            $row['OutTags'] = array();

            foreach($tags as $tag)
                $row['OutTags']['#'.$tag->getTagRole()][] = $tag->toPersistentArray();

        }

        $tags = $node->getInTags();
        if(!empty($tags)) {
            $row['InTags'] = array();

            foreach($tags as $tag)
                $row['InTags']['#'.$tag->getTagRole()][] = $tag->toPersistentArray();

        }

        return $row;
    }

    protected $cheaters = array();

    protected function recurseTags(&$row, $base = '')
    {

        $metatagsForCheaters = $row->getMetas();

        foreach($metatagsForCheaters as $tag) {
            // When: contains meta tag 'meta:name=value"Display"'
            // Then: row should contain { 'meta:name': 'Display' }
//            $val = isset($tag['MetaValue'])?$tag['MetaValue']:1;
            foreach((array)$base as $b) {
                if($b != '')
                    $b = $b.'.';


//                ArrayUtils::append($row[$b.'meta#' . $tag['MetaName']], $val);

                //ArrayUtils::append($row[$b.'#'.$tag['MetaName']], $val);
                $this->cheaters[$b.'#'.$tag['MetaName']] = $tag;
            }
        }


//        $row = array_merge($row, $metatagsForCheaters);

        $outtagsForCheaters = $row->getOutTags();
        $intagsForCheaters  = $row->getInTags();

        //error_log(print_r($outtagsForCheaters, true));

        $linktagsForCheaters = array_merge($outtagsForCheaters, $intagsForCheaters);
        if($base == '')
            $row['LinkTags']     = $linktagsForCheaters;

        if (!empty($linktagsForCheaters)) {
            foreach ($linktagsForCheaters as $tag) {
                if ($tag->hasTagLinkNode()) {
                    $newbases = array();
                    foreach((array)$base as $b) {
                        if($b != '')
                            $b = $b.'.';

                        $k = $b.'#' .$tag['TagRole'];
                        if(array_key_exists($k, $this->cheaters))
                        {
                            $val = $this->cheaters[$k];
                            if(!is_array($val))
                                $this->cheaters[$k] = array($val);

                            $this->cheaters[$k][] = $tag;
                        } else {
                            $this->cheaters[$k] = $tag;
                        }

                        $newbases[] = $k;

                        if(!empty($tag['TagValue'])) {
                            $k2 = $k.'='.$tag['TagValue'];
                            $newbases[] = $k2;

                            if(array_key_exists($k2, $this->cheaters))
                            {
                                $val = $this->cheaters[$k2];
                                if(!is_array($val))
                                    $this->cheaters[$k2] = array($val);

                                $this->cheaters[$k2][] = $tag;
                            } else {
                                $this->cheaters[$k2] = $tag;
                            }
                        }
                    }

                    $tRow = $tag['TagLinkNode'];
                    $this->recurseTags($tRow, $newbases);
                    $tag['TagLinkNode'] = $tRow;
                }
            }
        }

    }


}

<?php
/**
 * PartialUtils
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
 * @version     $Id: PartialUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PartialUtils
 *
 * @package     CrowdFusion
 */
class PartialUtils
{

    public static function unserializeMetaPartials($tagstring)
    {
        return self::unserializePartials('meta', $tagstring);
    }

    public static function unserializeInPartials($tagstring)
    {
        return self::unserializePartials('tags', $tagstring);
    }

    public static function unserializeOutPartials($tagstring)
    {
        return self::unserializePartials('tags', $tagstring);
    }

    protected static function unserializePartials($type = 'tags', $tagstring)
    {
        if(empty($tagstring))
            return array();

        if(!is_array($tagstring))
            $tagstring = array($tagstring);

        $tags = array();

        foreach($tagstring as $tstring) {

            if(is_array($tstring))
                $tagstring = array_merge($tagstring, $tstring);

            if($tstring instanceof MetaPartial || $tstring instanceof TagPartial) {
                $tags[] = $tstring;
                continue;
            }

            if(strtolower($tstring) == 'all' || (strtolower($type) == 'meta' && strtolower($tstring) == 'fields')) {
                return 'all';
            }

            $raw_tags = StringUtils::smartSplit($tstring, ",", '"', '\\"');

            foreach ($raw_tags as $tag) {
                if(strtolower($tag) == 'all' || (strtolower($type) == 'meta' && strtolower($tag) == 'fields'))
                    return 'all';
                else if(strtolower($type) != 'meta' && strtolower($tag) == 'fields')
                    $tags[] = 'fields';
                else {

                    try {
                        if(strtolower($type) == 'meta') {
                            $tags[] = new MetaPartial($tag);
                        } else {
                            $tags[] = new TagPartial(strtolower($tag));
                        }
                    }catch(Exception $e) {
                        throw new TagException('Cannot unserialize tag string, corrupted tag: '.$tag .' because: '.$e->getMessage());
                    }

                }

            }
        }

        return $tags;
    }

    public static function serializeMetaPartials($tags)
    {
        return self::serializePartials($tags, 'meta');
    }

    public static function serializeInPartials($tags)
    {
        return self::serializePartials($tags, 'tags');
    }

    public static function serializeOutPartials($tags)
    {
        return self::serializePartials($tags, 'tags');
    }

    protected static function serializePartials($tags, $type = 'tags') {
        if(empty($tags)) return '';

        if(is_string($tags))
            return $tags;

        if(!is_array($tags))
            throw new Exception('Cannot serialize Partials: '.print_r($tags, true));


        $newarray = array();
        foreach($tags as $key => $tag) {
            if(strtolower($type) == 'meta') {
                if(!($tag instanceof MetaPartial)) {
                    $newarray[$key] = new MetaPartial($tag);
                    continue;
                }
            } else {
                if(!($tag instanceof TagPartial)) {
                    $newarray[$key] = new Partial($tag);
                    continue;
                }
            }

            $newarray[$key] = $tag;
        }

        $tagstrs = array();
        foreach($newarray as $tag)
            $tagstrs[] = $tag->toString();

        return implode(',', $tagstrs);
    }


    public static function increasePartials($partials, $increase) {
        if(empty($partials))
            return $increase;

        if(in_array($increase, StringUtils::smartSplit($partials, ',', '"', '\\"')) === FALSE)
            return $partials.','.$increase;

        return $partials;
    }

    public static function decreasePartials($partials, $decrease) {
        if(empty($partials))
            return $partials;

        if(($k = array_search($decrease, $explodedPartials = StringUtils::smartSplit($partials, ',', '"', '\\"'))) !== FALSE){
            unset($explodedPartials[$k]);
            return implode(',', $explodedPartials);
        }

        return $partials;
    }


    public static function isMetaInScope(NodeSchema $schema, NodePartials $nodePartials, $id)
    {
        $partials = self::unserializeMetaPartials($nodePartials->getMetaPartials());
        $re_partials = self::unserializeMetaPartials($nodePartials->getRestrictedMetaPartials());

        $id = strtolower(ltrim($id, '#'));

        foreach( (array)$re_partials as $partial ) {
            if($partial->getMetaName() == $id){
                return false;
            }
        }

        if($partials == 'all')
            return true;

        // will throw Exception is schema def doesn't exist
        try {
            $schema->getMetaDef($id);
        } catch(SchemaException $se)
        {
            return false;
        }


        $inScope = false;
        foreach( $partials as $partial ) {
            if($partial->getMetaName() == $id){
                $inScope = true;
                break;
            }
        }
        if(!$inScope)
            return false;

        return true;
    }


    public static function isTagRoleInOutTagsScope(NodeSchema $schema, NodePartials $nodePartials, $role)
    {

        $partials_string = $nodePartials->getOutPartials();

        return self::isTagRoleInScope($schema, $partials_string, $role);
    }


    public static function isTagRoleInInTagsScope(NodeSchema $schema, NodePartials $nodePartials, $role)
    {

        $partials_string = $nodePartials->getInPartials();

        return self::isTagRoleInScope($schema, $partials_string, $role);
    }

    private static function isTagRoleInScope(NodeSchema $schema, $partials_string, $role)
    {
        $role = strtolower(ltrim($role, '#'));

        if (empty($partials_string)) {
            return false;
        } else {

            $partials = self::unserializePartials('tags', $partials_string);

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

            try {
                $tagDef = $schema->getTagDef($role);
            } catch(SchemaException $se)
            {
                return false;
            }

            if($all)
                return true;

            if($allfieldlike && $tagDef->isFieldlike())
                return true;

            $inScope = false;
            foreach( $partials as $partial ) {
                if($partial->getTagRole() == strtolower($role)){
                    return true;
                }
            }

        }

        return false;

    }
}
<?php
/**
 * MetaUtils
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
 * @version     $Id: MetaUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * MetaUtilities provides static methods for dealing with meta tags.
 *
 * @package     CrowdFusion
 */
class MetaUtils
{

    /**
     * Validates that the given array contains all valid Meta entries
     *
     * @param array &$array An array of strings or Meta objects
     *
     * @return void
     * @throws Exception if any items are invalid.
     */
    public static function validateMeta(array &$array)
    {

        foreach ($array as $key => $tag) {
            if (!($tag instanceof Meta)) {
                $newtag = new Meta($tag);
                $array[$key] = $newtag;
            }
        }
    }

    /**
     * Takes an array of Meta tags and returns only those that match the {@link $partial}
     *
     * @param array       $tags       an array of meta tags
     * @param MetaPartial $partial    The partial used to select the tags
     * @param boolean     $matchExact If set to true, we require an exact match for the tag
     *
     * @return array an array of Meta tags that match the partial
     */
    public static function filterMeta(array $tags, $nameOrMeta)
    {

        if(empty($tags))
            return array();

        $newtags = array();
        foreach ($tags as $key => $tag) {
            if (!($tag instanceof Meta))
                $tag = new Meta($tag);

            if (
                (($nameOrMeta instanceof $nameOrMeta) && ($roleOrTag->matchExact($tag)))
                    || (strcmp($nameOrMeta, $tag->getMetaName()) === 0)
            )
                $newtags[$key] = $tag;
        }

        return $newtags;
    }

    /**
     * Takes an array of Meta tags and removes the tags that match the {@link $partial}
     *
     * @param array       $tags       An array of Meta tags to filter
     * @param MetaPartial $partial    The partial that matches tags we want to remove from the array
     * @param boolean     $matchExact If set to true, then we require an exact match for the partial
     *
     * @return array an array of Meta tags with all tags matching the {@link $partial} removed.
     */
    public static function deleteMeta(array $tags, $nameOrMeta)
    {
        if(empty($tags))
            return $tags;

        foreach ($tags as $key => $tag) {
            if (!($tag instanceof Meta))
                $tags[$key] = new Meta($tag);

            if (
                (($nameOrMeta instanceof $nameOrMeta) && ($roleOrTag->matchExact($tag)))
                    || (strcmp($nameOrMeta, $tag->getMetaName()) === 0)
            )
                unset($tags[$key]);
        }

        return $tags;
    }


    /**
     * Return all tags from {@link $originalTags} that are not in {@link $tagsToDiff}
     *
     * @param array $originalTags An array of Meta tags to filter
     * @param array $tagsToDiff   An array of Meta tags to remove from the {@link $originalTags}
     *
     * @return array all tags from {@link $originalTags} that are not in {@link $tagsToDiff}
     */
    public static function diffMeta(array $originalTags, array $tagsToDiff)
    {
        $tagsToReturn = $originalTags;

        foreach ($originalTags as $k => $tag) {
            foreach ($tagsToDiff as $dtag) {
                if ($tag->matchExact($dtag)) {
                    unset($tagsToReturn[$k]);
                    break;
                }
            }
        }

        return $tagsToReturn;

    }



    /**
     * Returns a string containing all tags passed, for text index
     *
     * @param array $tags, array $tags, ... (as many as you want!)
     * @return string $tagstring
     */
    public static function indexableString(array $metas) {
        $tagstr = '';

        for ($i = 0; $i < func_num_args(); $i++) {
            $metas = func_get_arg($i);

            if (is_array($metas))
                foreach($metas as $meta)
                    if($meta->getMetaStorageDatatype() != 'blob')
                        $tagstr .= $meta->getMetaValue().' ';
        }

        return trim($tagstr);
    }

    /**
     * Returns a string truncated to fit inside a meta.
     *
     * @param mixed $obj - object that has the schema
     *                     (Node | NodeRef | Element | Aspect)
     * @param string $metaName
     * @param string $string - string to be truncated
     * @param bool $ellipsis - if set, output is appended with three dots
     * @return string
     */
    public static function truncateToMax($obj, $metaName, $string, $ellipsis = false)
    {
        switch (true) {
            case $obj instanceof Element:
            case $obj instanceof Aspect:
                break;
            case $obj instanceof NodeRef:
            case $obj instanceof Node:
                $obj = $obj->Element;
                break;
            default:
                throw new Exception(__CLASS__ . '::' . __FUNCTION__ . ": Expected first argument to be instance of Element, Aspect, Node or NodeRef");
        }

        $metaValidationArray = $obj->Schema->getMetaDef($metaName)->Validation->toArray();
        $max = $metaValidationArray['max'];

        $maxBytes = max($max, 255);
        $truncated = false;

        if (strlen($string) > $maxBytes) {
            $truncated = true;
            $string = StringUtils::utf8SafeTruncate($string, $maxBytes - ($ellipsis ? 3 : 0));
        }

        if ($max < 255 && StringUtils::charCount($string) > $max) {
            $truncated = true;
            $string = StringUtils::utf8Substr($string, 0, $max - ($ellipsis ? 1 : 0));
        }

        return $string . (($truncated && $ellipsis) ? "\xe2\x80\xa6" : '');
    }
}

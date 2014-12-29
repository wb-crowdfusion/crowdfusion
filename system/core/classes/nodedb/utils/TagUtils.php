<?php
/**
 * TagUtils
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
 * @version     $Id: TagUtils.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * TagUtils provides static methods for dealing with in and out tags.
 *
 * @package     CrowdFusion
 */
class TagUtils
{

    public static function validateTag(&$tag, $direction = null)
    {
        if (!($tag instanceof Tag))
            $tag = new Tag($tag);

        if (!is_null($direction))
            $tag->setTagDirection($direction);

        return $tag;
    }


    /**
     * Validates that the given array contains all valid Tag entries
     *
     * @param array  &$array    An array of strings or Tag objects
     * @param string $direction Either 'in' or 'out'. Used to specify the direction for all tags in the array
     *
     * @return void
     * @throws Exception if any items are invalid.
     */
    public static function validateTags(array &$array, $direction = null)
    {
        foreach ($array as $key => $tag) {
            $array[$key] = self::validateTag($tag, $direction);
        }

        return $array;
    }

    /**
     * Determines if any tags in the array {@link $tags} match the {@link $partial}
     *
     * @param array      $tags    An array of Tags
     * @param TagPartial $partial A partial to match
     *
     * @return boolean
     */
    public static function containsTags(array $tags, $role)
    {
        if (empty($tags))
            return false;

        foreach ($tags as $tag) {
            if (!($tag instanceof Tag))
                $tag = new Tag($tag);

            if (strcmp(ltrim($role, '#'), $tag->getTagRole()) === 0)
                return true;
        }
        return false;
    }

    /**
     * Returns a count of the number of tags in {@link $tags} that match the {@link $partial}
     *
     * @param array      $tags    An array of Tag objects or tag strings
     * @param TagPartial $partial The partial to match against
     *
     * @return integer
     */
    public static function countTags(array $tags, $role)
    {
        if (empty($tags))
            return 0;

        $count = 0;
        foreach ($tags as $tag) {
            if (!($tag instanceof Tag))
                $tag = new Tag($tag);

            if (strcmp(ltrim($role, '#'), $tag->getTagRole()) === 0)
                $count++;
        }

        return $count;

    }

    /**
     * Filters the array {@link $tags} to remove any tags that match the {@link $partial}
     *
     * @param array      $tags       An array of Tags and/or tag strings
     * @param TagPartial $partial    The partial that will match tags we want to remove
     * @param boolean    $matchExact If set to true, we will only filter tags that exactly match the partial
     *
     * @return array An array of tags without any matching tags
     */
    public static function deleteTags(array $tags, $roleOrTag)
    {
        if (empty($tags))
            return $tags;

        foreach ($tags as $key => $tag) {
            if (!($tag instanceof Tag))
                $tags[$key] = new Tag($tag);

            if (
                (($roleOrTag instanceof Tag) && ($roleOrTag->matchExact($tag)))
                    || (strcmp(ltrim($roleOrTag, '#'), $tag->getTagRole()) === 0)
            )
                unset($tags[$key]);
        }

        if(empty($tags))
            return array();

        return array_values($tags);
    }

    /**
     * Returns an array built with entries from {@link $tags} that match the {@link $partial}
     *
     * @param array      $tags       An array of Tag objects and/or tag strings
     * @param TagPartial $partial    The partial that will match tags we want to return
     * @param string     $matchExact If set to true, we will only return tags that exactly match the partial
     *
     * @return array An array of tags that match the {@link $partial}
     */
    public static function filterTags(array $tags, $roleOrTag)
    {
        if (empty($tags))
            return array();

        $newtags = array();
        foreach ($tags as $key => $tag) {
            if(!($tag instanceof Tag))
                $tag = new Tag($tag);

            if (
                (($roleOrTag instanceof Tag) && ($roleOrTag->matchExact($tag)))
                    || (strcmp(ltrim($roleOrTag, '#'), $tag->getTagRole()) === 0)
            )
                $newtags[$key] = $tag;
        }

        return array_values($newtags);
    }


    /**
     * Return all tags from {@link $originalTags} that are not in {@link $tagsToDiff}
     *
     * @param array   $originalTags   An array of Tag objects to filter
     * @param array   $tagsToDiff     An array of Tag objects to remove from the {@link $originalTags}
     * @param boolean $matchSortOrder If set to true, then sort order must match
     *
     * @return array all tags from {@link $originalTags} that are not in {@link $tagsToDiff}
     */
    public static function diffTags(array $originalTags, array $tagsToDiff, $matchSortOrder = false)
    {
        $tagsToReturn = $originalTags;

        foreach ($originalTags as $k => $tag) {
            foreach ($tagsToDiff as $dtag) {
                if ($tag->matchExact($dtag, $matchSortOrder)) {
                    unset($tagsToReturn[$k]);
                    break;
                }
            }
        }

        return array_values($tagsToReturn);

    }

    /**
     * Determines if the tags in both arrays are a match (including sort order)
     *
     * @param array $tags1 An array of Tag objects
     * @param array $tags2 An Array of Tag objects
     *
     * @return boolean true if both array contain the same tags in the same order
     */
    public static function matchTagsWithSortOrder(array $tags1, array $tags2)
    {
        if (count(self::diffTags($tags1, $tags2, true)) == 0 &&
            count(self::diffTags($tags2, $tags1, true)) == 0)
            return true;

        return false;

    }

    /**
     * Determines if the tags in both arrays are a match
     *
     * @param array $tags1 An array of Tag objects
     * @param array $tags2 An Array of Tag objects
     *
     * @return boolean true if both array contain the same tags
     */
    public static function matchTags(array $tags1, array $tags2)
    {
        if (count(self::diffTags($tags1, $tags2)) == 0 &&
            count(self::diffTags($tags2, $tags1)) == 0)
            return true;

        return false;

    }

    /**
     * Converts all the tags in the array {@link $tags} into a tagstring
     *
     * @param array $tags An array of Tag objects
     *
     * @return string
     */
    public static function serializeTags(array $tags)
    {
        if (empty($tags))
            return '';

        self::validateTags($tags);
        $tagstrs = array();
        foreach ($tags as $tag)
            $tagstrs[] = $tag->toString(true);

        return implode(';', $tagstrs);
    }

    /**
     * Returns a string containing all tags passed, for text index
     *
     * @param array $tags, array $tags, ... (as many as you want!)
     * @return string $tagstring
     */
    public static function indexableString(array $tags) {
        $tagstr = '';

        for ($i = 0; $i < func_num_args(); $i++) {
            $tags = func_get_arg($i);

            if (is_array($tags))
                foreach($tags as $tag)
                    $tagstr .= $tag->getTagSlug().' '.$tag->getTagRoleDisplay().' '.$tag->getTagValueDisplay().' '.$tag->getTagLinkTitle().' ';
        }

        return trim($tagstr);
    }


    public static function filterTagAgainstDef(Tag $tag, TagDef $tagDef)
    {
        $matchPartial = clone $tagDef->getPartial();

		$value = $tag->getTagValue();

        $value_mode     = "none";
        $value_options  = $tagDef->ValueOptions;

        if ($value_options) {
            $value_multiple = $value_options->isMultiple();
            $value_mode     = strtolower((string)$value_options->Mode);

            if ($value_mode == 'predefined') {
                // Extract values from the value tags
                foreach( $value_options->getValues() as $valueOpt => $title ) {
                    $allowed_values[(string)$valueOpt] = $title;
                }
            }

			if(!$value_multiple)
				$matchPartial->TagValue = '';

        } else {
            if($matchPartial->getTagValue() != '')
                $allowed_values[$matchPartial->getTagValue()] = $matchPartial->getTagValueDisplay();
        }

        if($value_mode != 'typein' && !empty($allowed_values) && !array_key_exists($value, $allowed_values))
            throw new Exception('Value not allowed for role ['.$tagDef->Id.']: '.$value);

		$roledisplay = $matchPartial->getTagRoleDisplay();

		if(!$tagDef->isMultiple())
		    $matchPartial->TagSlug = '';
        else
            $matchPartial->TagSlug = $tag->getTagSlug();

		$tag->setTagRoleDisplay($roledisplay);

		$tag->setMatchPartial($matchPartial);

        return $tag;
    }

    public static function determineMatchPartial(TagDef $tagDef, $slug)
    {
        $matchPartial = clone $tagDef->getPartial();

        $value_options  = $tagDef->ValueOptions;

        if ($value_options) {
            $value_multiple = $value_options->isMultiple();

            if(!$value_multiple)
                $matchPartial->TagValue = '';
        }

        if(!$tagDef->isMultiple())
            $matchPartial->TagSlug = '';
        else
            $matchPartial->TagSlug = $slug;

        return $matchPartial;
    }

}
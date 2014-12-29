<?php
/**
 * TagsFilterer
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
 * @version     $Id: TagsFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * TagsFilterer
 *
 * @package     CrowdFusion
 */
class TagsFilterer extends AbstractFilterer
{
    protected $TagsHelper;

    public function setTagsHelper($TagsHelper)
    {
        $this->TagsHelper = $TagsHelper;
    }

    public function hasOutTag()
    {
        $tags = $this->getLocal('OutTags');
        $partial = new TagPartial($this->getParameter('Partial'));

        foreach((array)$tags as $tag) {
            if (($this->getParameter('Status.isActive') != null) && empty($tag['TagLinkURL']))
                continue;
            if($this->TagsHelper->matchPartial($partial, $tag))
                return true;
        }

        return false;
    }

    public function hasInTag()
    {
        $tags = $this->getLocal('InTags');
        $partial = new TagPartial($this->getParameter('Partial'));

        foreach((array)$tags as $tag) {
            if (($this->getParameter('Status.isActive') != null) && empty($tag['TagLinkURL']))
                continue;
            if($this->TagsHelper->matchPartial($partial, $tag))
                return true;
        }

        return false;
    }

    public function hasTag()
    {
        $tags = $this->getLocal('LinkTags');
        $partial = new TagPartial($this->getParameter('Partial'));

        foreach((array)$tags as $tag) {
            if (($this->getParameter('Status.isActive') != null) && empty($tag['TagLinkURL']))
                continue;
            if($this->TagsHelper->matchPartial($partial, $tag))
                return true;
        }

        return false;
    }



    /**
     * Returns a subset of tags filtered according to the params passed.
     *
     * Expected Params:
     *  Tags                array  An Array of tags that we want to filter
     *  Partial             string (optional) A CSV string of types that we want
     *  FilterElement       string (required only with FilterType. Optional otherwise) The tag element to filter for
     *  FilterSlug          string (optional) The slug to filter for
     *  FilterRole          string (optional) The TagRole to match
     *  FilterLinkStatus    string (optional) The TagLinkStatus to match
     *  FilterLinkURLExists string (optional) If set, then a value is required in TagLinkURL
     *  return              string If set to 'count', the function will return a count of the number of tags matched.
     *                             Otherwise should be set to Type, Element, Slug, Role, (any other Tag* attribute)
     *                             which specifies what will be returned.
     *  ReturnString        string If specified and Param 'return' is not set to 'count', then this string will be returned
     *                             with '%ReturnValue%' in the string replaced with the value the value from the tag.
     *
     * @return string
     */
    public function filter()
    {
        $count  = 0;

        $tags = $this->getParameter('Tags');
        if($tags === null)
            $tags = $this->getLocal('LinkTags');

        $maxRows = $this->getParameter('MaxRows');

        $partial = $this->getParameter('Partial');

        $partials = array();
        if(!empty($partial)) {
            $partialsStr = explode(',',$partial);
            foreach($partialsStr as $partial)
                $partials[] = new TagPartial($partial);
        }

        $return = $this->getParameter('Return');
        if(empty($return))
            $return = 'TagLinkTitle';

        $value = '';

        if (empty($tags)) return;
        foreach ((array)$tags as $tag) {
            if (!empty($partials)) {
                    $found = false;
                    foreach($partials as $partial) {
                        if($this->TagsHelper->matchPartial($partial, $tag))
                        {
                            $found = true;
                            break;
                        }
                    }
                    if(!$found)
                        continue;
            }

            if (($this->getParameter('Status.eq') != null) && $tag['TagLinkStatus'] != $this->getParameter('Status.eq'))
                continue;
            if (($this->getParameter('Status.isActive') != null) && empty($tag['TagLinkURL']))
                continue;

            $count++;

            if ($return != 'count' && ($tag instanceof Tag || is_array($tag))) {
                $value .= $tag[$return] . ', ';
            }

            if(!empty($maxRows) && $count == $maxRows)
                break;

        }
        if ($return == 'count')
            return $count;
        if ($this->getParameter('ReturnString') != null)
            $value = str_replace("%ReturnValue%", $value, $this->getParameter('ReturnString'));

        return substr($value, 0, -2);

    }

    public function links()
    {
        $tags = $this->getParameter('Tags');

        if($tags === null)
            $tags = $this->getLocal('LinkTags');

        $partial = $this->getParameter('Partial');

        $partials = array();
        if(!empty($partial)) {
            $partialsStr = explode(',',$partial);
            foreach($partialsStr as $partial)
                $partials[] = new TagPartial($partial);
        }

        if (empty($tags)) return;

        $result = '';

        foreach ((array)$tags as $tag) {
            if (!empty($partials)) {
                    $found = false;
                    foreach($partials as $partial) {
                        if($this->TagsHelper->matchPartial($partial, $tag))
                        {
                            $found = true;
                            break;
                        }
                    }
                    if(!$found)
                        continue;
            }

            if (($this->getParameter('Status.eq') != null) && $tag['TagLinkStatus'] != $this->getParameter('Status.eq'))
                continue;
            if (($this->getParameter('Status.isActive') != null) && empty($tag['TagLinkURL']))
                continue;

            if(!empty($tag['TagLinkURL']))
            {
                $result .= "<a href=\"".$tag['TagLinkURL']."\">".$tag['TagLinkTitle']."</a>, ";
            } else {

                $result .= $tag['TagLinkTitle'].", ";
            }

        }
        if(empty($result))
            return $result;

        return substr($result, 0, -2);
    }

    /**
     * Returns a ;-separated list of partials that match the tags given.
     *
     * Params Accepted: Slugs (A list of all slugs to build our partials from. CSV),
     *                  TagElement,
     *                  TagPredicate,
     *                  TagName,
     *                  TagValue,
     *                  Replace (Value should be TagElement|TagPredicate|TagName|TagValue)
     *
     * @return string a list of partials that match the tags given
     */
//    public function buildPartials ()
//    {
//        if (($this->getParameter('Slugs') == null) || ($this->getParameter('TagElement') == null) ||
//            ($this->getParameter('TagType') == null) || ($this->getParameter('Replace') == null))
//                return;
//
//        $slugs    = StringUtils::smartExplode($this->getParameter('Slugs'));
//        $partials = array();
//
//        foreach ((array)$slugs as $slug) {
//            $params[$this->getParameter('Replace')] = SlugUtils::createSlug($slug);
//            $partial = $this->getParameter('TagElement') . '-' . $this->getParameter('TagType') . ':' . @$this->getParameter('TagSlug');
//            if ($this->getParameter('TagRole') != null)
//                $partial .= '#' . @$this->getParameter('TagRole');
//
//            $partials[] = $partial;
//        }
//
//        return join(';', $partials);
//
//    }

    /**
     * Returns a ;-separated list of slugs from the tags given
     *
     * Expected Params:
     *  Tags        array  (optional) an Array of tags
     *  TagElement  string The TagElement that tags much match
     *  TagType     string (optional) The TagType that tags must match
     *  TagSlug     string (optional) The TagSlug that tags must match
     *  TagRole     string (optional) The TagRole that tags must match
     *  TagValue    string (optional) The TagValue that tags must match
     *
     * Expected Locals:
     *  SerializedLinkTags array if param 'Tags' is not specifed, this is used. Should be an array of tags.
     *
     * @return string
     */
//    public function getSlugsFromTags()
//    {
//        $slugs = array();
//        $tags = $this->getParameter('Tags')!=null?$this->getParameter('Tags'):array();
//        if (empty($tags))
//            $tags = $this->getLocal('SerializedLinkTags');
//
//        if (empty($tags) || ($this->getParameter('TagElement') == null))
//            return;
//        foreach ($tags as $tag) {
//            foreach (array('TagElement', 'TagType', 'TagSlug', 'TagRole', 'TagValue') as $key) {
//                if ($this->getParameter($key) == null)
//                    continue;
//
//                if (strcmp($tag[$key], $this->getParameter($key)) !== 0)
//                    continue 2;
//            }
//            $slugs[] = $tag['TagSlug'];
//        }
//        return join(';', $slugs);
//    }



}
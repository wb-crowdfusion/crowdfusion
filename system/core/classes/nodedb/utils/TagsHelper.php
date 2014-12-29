<?php
/**
 * TagsHelper
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
 * @version     $Id: TagsHelper.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * TagsHelper
 *
 * @package     CrowdFusion
 */
class TagsHelper
{

    protected $ElementService;

    public function setElementService($ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function filterTags(TagPartial $tagPartial, array $tags, $returnFirstMatch = false)
    {
        $matches = array();

        foreach($tags as $tag) {
            if($this->matchPartial($tagPartial,$tag))
                if($returnFirstMatch)
                    return $tag;
                else
                    $matches[] = $tag;
        }

        return $matches;
    }

    public function matchPartial(TagPartial $tagPartial, $tagOrPartial)
    {
        return $this->match($tagPartial, $tagOrPartial, false);
    }

    public function matchPartialExact(TagPartial $tagPartial, $tagOrPartial)
    {
        return $this->match($tagPartial, $tagOrPartial, true);
    }


    protected function match(TagPartial $tagPartial, $tagOrPartial, $exact = false)
    {
//        if(!($tagOrPartial instanceof Tag) && !($tagOrPartial instanceof TagPartial))
//            throw new Exception('Unable to match, $tagOrPartial must be instance of Tag or TagPartial');

//        $tagArray = $tagOrPartial->toArray();
//        $partialArray = $tagPartial->toArray();
        foreach(array('TagElement', 'TagAspect', 'TagSlug', 'TagRole', 'TagValue') as $key) {
            if (!$exact && empty($tagPartial[$key]))
                continue;

            if ($key == 'TagAspect') {
                if (!empty($tagOrPartial[$key])
                   && strcmp($tagOrPartial[$key], $tagPartial[$key]) !== 0) // compare TagPartial w/ TagPartial, same TagAspect?
                    return false;
                else if (!$this->ElementService->getBySlug($tagOrPartial['TagElement'])->hasAspect($tagPartial['TagAspect']))
                    return false;
            } else if (strcmp($tagOrPartial[$key], $tagPartial[$key]) !== 0)
                return false;
        }
        return true;
    }






}
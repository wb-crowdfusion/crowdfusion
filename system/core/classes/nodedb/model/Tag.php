<?php
/**
 * Tag
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
 * @version     $Id: Tag.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Tag
 *
 * @package     CrowdFusion
 * @property string $TagDirection
 * @property string $TagElement
 * @property string $TagSlug
 * @property string $TagRole
 * @property string $TagRoleDisplay
 * @property string $TagValue
 * @property string $TagValueDisplay
 * @property mixed $TagSortOrder
 * @property int $TagSectionID
 * @property TagPartial $MatchPartial
 * @property int $TagElementID
 * @property int $TagLinkID
 * @property NodeRef $TagLinkNodeRef
 * @property string $TagLinkRefURL
 * @property Node $TagLinkNode
 * @property string $TagLinkTitle
 * @property string $TagLinkStatus
 * @property Date $TagLinkActiveDate
 * @property mixed $TagLinkSortOrder
 * @property string $TagLinkURL
 */
class Tag extends Object
{

    // for reference only
    // protected static $persistent = array(
    //                 'TagID',
    //                 'TagSectionID',
    //                 'TagElementID',
    //                 'TagSlug',
    //                 'TagRole',
    //                 'TagRoleDisplay',
    //                 'TagValue',
    //                 'TagValueDisplay',
    //                 'TagSortOrder',
    //             );

//    protected static $transient = array(
//                 'TagSite',
//                 'TagElement',
//                    'TagDirection',
//                    'TagLinkRefURL',
//                    'TagLinkURL',
//                    'TagLinkSiteID',
//                    'TagLinkElementID',
//                    'TagLinkTitle',
//                    'TagLinkStatus',
//                    'TagLinkActiveDate',
//                    'TagLinkSortOrder',
//                );


    public function __construct($tagOrElement, $slug = '', $role = '', $value = '', $valuedisplay = '') {

        $this->fields = array(
            'TagDirection' => 'out',
            'TagElement' => '',
            'TagSlug' => '',
            'TagRole' => '',
            'TagRoleDisplay' => '',
            'TagValue' => '',
            'TagValueDisplay' => '',
            'TagSortOrder' => 0,
            'TagSectionID' => 0,
        //      'TagString' => '',
            );

        if($tagOrElement instanceof Tag || $tagOrElement instanceof TagPartial) {
            $this->fields = array_merge($this->fields, $tagOrElement->toArray());
        } else {

            if(is_array($tagOrElement)) {
                foreach($tagOrElement as $key => $val)
                {
                    $this->fields[$key] = $val;
                    unset($key);
                    unset($val);
                }
            } else if (is_string($tagOrElement) || $tagOrElement instanceof NodeRef) {

                if($tagOrElement instanceof NodeRef)
                {
                    $slug = $tagOrElement->getSlug();
                    $tagOrElement = $tagOrElement->getElement()->getSlug();
                }

                // assume first param is element
                if(!empty($slug) || !empty($role) || !empty($value) || !empty($valuedisplay)) {

                    $this->fields['TagElement'] = $tagOrElement;
                    $this->fields['TagSlug'] = $slug;
                    $this->fields['TagRole'] = $role;
//                    $this->fields['TagRoleDisplay'] = $roledisplay;
                    $this->fields['TagValue'] = $value;
                    $this->fields['TagValueDisplay'] = $valuedisplay;

                } else {

                    if(($pos = strpos($tagOrElement, '"')) !== FALSE) {
                        $parseTag = substr($tagOrElement, 0, $pos);
                        if(substr($tagOrElement, -1) != '"')
                            throw new TagException('Invalid tag: TagValueDisplay must end in "');
                        $this->fields['TagValueDisplay'] = substr($tagOrElement, $pos+1, -1);
                    } else {
                        $parseTag = $tagOrElement;
                    }

                    if (preg_match("/^(?P<el>[a-z0-9-]+):(?P<s>[a-z0-9\/\-]+)#(?P<r>[a-z0-9-]+)(=(?P<v>.+?)?)?$/",
                                    $parseTag, $m)) {

                        $this->fields['TagElement'] = !empty($m['el'])?$m['el']:'';
                        $this->fields['TagSlug']     = !empty($m['s'])?$m['s']:'';
                        $this->fields['TagRole']     = !empty($m['r'])?$m['r']:'';
                        $this->fields['TagValue']    = !empty($m['v'])?$m['v']:'';
                    }
                }
            }
        }

        if(isset($this->fields['NoValidation']))
            return;

        if(empty($this->fields['TagElement']))
            throw new TagException('Invalid tag: No TagElement was specified');

        if(strtolower($this->fields['TagElement']) !== $this->fields['TagElement'])
            $this->fields['TagElement'] = strtolower($this->fields['TagElement']);

//      $elementObj = AppContext::Element($this->fields['TagElement']);

//      if(empty($elementObj))
//          throw new TagException('Invalid tag: "'.$this->fields['TagElement'].'" is not a valid element');

        if(!isset($this->fields['TagSlug']))
            throw new TagException('Invalid tag: No TagSlug was specified');

        if(strtolower($this->fields['TagSlug']) !== $this->fields['TagSlug'])
            $this->fields['TagSlug'] = strtolower($this->fields['TagSlug']);

        if(!preg_match("/^[a-z0-9-\/]+$/", $this->fields['TagSlug']))
            throw new TagException('Invalid tag: "'.$this->fields['TagSlug'] .'" must be valid slug');

        if(empty($this->fields['TagRole']))
            throw new Exception('Invalid tag: No TagRole was specified');


        if(!preg_match("/^[a-z0-9-\/]+$/", $this->fields['TagRole']))
            $this->fields['TagRole'] = SlugUtils::createSlug($this->fields['TagRole']);

        // TagRoleDisplay inherits TagRole if empty
//        if(empty($this->fields['TagRoleDisplay'])) {
//            $this->fields['TagRoleDisplay'] = SlugUtils::unsluggify($this->fields['TagRole']);
//        }


//        if(!empty($this->tag['TagRoleDisplay']))
//            $this->tag['TagRoleDisplay'] = preg_replace("/\s+/s",' ',$this->tag['TagRoleDisplay']);


        if(!empty($this->fields['TagValue'])) {

            if(!preg_match("/^[a-z0-9-\/]+$/", $this->fields['TagValue']))
                $this->fields['TagValue'] = SlugUtils::createSlug($this->fields['TagValue']);

            // TagValueDisplay inherits TagValue if empty
            if(empty($this->fields['TagValueDisplay'])) {
                $this->fields['TagValueDisplay'] = $this->fields['TagValue'];
            }
        } else {
            if(!empty($this->fields['TagValueDisplay']))
                $this->fields['TagValue'] = SlugUtils::createSlug($this->fields['TagValueDisplay']);
        }

        if(!empty($this->fields['TagValueDisplay']))
            $this->fields['TagValueDisplay'] = preg_replace("/\s+/s",' ',$this->fields['TagValueDisplay']);

        // lowercase all parts
        foreach(array('TagElement', 'TagSlug', 'TagRole', 'TagValue') as $name)
            $this->fields[$name] = strtolower($this->fields[$name]);

        if(!empty($this->fields['MatchPartial']))
            $this->setMatchPartial(new TagPartial($this->fields['MatchPartial']));

        // set TagString to empty so toString forces rebuild
//      $this->fields['TagString'] = '';
//      $this->toString();

    }


    public function getMatchPartial() {
        return isset($this->fields['MatchPartial']) ? $this->fields['MatchPartial'] : null;
    }

    public function setMatchPartial(TagPartial $partial) {
        $this->fields['MatchPartial'] = $partial;
    }

    public function getTagSectionID() {
        return empty($this->fields['TagSectionID'])?0:$this->fields['TagSectionID'];
    }

    public function isOutTag () {
        return (!array_key_exists('TagDirection', $this->fields) || $this->fields['TagDirection'] == 'out');
    }

    public function isInTag () {
        return (array_key_exists('TagDirection', $this->fields) && $this->fields['TagDirection'] == 'in');
    }

    public function matchExact(Tag $tag, $matchSortOrder = false) {

        $tagArray = $tag->toArray();
        $matchArray = array('TagElement', 'TagSlug', 'TagRole', 'TagValue', 'TagValueDisplay'/*, 'TagSectionID'*/);
        if($matchSortOrder)
            $matchArray[] = 'TagSortOrder';
        foreach($matchArray as $key) {
//          error_log('COMPARE ['.$key.'] val1 = ['.$tagArray[$key].'] with val2 = ['.$this->fields[$key].']');
            if((string)$tagArray[$key] !== (string)$this->fields[$key]) {
//                error_log('Unmatched key: '.$key);
                return false;
            }
        }

        return true;
    }

    public function diff(Tag $tag, $matchSortOrder = false) {
        $diffArray = array();
        $tagArray = $tag->toArray();
        $matchArray = array('TagElement', 'TagSlug', 'TagRole', 'TagValue', 'TagValueDisplay'/*, 'TagSectionID'*/);
        if($matchSortOrder)
            $matchArray[] = 'TagSortOrder';
        foreach($matchArray as $key) {
//          error_log('COMPARE ['.$key.'] val1 = ['.$tagArray[$key].'] with val2 = ['.$this->fields[$key].']');
            if((string)$tagArray[$key] !== (string)$this->fields[$key]) {
//                error_log('Unmatched key: '.$key);
                $diffArray[] = $key;
            }
        }

        return $diffArray;
    }

    public function toArray() {
//      if(empty($this->fields['TagString']))
//          $this->fields['TagString'] = $this->toString();

        return $this->fields;
    }

    public function toPersistentArray()
    {
        return array_intersect_key($this->fields, array_flip(array('TagElement', 'TagSlug', 'TagRole', 'TagValue', 'TagValueDisplay', 'TagSortOrder')));
    }

    public function __toString () {
        return !empty($this->TagLinkTitle)?$this->TagLinkTitle:'';
    }

    public function toString($escape_quotes = FALSE) {
        $tagString = '';

        $tagString = $this->fields['TagElement'];

        $tagString .= ':'.$this->fields['TagSlug'];

        $tagString .= '#'.$this->fields['TagRole'];

        //$tagString .= '"'. ($escape_quotes?str_replace('"', '\\"', $this->fields['TagRoleDisplay']):$this->fields['TagRoleDisplay']) .'"';

        if(!empty($this->fields['TagValue'])) {
            $tagString .= '='.$this->fields['TagValue'];

            if(!empty($this->fields['TagValueDisplay']))
                $tagString .= '"'. ($escape_quotes?str_replace('"', '\\"', $this->fields['TagValueDisplay']):$this->fields['TagValueDisplay']) .'"';
        }

        if(!empty($this->fields['TagSectionID'])) {
            $tagString .= '['.$this->fields['TagSectionID'].']';
        }

        if(!empty($this->matchPartial)) {
            $tagString .= '('.$this->matchPartial->toString().')';
        }


//      $this->fields['TagString'] = $tagString;

//      return $this->fields['TagString'];
        return $tagString;
    }


    public static function isValidTag($tag) {
        try {
            new Tag($tag);

            return true;
        }catch(Exception $e) {
            return false;
        }
    }

    public function toPartial() {
        return new TagPartial($this);
    }

    public function getAsMD5ReadyString() {

        $tagString = $this->fields['TagElement'];
        $tagString .= ':'.$this->fields['TagSlug'];
        $tagString .= '#'.$this->fields['TagRole'];
        $tagString .= '='.$this->fields['TagValue'];
        $tagString .= '"'. $this->fields['TagValueDisplay'] .'"';

        return $tagString;

    }

}

?>

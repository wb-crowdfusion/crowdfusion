<?php
/**
 * TagPartial
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
 * @version     $Id: TagPartial.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * TagPartial
 *
 * @package     CrowdFusion
 * @property string $TagElement
 * @property string $TagAspect
 * @property string $TagSlug
 * @property string $TagRole
 * @property string $TagValue
 */
class TagPartial extends Object{

    public function __construct($tagOrElement, $slug = '', $role = '', $value = '') {

        if($tagOrElement instanceof TagPartial || $tagOrElement instanceof Tag) {
            $this->fields = $tagOrElement->toArray();
            return;
        }

        $this->fields = array_merge($this->fields, array('TagElement'  => '',
                                                         'TagAspect'   => '',
                                                         'TagSlug'     => '',
                                                         'TagRole'     => '',
                                                         'TagValue'    => '',
                                                         ));

        if(is_array($tagOrElement))
            $this->fields = array_merge($this->fields, $tagOrElement);
        else if (is_string($tagOrElement)) {

            // assume first param is element
            if(!empty($slug) || !empty($role) || !empty($value)) {

                if(substr($tagOrElement, 0, 1)=='@')
                    $this->fields['TagAspect'] = substr($tagOrElement, 1);
                else
                    $this->fields['TagElement'] = $tagOrElement;
                $this->fields['TagSlug'] = $slug;
                $this->fields['TagRole'] = $role;
                $this->fields['TagValue'] = $value;

            // assume first param is tag string
            } else {

                    $expressions = StringUtils::smartSplit($tagOrElement, ".", '"', '\\"', 2);

                    if (preg_match("/^(((?P<ai>@)?(?P<el>[a-z0-9-]+))?(:(?P<s>[a-z0-9\/\-]+)?)?)?(#(?P<r>[a-z0-9-]+)?)?(=(?P<v>.+?))?$/",
                                    array_shift($expressions), $m)) {

                        if (!empty($m['ai']))
                            $this->fields['TagAspect'] = !empty($m['el'])?$m['el']:'';
                        else
                            $this->fields['TagElement'] = !empty($m['el'])?$m['el']:'';

                        $this->fields['TagSlug']     = !empty($m['s'])?$m['s']:'';
                        $this->fields['TagRole']     = !empty($m['r'])?$m['r']:'';
                        $this->fields['TagValue']    = !empty($m['v'])?$m['v']:'';

                        if(!empty($expressions)) {
//                            if(count($expressions) > 1)
                                $this->fields['ChildPartials'] = current($expressions);
//                            else
//                                $this->fields['ChildPartial'] = $expressions[0];
                        }
                    }

            }
        } else
            throw new Exception('Invalid parameter to TagPartial: '.ClassUtils::getQualifiedType($tagOrElement));

        if (empty($this->fields['TagElement']) && empty($this->fields['TagAspect']) && empty($this->fields['TagRole']))
            throw new TagException('Invalid partial: No element, aspect, or role was specified [' . print_r($tagOrElement, true) . ']');

        if(!empty($this->fields['TagAspect'])) {
//            $this->fields['TagAspect'] = strtolower($this->fields['TagAspect']);

            if(!preg_match("/[a-z0-9-]+/", $this->fields['TagAspect']))
                throw new TagException('Invalid partial: Aspect "'.$this->fields['TagAspect'] .'" must contain only characters or dash');
        }

        if(!empty($this->fields['TagSlug'])) {
            $this->fields['TagSlug'] = strtolower($this->fields['TagSlug']);
            if (!SlugUtils::isSlug($this->fields['TagSlug'], true))
                throw new TagException('Invalid tag: "'.$this->fields['TagSlug'] .'" must be valid slug');
        }

        if(!empty($this->fields['TagRole']) && !SlugUtils::isSlug($this->fields['TagRole']))
                $this->fields['TagRole'] = SlugUtils::createSlug($this->fields['TagRole']);

        if(!empty($this->fields['TagValue']) && !SlugUtils::isSlug($this->fields['TagValue']))
                $this->fields['TagValue'] = SlugUtils::createSlug($this->fields['TagValue']);

        // lowercase all parts
        foreach(array('TagElement', 'TagAspect', 'TagSlug', 'TagRole', 'TagValue') as $name)
            $this->fields[$name] = strtolower($this->fields[$name]);
    }

    public function toArray()
    {
        return $this->fields;
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString()
    {
        $tagString = '';
        //if(!empty($this->fields['TagElement']) || !empty($this->fields['TagAspect'])) {

            if (!empty($this->fields['TagElement']))
                $tagString .= $this->fields['TagElement'];
            else if (!empty($this->fields['TagAspect']))
                $tagString .= '@' . $this->fields['TagAspect'];

            if(!empty($this->fields['TagSlug']))
                $tagString .= ':'.$this->fields['TagSlug'];

            if(!empty($this->fields['TagRole'])) {
                $tagString .= '#' . $this->fields['TagRole'];

                if(!empty($this->fields['TagValue']))
                    $tagString .= '=' . $this->fields['TagValue'];
            }
        //}
        return $tagString;
    }

}

?>

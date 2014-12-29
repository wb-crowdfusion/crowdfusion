<?php
/**
 * NodeRef
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
 * @version     $Id: NodeRef.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeRef
 *
 * @package     CrowdFusion
 */
class NodeRef extends Object
{
    protected $site;
    protected $element;
    protected $slug;

    public function __construct(Element $element, $slug = null)
    {
        $this->element = $element;
        $this->site = $element->getAnchoredSite();
        $this->slug = $slug;
    }

    /**
     * Generates a node based on the element's
     * node class which MUST be either Node or
     * extending Node.
     *
     * @return Node
     */
    public function generateNode()
    {
        $nodeClass = $this->element->NodeClass;
        return new $nodeClass($this);
    }

    public function isFullyQualified()
    {
        return isset($this->slug);
    }

    public function getRefURL()
    {
        return $this->getElement()->getSlug().':'.$this->getSlug();
    }

    public function getAsSlug()
    {
        return $this->getElement()->getSlug().'-'.$this->getSlug();
    }
    public function getAsSafeSlug()
    {
        return $this->getElement()->getSlug().'-'.str_replace('/', '-', $this->getSlug());
    }


    public function getRecordLink()
    {
        return $this->getSite()->getBaseURL().'/'.$this->getElement()->getBaseURL().$this->getSlug().(empty($this->getSite()->ExcludeFinalSlash)?'/':'');
    }

    public function getRecordLinkURI()
    {
        return '/'.$this->getElement()->getBaseURL().$this->getSlug().(empty($this->getSite()->ExcludeFinalSlash)?'/':'');
    }

    public function getSite()
    {
        return $this->site;
    }

    public function getElement()
    {
        //if(is_null($this->element))
            //throw new Exception('Element is missing');
        return $this->element;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function toTagPartial()
    {
        return new TagPartial($this->getElement()->getSlug(), $this->getSlug());
    }

    /**
     * @link http://php.net/manual/en/class.jsonserializable.php
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->getRefURL();
    }

    public function __toString()
    {
        return $this->getRefURL();
    }

    public function __destruct()
    {
        unset($this->element);
        unset($this->site);
        unset($this->slug);
    }
}
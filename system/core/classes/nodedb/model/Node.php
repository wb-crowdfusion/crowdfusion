<?php
/**
 * Node
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
 * @version     $Id: Node.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Node
 *
 * @package     CrowdFusion
 * @property array $Metas
 * @property array $OutTags
 * @property array $InTags
 * @property array $NodePartials
 * @property string $OriginalSlug
 * @property array $Sections
 * @property NodeRef $NodeRef
 * @property string $Slug
 * @property int $SiteID
 * @property int $ElementID
 * @property string $Title
 * @property string $Status
 * @property Date $ActiveDate
 * @property Date $CreationDate
 * @property Date $ModifiedDate
 * @property string $SortOrder
 * @property string $TreeID
 * @property string $TreeParent
 * @property array $Cheaters
 */
class Node extends Object
{
    /**
     * Builds a Node Object from the NodeRef
     *
     * @param NodeRef $nodeRef The NodeRef used to create the Node.
     */
    public function __construct(NodeRef $nodeRef)
    {
        parent::__construct();

        $this->fields['OriginalSlug'] = $nodeRef->isFullyQualified() ? $nodeRef->getSlug() : '';

        $this->setNodeRef($nodeRef);
        $this->fields['Metas'] = array();
        $this->fields['OutTags'] = array();
        $this->fields['InTags'] = array();
    }

    /**
     * You must implement a toModel method in a subclass of Node, defined in
     * a plugin.xml or system.xml in order to have toModel functionality.
     *
     * @return mixed
     *
     * @throws LogicException
     */
    public function toModel()
    {
        $nodeClass = $this->getElement()->NodeClass;
        if ($nodeClass === 'Node') {
            throw new LogicException('You must set a custom node in <node_class/> for element: ' . $this->getElement()->getSlug());
        } else {
            throw new LogicException('You must create a toModel method in: ' . $nodeClass);
        }
    }

    /**
     * Returns our NodeRef
     *
     * @return NodeRef
     */
    public function getNodeRef()
    {
        return $this->fields['NodeRef'];
    }

    /**
     * Changes our NodeRef
     *
     * @param NodeRef $newNodeRef
     */
    public function setNodeRef($newNodeRef)
    {
        $this->fields['NodeRef'] = $newNodeRef;
        $this->fields['Slug'] = $newNodeRef->isFullyQualified()?$newNodeRef->getSlug():'';
    }


    public function getSchema()
    {
        return $this->getNodeRef()->getElement()->getSchema();
    }

    /**
     * Returns the site slug for the node.
     * This is called through the NodeRef
     *
     * @return integer
     */
    public function getSiteSlug()
    {
        return $this->getNodeRef()->getSite()->getSlug();
    }

    /**
     * Returns the ElementID
     *
     * @return integer
     */
    public function getElementID()
    {
        return $this->getNodeRef()->getElement()->getElementID();
    }

    public function getElement()
    {
        return $this->getNodeRef()->getElement();
    }

    public function getSite()
    {
        return $this->getNodeRef()->getSite();
    }

    /**
     * Since we extend Object, it's possible to call
     * setSiteID and have it look like it's worked.
     *
     * We utilize this to throw an exception if this is tried.
     *
     * @param string $siteID Anything, this is ignored
     *
     * @return void
     */
    public function setSiteID($siteID)
    {
        throw new NodeException('Tried to change immutable property: SiteID');
    }

    /**
     * Since we extend Object, it's possible to call
     * setElementID and have it look like it's worked.
     *
     * We utilize this to throw an exception if this is tried.
     *
     * @param string $elementID Anything, this is ignored
     *
     * @return void
     * @throws Exception
     */
    public function setElementID($elementID)
    {
        throw new NodeException('Tried to change immutable property: ElementID');
    }

    /**
     * Sets the Slug
     *
     * @param string $slug A valid
     *
     * @return void
     */
    public function setSlug($slug)
    {
        if(empty($this->fields['Slug']) || empty($this->fields['OriginalSlug']))
        {
            $this->fields['NodeRef'] = new NodeRef($this->getNodeRef()->getElement(), $slug);
        }

        $this->fields['Slug'] = $slug;
    }

    public function getResolvedNodeRef()
    {
        return new NodeRef($this->getNodeRef()->getElement(), $this->getSlug());
    }

    /**
     * Returns the slug for this Node
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->fields['Slug'];
    }

    /**
     * Prevents changing the immutable property: OriginalSlug
     *
     * @param string $slug ignored
     *
     * @return void
     * @throws Exception
     */
    public function setOriginalSlug($slug)
    {
        throw new NodeException('Tried to change immutable property: OriginalSlug');
    }

    /**
     * Returns the immutable value for OriginalSlug
     *
     * @return string
     */
    public function getOriginalSlug()
    {
        if(empty($this->fields['OriginalSlug']))
            throw new NodeException('Cannot retrieve OriginalSlug for record not initialized with a fully qualified NodeRef');

        return $this->fields['OriginalSlug'];
    }


    public function getRecordLink()
    {
        return $this->getNodeRef()->getRecordLink();
    }

    public function getRecordLinkURI()
    {
        return $this->getNodeRef()->getRecordLinkURI();
    }

    public function getRefURL()
    {
        return $this->getNodeRef()->getRefURL();
    }



    /* TAGS */


    protected function _getTags($tags, $role = null) {
        if(is_null($role))
            return $tags;

        return TagUtils::filterTags($tags,$role);
    }

    protected function _getTag($tags) {

        if(count($tags) > 0) {
            reset($tags);
            return current($tags);
        }

        return null;
    }

    public function getAllTags($role = null){
        $in = $this->getInTags($role);
        $out = $this->getOutTags($role);
        return array_merge($in, $out);
    }

    public function getInTags($role = null) {
        return $this->_getTags($this->fields['InTags'], $role);
    }

    public function getInTag($role = null) {
        return $this->_getTag($this->getInTags($role));
    }

    public function getOutTags($role = null) {
        return $this->_getTags($this->fields['OutTags'], $role);
    }

    public function getOutTag($role = null) {
        return $this->_getTag($this->getOutTags($role));
    }


    public function setInTags(array $tags) {
        TagUtils::validateTags($tags, 'in');
        $this->fields['InTags'] = $tags;
        unset($tags);
    }

    public function setOutTags(array $tags) {
        TagUtils::validateTags($tags, 'out');
        $this->fields['OutTags'] = $tags;
        unset($tags);
    }

    public function addOutTags(array $tags) {
        foreach($tags as $tag)
            $this->addOutTag($tag);
    }

    public function addOutTag(Tag $tag) {
        TagUtils::validateTag($tag, 'out');
        $this->fields['OutTags'][] = $tag;

        $this->getNodePartials()->increaseOutPartials($tag->getTagRole());
    }

    public function addInTags(array $tags) {
        foreach($tags as $tag)
            $this->addInTag($tag);
    }

    public function addInTag(Tag $tag) {
        TagUtils::validateTag($tag, 'in');

        $this->fields['InTags'][] = $tag;

        $this->getNodePartials()->increaseInPartials($tag->getTagRole());
    }

    public function replaceOutTags($role, array $tags) {
        $this->removeOutTags($role);
        $this->addOutTags($tags);
    }

    public function replaceOutTag(Tag $tag) {
        $this->removeOutTags($tag->getTagRole());
        $this->addOutTag($tag);
    }

    public function replaceInTags($role, array $tags) {
        $this->removeInTags($role);
        $this->addInTags($tags);
    }

    public function replaceInTag(Tag $tag) {
        $this->removeInTags($tag->getTagRole());
        $this->addInTag($tag);
    }

    public function removeOutTags($role) {
        $this->getNodePartials()->increaseOutPartials($role);
        $this->fields['OutTags'] = TagUtils::deleteTags($this->fields['OutTags'], $role);
    }

    public function removeOutTag(Tag $tag) {
        $this->getNodePartials()->increaseOutPartials($tag->getTagRole());
        $this->fields['OutTags'] = TagUtils::deleteTags($this->fields['OutTags'],$tag,true);
    }

    public function removeInTags($role) {
        $this->getNodePartials()->increaseInPartials($role);
        $this->fields['InTags'] = TagUtils::deleteTags($this->fields['InTags'], $role);
    }

    public function removeInTag(Tag $tag) {
        $this->getNodePartials()->increaseInPartials($tag->getTagRole());
        $this->fields['InTags'] = TagUtils::deleteTags($this->fields['InTags'],$tag,true);
    }



    public function addOutTagsInternal(array $tags) {
        foreach($tags as $tag)
            $this->addOutTagInternal($tag);
    }

    public function addOutTagInternal(Tag $tag) {
        TagUtils::validateTag($tag, 'out');
        $this->fields['OutTags'][] = $tag;
    }

    public function addInTagsInternal(array $tags) {
        foreach($tags as $tag)
            $this->addInTagInternal($tag);
    }

    public function addInTagInternal(Tag $tag) {
        TagUtils::validateTag($tag, 'in');

        $this->fields['InTags'][] = $tag;
    }

    public function replaceOutTagsInternal($role, array $tags) {
        $this->removeOutTagsInternal($role);
        $this->addOutTagsInternal($tags);
    }

    public function replaceOutTagInternal(Tag $tag) {
        $this->removeOutTagsInternal($tag->getTagRole());
        $this->addOutTagInternal($tag);
    }

    public function replaceInTagsInternal($role, array $tags) {
        $this->removeInTagsInternal($role);
        $this->addInTagsInternal($tags);
    }

    public function replaceInTagInternal(Tag $tag) {
        $this->removeInTagsInternal($tag->getTagRole());
        $this->addInTagInternal($tag);
    }

    public function removeOutTagsInternal($role) {
        $this->fields['OutTags'] = TagUtils::deleteTags($this->fields['OutTags'], $role);
    }

    public function removeOutTagInternal(Tag $tag) {
        $this->fields['OutTags'] = TagUtils::deleteTags($this->fields['OutTags'],$tag,true);
    }

    public function removeInTagsInternal($role) {
        $this->fields['InTags'] = TagUtils::deleteTags($this->fields['InTags'], $role);
    }

    public function removeInTagInternal(Tag $tag) {
        $this->fields['InTags'] = TagUtils::deleteTags($this->fields['InTags'],$tag,true);
    }





    public function hasOutTags($partial=null) {
        return count($this->getOutTags($partial)) > 0;
    }

    public function hasInTags($partial=null) {
        return count($this->getInTags($partial)) > 0;
    }

    public function hasTags($partial=null) {
        return $this->hasOutTags($partial) || $this->hasInTags($partial);
    }


    /* META */

    public function setMetas(array $tags) {
        $this->fields['Metas'] = $tags;
//        foreach($tags as $name => $meta)
//            if(is_object($meta))
//                $this->fields['Metas'][$meta->getMetaName()] = $meta->getMetaValue();
//            else
//                $this->fields['Metas'][$name] = $meta;
    }

    public function getMetas() {
        return $this->fields['Metas'];
//        $array = array();
//        foreach($this->fields['Metas'] as $name => $value)
//            $array[] = new Meta($name, $value);
//        return $array;
    }

//    public function getMetasAsArray()
//    {
//        return $this->fields['Metas'];
//    }

    public function setMeta($name, $value = null)
    {
        $name = ltrim($name, '#');
        if(is_null($value))
            unset($this->fields['Metas'][$name]);
        else
            $this->fields['Metas'][$name] = new Meta($name,$value);

        $this->getNodePartials()->increaseMetaPartials($name);
    }

    public function updateMeta($name, $value = null)
    {
        $this->setMeta($name, $value);
    }

    public function removeMeta($name) {
        $name = ltrim($name, '#');
        unset($this->fields['Metas'][$name]);
    }

    public function getMeta($name)
    {
        $name = ltrim($name, '#');
        if(array_key_exists($name, $this->fields['Metas']))
            return $this->fields['Metas'][$name];

        return null;
    }


    public function jump($name)
    {
        if(count($array = explode('.', $name, 2)) > 1) {
            list($first, $rest) = $array;

            $value = null;
            if (count($vArray = explode('=', $first, 2)) > 1) {
                list($first, $value) = $vArray;
            }

            if($this->hasOutTags($first))
                $tags = $this->getOutTags($first);
            else if($this->hasInTags($first))
                $tags = $this->getInTags($first);
            else
                $tags = null;

            if(count($tags) > 0) {
                if (!empty($value)) {
                    foreach ($tags as $tag) {
                        if ($tag->TagValue == $value && $tag->TagLinkNode != null) {
                            return $tag->TagLinkNode->jump($rest);
                        }
                    }
                }
                else if (($current = current($tags)) && $current->TagLinkNode != null) {
                    return $current->TagLinkNode->jump($rest);
                }
            }

            return null;
        } else {

            if($this->hasMeta($name))
                return $this->getMetaValue($name);
            elseif($this->hasOutTags($name))
                return $this->getOutTags($name);
            elseif($this->hasInTags($name))
                return $this->getInTags($name);

        }
    }

    public function getMetaValue($name)
    {
        return ($m = $this->getMeta($name))!==null?$m->getMetaValue():null;
    }

    public function hasMeta($name)
    {
        return $this->getMeta($name) !== null;
    }

    /**
     * Returns a reference to the partials for this node.
     *
     * @return NodePartials
     */
    public function getNodePartials()
    {
        if(!array_key_exists('NodePartials', $this->fields) || is_null($this->fields['NodePartials']))
            $this->fields['NodePartials'] = new NodePartials();

        return $this->fields['NodePartials'];
    }

    /**
     * Sets the NodePartials
     *
     * @param NodePartials $nodePartials The NodePartials to set
     *
     * @return void
     */
    public function setNodePartials(NodePartials $nodePartials)
    {
        $this->fields['NodePartials'] = $nodePartials;
    }


    public function getCheaters()
    {
        if(array_key_exists('Cheaters', $array))
            return $array['Cheaters'];

        throw new NodeException('Cheaters have not been populated');
    }

    public function toArray()
    {
        $array = parent::toArray();
        if(array_key_exists('Cheaters', $array))
            $array = array_merge($array, $array['Cheaters']);
        return $array;
    }

}

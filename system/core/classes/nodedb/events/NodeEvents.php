<?php
/**
 * NodeEvents
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
 * @version     $Id: NodeEvents.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeEvents
 *
 * @package     CrowdFusion
 */
class NodeEvents
{

    protected $enabled = true;

    public function setNodeCache(NodeCacheInterface $NodeCache)
    {
        $this->NodeCache = $NodeCache;
    }

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    public function areEventsEnabled()
    {
        return $this->enabled;
    }

    public function disableEvents()
    {
        $this->enabled = false;
        return $this;
    }

    public function enableEvents()
    {
        $this->enabled = true;
        return $this;
    }

    public function fireNodeEvents($action, $location, NodeRef &$nodeRef, &$param2 = null, &$param3 = null)
    {
        if($location == 'post')
            $this->NodeCache->deleteNode($nodeRef);

        if(!$this->enabled)
            return;

        $location = !empty($location)?'.'.$location:'';

        $this->Events->trigger('Node.'.$action.$location, $nodeRef, $param2, $param3);
        foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
            $this->Events->trigger('Node.@'.$aspect->Slug.'.'.$action.$location, $nodeRef, $param2, $param3);
    }

    public function fireValidationEvents($action, Errors &$errors, NodeRef &$nodeRef, &$param2 = null)
    {
        if(!$this->enabled)
            return;

        $location = '.validate';

        $this->Events->trigger('Node.'.$action.$location, $errors, $nodeRef, $param2);
        foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
            $this->Events->trigger('Node.@'.$aspect->Slug.'.'.$action.$location, $errors, $nodeRef, $param2);
    }

    public function fireTagEvents($action, $location, NodeRef &$nodeRef, NodeRef &$nodeRef2, &$tag)
    {
        $this->NodeCache->deleteTags($tag->getTagDirection(), $nodeRef, $nodeRef2);

        if(!$this->enabled)
            return;

        $this->Events->trigger('Node.'.$action.'.'.$location, $nodeRef, $nodeRef2, $tag);
        foreach((array)$nodeRef->getElement()->getAspects() as $aspect){
            $this->Events->trigger('Node.@'.$aspect->Slug.'.'.$action.'.'.$location, $nodeRef, $nodeRef2, $tag);
            $this->Events->trigger('Node.@'.$aspect->Slug.'.'.$action.'.#'.$tag->getTagRole().'.'.$location, $nodeRef, $nodeRef2, $tag);

            foreach((array)$nodeRef2->getElement()->getAspects() as $aspect2)
                $this->Events->trigger('Node.@'.$aspect->Slug.'.@'.$aspect2->Slug.'.'.$action.'.#'.$tag->getTagRole().'.'.$location, $nodeRef, $nodeRef2, $tag);
        }
    }

    public function fireMetaEvents($action, $location, NodeRef &$nodeRef, &$meta, &$originalMeta = null)
    {

        $this->NodeCache->deleteMeta($meta->getMetaStorageDatatype(), $nodeRef);

        if(!$this->enabled)
            return;

        $this->Events->trigger('Node.'.$action.'.'.$location, $nodeRef, $meta, $originalMeta);
        foreach((array)$nodeRef->getElement()->getAspects() as $aspect){
            $this->Events->trigger('Node.@'.$aspect->Slug.'.'.$action.'.'.$location, $nodeRef, $meta, $originalMeta);
            $this->Events->trigger('Node.@'.$aspect->Slug.'.'.$action.'.#'.$meta->getMetaName().'.'.$location, $nodeRef, $meta, $originalMeta);
        }
    }

//    public function fireSectionEvents($action, $location, NodeRef &$nodeRef, &$section, &$originalSection = null)
//    {
//        if(!$this->enabled)
//            return;
//
//        $this->NodeCache->deleteSections($nodeRef);
//
//        $this->Events->trigger('Node.'.$action.'.'.$location, $nodeRef, $section, $originalSection);
//        foreach((array)$nodeRef->getElement()->getAspects() as $aspect){
//            $this->Events->trigger('Node.@'.$aspect->Slug.'.'.$action.'.'.$location, $nodeRef, $section, $originalSection);
//            $this->Events->trigger('Node.@'.$aspect->Slug.'.'.$action.'.#'.$section->getSectionType().'.'.$location, $nodeRef, $section, $originalSection);
//        }
//    }
}
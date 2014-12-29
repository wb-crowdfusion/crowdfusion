<?php
/**
 * NodeRefService
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
 * @version     $Id: NodeRefService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeRefService
 *
 * @package     CrowdFusion
 */
class NodeRefService
{


    protected $Events;
    protected $ElementService;
    protected $AspectService;
    protected $SiteService;
    protected $Logger;

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    public function setElementService($ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function setAspectService(AspectService $AspectService)
    {
        $this->AspectService = $AspectService;
    }

    public function setSiteService(SiteService $SiteService)
    {
        $this->SiteService = $SiteService;
    }

    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

    public function parseFromString($noderefstr)
    {
        try {

            $arr = explode(':', $noderefstr, 2);

            if(count($arr) == 2)
            {
                list($element, $slug) = $arr;

                $element = $this->ElementService->getBySlug($element);

                return new NodeRef($element, SlugUtils::createSlug($slug, true));
            } else {

                $element = $this->ElementService->getBySlug($noderefstr);

                return new NodeRef($element);

            }
        }catch(Exception $e) {$this->Logger->debug($e->getMessage());}

        return null;
    }

    public function parseFromTag(Tag $tag)
    {
        try {

            $element = $tag->getTagElement();
            $slug = $tag->getTagSlug();

            $element = $this->ElementService->getBySlug($element);

            return new NodeRef($element, $slug);
        }catch(Exception $e) {}

        return null;
    }

    public function parseFromTags(array $tags)
    {

        $nodeRefs = array();

        foreach($tags as $tag) {
            $nodeRef = $this->parseFromTag($tag);
            if(!is_null($nodeRef))
                $nodeRefs[] = $nodeRef;
        }

        return $nodeRefs;
    }


    //public function parseFromNodeQuery(NodeQuery $nodeQuery)
    public function normalizeNodeQuery(NodeQuery $nodeQuery)
    {
        if($nodeQuery->getParameter('NodeQuery.normalized') == null)
        {
            $this->Events->trigger('Node.normalizeQuery', $nodeQuery);
            $nodeQuery->setParameter('NodeQuery.normalized', true);
        }

//
//        if($nodeQuery->hasParameter('NodePartials.eq') && $nodeQuery->hasParameter('NodeRefs.normalized') && $nodeQuery->hasParameter('NodeRefs.fullyQualified'))
//            return $nodeQuery;


        if(!$nodeQuery->hasParameter('NodePartials.eq'))
            $nodeQuery->setParameter('NodePartials.eq', new NodePartials($nodeQuery->getParameter('Meta.select'), $nodeQuery->getParameter('OutTags.select'), $nodeQuery->getParameter('InTags.select')));

        // NODEREFS
        if($nodeQuery->hasParameter('NodeRefs.in'))
        {
            $noderefsin = $nodeQuery->getParameter('NodeRefs.in');

            if(is_string($noderefsin)) {
                $nodeRefsIn = array();

                $noderefsin = explode(',',$noderefsin);
                foreach($noderefsin as $noderefstr)
                {
                    $noderef = $this->parseFromString(trim($noderefstr));
                    if(!empty($noderef))
                        $nodeRefsIn[] = $noderef;
                }

            } else {
                $nodeRefsIn = $noderefsin;

            }

            $nodeQuery->setParameter('NodeRefs.normalized', $nodeRefsIn);

            if(!$nodeQuery->hasParameter('NodeRefs.fullyQualified'))
                $nodeQuery->setParameter('NodeRefs.fullyQualified', true);

            return $nodeQuery;
//            return array($nodeRefsIn, $nodePartials,
//                $nodeQuery->hasParameter('NodeRefs.fullyQualified')?$nodeQuery->getParameter('NodeRefs.fullyQualified'):true);

        }



        $nodeRefs = array();

        if(!$nodeQuery->hasParameter('Elements.in'))
            throw new NodeException('Unable to query nodes without Elements.in specified');

        $elementSlugs = $nodeQuery->getParameter('Elements.in');
        if(!is_array($elementSlugs))
            $elementSlugs = StringUtils::smartExplode($elementSlugs);

//        $firstElement = null;

        $sites = array();

        if($nodeQuery->hasParameter('Sites.in')) {
            $siteSlugs = $nodeQuery->getParameter('Sites.in');
            if(!is_array($siteSlugs))
                $siteSlugs = StringUtils::smartExplode($siteSlugs);

            $sites = $this->SiteService->multiGetBySlug($siteSlugs);
        }

        $elements = array();
        foreach($elementSlugs as $ek => $elementSlug)
        {
            if(substr($elementSlug, 0, 1) == '@'){

                $aspectSlug = substr($elementSlug, 1);
                $aspect = $this->AspectService->getBySlug($aspectSlug);

                if($aspect->ElementMode == 'one' || $aspect->ElementMode == 'anchored')
                {

                    $elements = array_merge($elements, (array)$this->ElementService->findAllWithAspect($aspectSlug));
                    unset($elementSlugs[$ek]);

                } else {

                    if(!empty($sites))
                        foreach((array)$sites as $site)
                            $elements = array_merge($elements, (array)$this->ElementService->findAllWithAspect($aspectSlug, $site->getSlug()));
                    else
                        $elements = array_merge($elements, (array)$this->ElementService->findAllWithAspect($aspectSlug));

                }
            } else {

                $elements[] = $this->ElementService->getBySlug($elementSlug);
            }

        }

        if(empty($elements))
            throw new NoElementsException('No elements found for expression ['.$nodeQuery->getParameter('Elements.in').'].');


        $allFullyQualified = false;

//        $allSites = array();
//        if($nodeQuery->hasParameter('Sites.in')) {
//            $siteSlugs = $nodeQuery->getParameter('Sites.in');
//            if(!is_array($siteSlugs))
//                $siteSlugs = StringUtils::smartExplode($siteSlugs);
//
//            $allSites = $this->SiteService->multiGetBySlug($siteSlugs);
//        } else if ($nodeQuery->hasParameter('SiteIDs.in')) {
//            $siteIDs = $nodeQuery->getParameter('SiteIDs.in');
//            if(!is_array($siteIDs))
//                $siteIDs = StringUtils::smartExplode($siteIDs);
//
//            foreach($siteIDs as $siteID)
//                $allSites[] = $this->SiteService->getByID($siteID);
//
//        } else {
//            $allSites = $this->SiteService->findAll()->getResults();
//        }


        foreach($elements as $element)
        {

//            if($element->isAnchored() && $nodeQuery->getParameter('Elements.ignoreAnchoredSite') == null){
//                $sites = array($element->getAnchoredSite());
//            } else {
//                $sites = $allSites;
//            }

//            foreach($sites as $site)
//            {
                if (($slugs = $nodeQuery->getParameter('Slugs.in')) != null) {
                    if(!is_array($slugs))
                        $slugs = StringUtils::smartExplode($slugs);

                    $allFullyQualified = true;
                    foreach($slugs as $slug)
                        $nodeRefs[] = new NodeRef($element, SlugUtils::createSlug($slug, true));
                }else {
                    $nodeRefs[] = new NodeRef($element);
                }
//            }
        }


        $nodeQuery->setParameter('NodeRefs.normalized', $nodeRefs);

        //if(!$nodeQuery->hasParameter('NodeRefs.fullyQualified'))
        $nodeQuery->setParameter('NodeRefs.fullyQualified', $allFullyQualified);
        //return array($nodeRefs, $nodePartials, $allFullyQualified);
        return $nodeQuery;

    }


    public function allFromAspect($aspect)
    {

        $elements = $this->ElementService->findAllWithAspect($aspect);

        if(empty($elements))
            return array();

        $noderefs = array();

        foreach($elements as $element) {
            $noderefs[] = new NodeRef($element);
        }

        return $noderefs;
    }

    public function oneFromAspect($aspect, $slug = null, $restrictSiteSlug = null)
    {

        $elements = $this->ElementService->findAllWithAspect($aspect, $restrictSiteSlug);

        if(empty($elements))
            throw new NodeException('No elements have aspect ['.$aspect.']');

        if(count($elements) > 1)
            throw new NodeException('More than 1 element has aspect ['.$aspect.']');

        $element = current($elements);

        return new NodeRef($element, $slug);
    }


    public function generateNodeRef(NodeRef $nodeRef, $title = null, $useTime = false)
    {

        if(is_null($nodeRef))
            throw new NodeException('Cannot generate NodeRef, $nodeRef is null');

        $slug = $nodeRef->getSlug();
        if (empty($slug)) {
            if(empty($title))
                throw new NodeException('Cannot generate NodeRef without title');
            if($useTime)
                $slug = SlugUtils::createSlug(substr($title, 0, 237).'-'.(floor(microtime(true)*100)),$nodeRef->getElement()->isAllowSlugSlashes());
            else
                $slug = SlugUtils::createSlug(substr($title, 0, 255),$nodeRef->getElement()->isAllowSlugSlashes());
            $nodeRef = new NodeRef($nodeRef->getElement(), $slug);
        } else {
            if($useTime)
                $title = substr($slug, 0, 237).'-'.(floor(microtime(true)*100));  // makes 12 digits (127213194347)
            else
                $title = substr($slug, 0, 255);

            $nodeRef = new NodeRef($nodeRef->getElement(), SlugUtils::createSlug($title,$nodeRef->getElement()->isAllowSlugSlashes()));
        }

        return $nodeRef;
    }

}
<?php
/**
 * ElementDAO
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
 * @version     $Id: ElementDAO.php 2019 2010-02-17 23:59:09Z ryans $
 */

/**
 * Service to store and retrieve Elements
 *
 * @package     CrowdFusion
 */
class ElementDAO extends AbstractSystemXMLDAO
{
    protected $aspectrel = array();

    protected $AspectDAO;
    protected $SiteService;
    protected $NodeSchemaParser;

    /**
     * [IoC] Creates the ElementDAO
     *
     * @param AspectDAO        $AspectDAO        the AspectDAO
     * @param SiteService      $SiteService      The SiteService
     * @param NodeSchemaParser $NodeSchemaParser The NodeSchemaParser
     */
    public function __construct(AspectDAO $AspectDAO, SiteService $SiteService, NodeSchemaParser $NodeSchemaParser)
    {
        parent::__construct(new Element());

        $this->AspectDAO        = $AspectDAO;
        $this->SiteService      = $SiteService;
//        $this->PluginService    = $PluginService;
        $this->NodeSchemaParser = $NodeSchemaParser;
    }

    /**
     * Sets the schema for the element
     *
     * @param Element $obj The element to analyze
     *
     * @return Element the translated element
     */
    public function preCacheTranslateObject(ModelObject $obj)
    {
        // merge aspects
        //$db = $this->getConnection();

//        $q = new Query();
//        $q->select('aspectid');
//        $q->from('elementaspectrel');
//        $q->where("elementid = {$db->quote($obj->{$obj->getPrimaryKey()})}");

//        $aspectIDs = $db->readCol($q);

//        $this->populateRels();

//        $aspectSlugs = array_key_exists($obj->Slug, $this->aspectrel)?$this->aspectrel[$obj->Slug]['Aspects']:array();

        $aspectSlugs = $obj->getAspectSlugs();

        $schema = new NodeSchema();

        if (!empty($aspectSlugs)) {

            $aspects = $this->AspectDAO->multiGetBySlug($aspectSlugs);

            $newAspects = array();
            foreach ($aspects as $aspect) {
//                $plugin = $this->PluginService->getByID($aspect['PluginID']);
//                if(empty($plugin) || !$plugin->isInstalled() || !$plugin->isEnabled())
//                    continue;


                $aspectSchema = $aspect->getSchema();
                foreach ($aspectSchema->getTagDefs() as $tagDef)
                    $schema->addTagDef($tagDef);
                foreach ($aspectSchema->getMetaDefs() as $metaDef)
                    $schema->addMetaDef($metaDef);

                $newAspects[] = $aspect;
            }

            $obj->setAspects($newAspects);

        }

        $obj->setSchema($schema);
        $obj->setAnchoredSite($this->SiteService->getAnchoredSite());
        $obj->setAnchoredSiteSlug($obj->getAnchoredSite()->getSlug());

        return $obj;
    }

    /**
     * Finds matching elements
     *
     * @param DTO $dto A DTO that can accept the following params:
     *                  <PrimaryKey>
     *                  Slug
     *                  PluginID
     *                  Anchored
     *                  DefaultOrder
     *                  AnchoredSiteID
     *                  StartDate           string  ModifiedDate is after this
     *                  EndDate             string  ModifiedDate is before this
     *                  Search              string  Matches any part of name, slug, or description
     *                  IncludesAllAspects  string  A list of aspects that results must be related to
     *                  IncludesAspects     string  A list of aspects that results must relate to at least one of
     *
     * @return DTO The filled DTO object
     */
    public function findAll(DTO $dto)
    {

        if($dto->hasParameter($this->getModel()->getPrimaryKey())
         || $dto->hasParameter('Slug')
         || $dto->hasParameter('PluginID')
//         || $dto->hasParameter('DefaultOrder')
//         || $dto->hasParameter('StartDate')
//         || $dto->hasParameter('EndDate')
//         || $dto->hasParameter('Search')
         || $dto->getLimit() != null
         || $dto->getOffset() != null
         || $dto->getOrderBys() != null
         ) {

            $sd    = __CLASS__.(string)serialize($dto);
            $slugs = $this->SystemCache->get($sd);

            if ($slugs === false) {

                // find slugs

                $this->loadSource();

                $slugs = array();
                $sort_array = array();

                $dir = 'ASC';

                $orderbys = $dto->getOrderBys();
                if(!empty($orderbys))
                    foreach($orderbys as $col => $dir) {}
                else
                    $col = 'Slug';

                foreach($this->objectsBySlug as $slug => $obj)
                {

                    if(($val = $dto->getParameter('Slug')) != null)
                    {
                        if($obj->Slug != $val)
                            continue;
                    }

                    if(($val = $dto->getParameter('PluginID')) != null)
                    {
                        if($obj->PluginID != $val)
                            continue;
                    }

                    $slugs[] = $slug;
                    $sort_array[] = $obj[$col];
                }

                array_multisort($sort_array, strtolower($dir)=='asc'?SORT_ASC:SORT_DESC, SORT_REGULAR, $slugs);
                $this->SystemCache->put($sd, $slugs, 0);
            }

        } else {

            $this->populateRels();
            if(!empty($this->aspectrel))
            {

                foreach($this->aspectrel as $elementSlug => $element)
                {

                    if ($dto->hasParameter('IncludesAspect')) {

                        $aspect = strtolower($dto->getParameter('IncludesAspect'));
                        if(!in_array($aspect, (array)$element['Aspects']))
                            continue;
                    }

                    $slugs[] = $elementSlug;

                }
            }

        }

        $results = array();

        if(!empty($slugs))
        {
            // retrieve objects
            $rows = $this->multiGetBySlug($slugs);

            foreach ($slugs as $slug)
                $results[] = $rows[$slug];
        }

        $dto->setResults($results);


        return $dto;
    }

    protected function populateRels()
    {
        if(empty($this->aspectrel))
        {
            $this->aspectrel = $this->SystemCache->get('elementaspectrel');
            if($this->aspectrel === false)
            {

                // find slugs
                $this->loadSource();

                foreach($this->objectsBySlug as $row)
                {
                    $slugs[] = $row['Slug'];
                    $this->aspectrel[$row['Slug']] = array('Aspects'=> $row['AspectSlugs']);
                }

                $this->SystemCache->put('elementaspectrel', $this->aspectrel, 0);
            }
        }
    }

}

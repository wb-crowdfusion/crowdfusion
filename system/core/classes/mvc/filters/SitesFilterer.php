<?php
/**
 * SitesFilterer
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
 * @version     $Id: ElementsFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SitesFilterer
 *
 * @package     CrowdFusion
 */
class SitesFilterer extends AbstractFilterer
{

    protected $SiteService;

    public function setSiteService(SiteService $SiteService)
    {
        $this->SiteService = $SiteService;
    }


    public function getBySlug()
    {
        $slug = $this->getRequiredParameter('Slug');
        $property = $this->getRequiredParameter('Property');
        $site = $this->SiteService->getBySlug($slug);
        if(!empty($site))
            return $site[$property];        
    }

}
<?php
/**
 * SiteInterceptor
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
 * @version     $Id: SiteInterceptor.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Gets the site object from the context and stores it in the RequestContext.
 *
 * @package     CrowdFusion
 */
class SiteInterceptor {

    protected $RequestContext = null;
    protected $SiteService;

    protected $siteDomain;

    public function __construct(RequestContext $RequestContext, $site)
    {
        $this->RequestContext = $RequestContext;

        $this->site = $site;
    }

    public function preDeploy()
    {

        $siteArray = $this->site;
        if(empty($siteArray))
            throw new Exception('Site array could not be loaded');

        $site = URLUtils::resolveSiteFromArray($siteArray, !empty($siteArray['matched_alias'])?$siteArray['matched_alias']:false);
//
//        $site = new Site();
//        $site->Slug = $siteArray['slug'];
//        $site->Name = isset($siteArray['name'])?$siteArray['name']:$site->Slug;
//        $site->Description = isset($siteArray['description'])?$siteArray['description']:'';
//
//        if(isset($siteArray['domain']))
//            $site->Domain = $siteArray['domain'];
//        if(isset($siteArray['domain_base_uri']))
//            $site->DomainBaseURI = $siteArray['domain_base_uri'];
//        if(isset($siteArray['domain_aliases']))
//            $site->DomainAliases = $siteArray['domain_aliases'];
//        if(isset($siteArray['domain_redirects']))
//            $site->DomainRedirects = $siteArray['domain_redirects'];
//
//        $site->BaseURL = 'http'.($site->isSSL()?'s':'').'://'.rtrim($domain.$site->DomainBaseURI,'/');

        $this->RequestContext->setSite($site);

//        if(isset($siteArray['storagefacilities']))
//        {
//
//            $sfs = $siteArray['storagefacilities'];
//            foreach($sfs as $key => $sfArray)
//            {
//
//                if(empty($sfArray['for']))
//                    throw new Exception('storage_facility property "for" is missing');
//
//                if(empty($sfArray['ref']))
//                    throw new Exception('storage_facility property "ref" is missing');
//
//                $info = new StorageFacilityInfo();
//                $info->For = $sfArray['for'];
//                $info->GenerateRewriteRules = isset($sfArray['generate_rewrite_rules'])?StringUtils::strToBool($sfArray['generate_rewrite_rules']):false;
//                $info->ObjectRef = $sfArray['ref'];
//
//                $params = new StorageFacilityParams();
//                $params->BaseURI = $sfArray['base_uri'];
//                $params->BaseStoragePath = $sfArray['base_storage_path'];
//                $params->Domain = $sfArray['domain'];
//
//                $info->StorageFacilityParams = $params;
//
//                $this->RequestContext->setStorageFacilityInfo($info->For, $info);
//            }
//        }

        foreach($site->getStorageFacilityInfo() as $slug => $sf)
            $this->RequestContext->setStorageFacilityInfo($slug, $sf);

    }
}
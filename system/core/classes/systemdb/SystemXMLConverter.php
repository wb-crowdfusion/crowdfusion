<?php
/**
 * SystemXMLConverter
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
 * @version     $Id: SystemXMLConverter.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SystemXMLConverter
 *
 * @package     CrowdFusion
 */
class SystemXMLConverter {

    protected $DateFactory;

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    public function xmlToPlugin(ModelObject $plugin, SimpleXMLExtended $xml)
    {

        if(($id = $xml->attribute('id')) !== null)
            $plugin->PluginID = intval($id);

        if(($id = $xml->attribute('slug')) !== null)
            $plugin->Slug = strval($id);

        if(($id = $xml->attribute('installed')) !== null)
            $plugin->Installed = StringUtils::strToBool($id);

        if(($id = $xml->attribute('enabled')) !== null)
            $plugin->Enabled = StringUtils::strToBool($id);

        if($xml->path)
            $plugin->Path = strval($xml->path);

        /*
        if ($xml->creation_date)
            $plugin->CreationDate = $this->DateFactory->newStorageDate(strval($xml->creation_date));
        if ($xml->modified_date)
            $plugin->ModifiedDate = $this->DateFactory->newStorageDate(strval($xml->modified_date));
        */

        if (!empty($xml->md5)) {
            $plugin->Md5 = strval($xml->md5);
        } else {
            $plugin->Md5 = '';
        }
        $plugin->Version = strval($xml->info->version);
        $plugin->Priority = intval($xml->info->priority);
        $plugin->Title = strval($xml->info->title);
        $plugin->Description = strval($xml->info->description);
        $plugin->Provider = strval($xml->info->provider);
        $plugin->License = strval($xml->info->license);
        $plugin->Dependencies = strval($xml->info->dependencies);
        $plugin->CFVersions = strval($xml->info->cfversions);
        $plugin->Homepage = strval($xml->info->homepage);
        $plugin->Locked = StringUtils::strToBool($xml->info->locked);

        return $plugin;
    }


    public function pluginToXML(ModelObject $plugin)
    {
        $xml = new SimpleXMLExtended('<plugin/>');
        $info = $xml->addChild('info');

        foreach(array('Version', 'Priority', 'Title', 'Description', 'Provider', 'License', 'Dependencies', 'Homepage', 'Locked') as $key)
        {
            $info->addChild(StringUtils::underscore($key), trim($plugin->$key));
        }

        // CFVersions
        $info->addChild('cfversions', $plugin->CFVersions);

        $xml->addAttribute('id', $plugin->PluginID);
        $xml->addAttribute('slug', $plugin->Slug);
        $xml->addAttribute('installed', ($plugin->isInstalled()?'true':'false'));
        $xml->addAttribute('enabled', ($plugin->isEnabled()?'true':'false'));

        if(empty($plugin->Path))
            throw new Exception('Plugin path is missing');

        $xml->addChild('path', $plugin->Path);
        $xml->addChild('md5', $plugin->Md5);
        // $xml->addChild('modified_date', $this->DateFactory->toStorageDate($plugin->ModifiedDate)->toMySQLDate());
        // $xml->addChild('creation_date', $this->DateFactory->toStorageDate($plugin->CreationDate)->toMySQLDate());

        return $xml;
    }

    public function xmlToElement(ModelObject $element, SimpleXMLExtended $xml)
    {

        if(($id = $xml->attribute('id')) !== null)
            $element->ElementID = intval($id);

        if(($id = $xml->attribute('slug')) !== null)
            $element->Slug = strval($id);

        if(($id = $xml->attribute('pluginid')) !== null)
            $element->PluginID = strval($id);

        /*
        if ($xml->creation_date)
            $element->CreationDate = $this->DateFactory->newStorageDate(strval($xml->creation_date));
        if ($xml->modified_date)
            $element->ModifiedDate = $this->DateFactory->newStorageDate(strval($xml->modified_date));
        */

        foreach(array('Name', 'Description', 'DefaultOrder') as $key)
        {
            $xmlKey = StringUtils::underscore($key);
            $element->$key = strval($xml->info->$xmlKey);
        }

        $element->AllowSlugSlashes = StringUtils::strToBool($xml->info->allow_slug_slashes);
        $element->BaseURL = strval($xml->info->base_url);
        if (isset($xml->info->node_class)) {
            $element->NodeClass = strval($xml->info->node_class);
        } else {
            $element->NodeClass = 'Node';
        }

        $aspectSlugs = array();
        foreach($xml->aspects->children() as $aspectNode)
        {
            if($aspectNode->getName() == 'aspect')
                $aspectSlugs[] = $aspectNode->attribute('slug');
        }
        $element->AspectSlugs = $aspectSlugs;

        if($xml->storage_facility)
        {
            foreach($xml->storage_facility as $sfXML)
            {
                $info = new StorageFacilityInfo();
                $info->For = $sfXML->attribute('for') != null?$sfXML->attribute('for'):'media';
                $info->GenerateRewriteRules = StringUtils::strToBool($sfXML->attribute('generate_rewrite_rules'));
                $info->ObjectRef = strval($sfXML->attribute('ref'));

                $params = new StorageFacilityParams();
                $params->BaseURI = strval($sfXML->base_uri);
                $params->BaseStoragePath = strval($sfXML->base_storage_path);
                $params->Domain = strval($sfXML->domain);

                if(($ssl = $sfXML->attribute('ssl')) !== null)
                    $params->SSL = StringUtils::strToBool($ssl);

                $info->StorageFacilityParams = $params;

                $element->setStorageFacilityInfo($info->For, $info);
            }
        }

        return $element;
    }

    public function elementToXML(ModelObject $element)
    {
        $xml = new SimpleXMLExtended('<element/>');
        $info = $xml->addChild('info');

        foreach(array('Name', 'Description', 'DefaultOrder') as $key)
        {
            $info->addChild(StringUtils::underscore($key), trim($element->$key));
        }

        // BaseURL
        $info->addChild('allow_slug_slashes', ($element->isAllowSlugSlashes()?'true':'false'));
        $info->addChild('base_url', $element->BaseURL);
        $info->addChild('node_class', isset($element->NodeClass) ? $element->NodeClass : 'Node');

        $xml->addAttribute('id', $element->ElementID);
        $xml->addAttribute('pluginid', $element->PluginID);
        $xml->addAttribute('slug', $element->Slug);

        // $xml->addChild('modified_date', $this->DateFactory->toStorageDate($element->ModifiedDate)->toMySQLDate());
        // $xml->addChild('creation_date', $this->DateFactory->toStorageDate($element->CreationDate)->toMySQLDate());

        $aspects = $xml->addChild('aspects');
        $aspectSlugs = (array)$element->AspectSlugs;
        if(!empty($aspectSlugs)){
            sort($aspectSlugs);
            foreach($aspectSlugs as $slug)
                $aspects->addChild('aspect')->addAttribute('slug', $slug);
        }

        if($element->hasStorageFacilityInfo())
        {
            foreach($element->getStorageFacilityInfo() as $sfInfo)
            {
                $sf = $xml->addChild('storage_facility');
                $sf->addAttribute('for', $sfInfo->For);
                $sf->addAttribute('generate_rewrite_rules', $sfInfo->GenerateRewriteRules);
                $sf->addAttribute('ref', $sfInfo->ObjectRef);

                $sfParams = $sfInfo->StorageFacilityParams;
                $sf->addAttribute('ssl', ($sfParams->isSSL()?'true':'false'));
                $sf->addChild('domain', $sfParams->Domain);
                $sf->addChild('base_uri', $sfParams->BaseURI);
                $sf->addChild('base_storage_path', $sfParams->BaseStoragePath);
            }
        }

        return $xml;
    }






    public function xmlToAspect(ModelObject $aspect, SimpleXMLExtended $xml)
    {

        if(($id = $xml->attribute('id')) !== null)
            $aspect->AspectID = intval($id);

        if(($id = $xml->attribute('slug')) !== null)
            $aspect->Slug = strval($id);

        if(($id = $xml->attribute('pluginid')) !== null)
            $aspect->PluginID = strval($id);

        /*
        if ($xml->creation_date)
            $aspect->CreationDate = $this->DateFactory->newStorageDate(strval($xml->creation_date));
        if ($xml->modified_date)
            $aspect->ModifiedDate = $this->DateFactory->newStorageDate(strval($xml->modified_date));
        */

        if (!empty($xml->md5)) {
            $aspect->Md5 = strval($xml->md5);
        } else {
            $aspect->Md5 = '';
        }

        foreach(array('Name', 'Description') as $key)
        {
            $xmlKey = StringUtils::underscore($key);
            $aspect->$key = trim(strval($xml->info->$xmlKey));
        }

        $aspect->ElementMode = trim(strval($xml->info->elementmode));

        $aspect->XMLSchema = '';
        if(!empty($xml->meta_defs))
            $aspect->XMLSchema .= strval($xml->meta_defs->asPrettyXML());
        if(!empty($xml->tag_defs))
            $aspect->XMLSchema .= strval($xml->tag_defs->asPrettyXML());

        return $aspect;
    }

    public function aspectToXML(ModelObject $aspect)
    {
        $xml = new SimpleXMLExtended('<aspect/>');
        $info = $xml->addChild('info');

        foreach(array('Name', 'Description') as $key)
        {
            $info->addChild(StringUtils::underscore($key), $aspect->$key);
        }
        $info->addChild('elementmode', $aspect->ElementMode);

        $xml->addAttribute('id', $aspect->AspectID);
        $xml->addAttribute('slug', $aspect->Slug);
        $xml->addAttribute('pluginid', $aspect->PluginID);
        $xml->addChild('md5', $aspect->Md5);
        // $xml->addChild('modified_date', $this->DateFactory->toStorageDate($aspect->ModifiedDate)->toMySQLDate());
        // $xml->addChild('creation_date', $this->DateFactory->toStorageDate($aspect->CreationDate)->toMySQLDate());

        preg_match('/<meta_defs>(.*?)<\/meta_defs>/s', $aspect->XMLSchema, $metaDefs);
        preg_match('/<tag_defs>(.*?)<\/tag_defs>/s', $aspect->XMLSchema, $tagDefs);

        if(!empty($metaDefs))
            $xml->addXMLElement(new SimpleXMLExtended(current($metaDefs)));

        if(!empty($tagDefs))
            $xml->addXMLElement(new SimpleXMLExtended(current($tagDefs)));

        return $xml;
    }




    public function xmlToCMSNavItem(ModelObject $CMSNavItem, SimpleXMLExtended $xml)
    {


        if(($id = $xml->attribute('id')) !== null)
            $CMSNavItem->CMSNavItemID = intval($id);

        if(($id = $xml->attribute('slug')) !== null)
            $CMSNavItem->Slug = strval($id);

        if(($id = $xml->attribute('pluginid')) !== null)
            $CMSNavItem->PluginID = strval($id);

        if(($id = $xml->attribute('uri')) !== null)
            $CMSNavItem->URI = strval($id);

        if(($id = $xml->attribute('create_add_menu')) !== null)
            $CMSNavItem->DoAddLinksFor = strval($id);

        if(($id = $xml->attribute('enabled')) !== null)
            $CMSNavItem->Enabled = StringUtils::strToBool($id);
        else
            $CMSNavItem->Enabled = true;

        foreach(array('label', 'sort_order', 'permissions', 'parent_slug') as $key)
        {
            $camel = StringUtils::camelize($key);
            $CMSNavItem->$camel = trim($xml->attribute($key));
        }

        if($CMSNavItem->Slug == '')
            $CMSNavItem->Slug = SlugUtils::createSlug($CMSNavItem->Label);

        if (empty($CMSNavItem->SortOrder))
            $CMSNavItem->SortOrder = PHP_INT_MAX;

        $children = array();

        foreach($xml as $childNode)
        {
            if($childNode->getName() == 'item') {

                $child = $this->xmlToCMSNavItem(new CMSNavItem(), $childNode);
                $child->ParentSlug = $CMSNavItem->Slug;
                $children[] = $child;
                $sort_array[] = $child->SortOrder;
                $sort_array2[] = $child->Slug;
            }
        }

        if(!empty($children))
            array_multisort($sort_array, SORT_ASC, $sort_array2, SORT_ASC, $children);

        $CMSNavItem->Children = $children;

//        if($xml->creation_date)
//            $CMSNavItem->CreationDate = $this->DateFactory->newStorageDate(strval($xml->creation_date));
//        if($xml->modified_date)
//            $CMSNavItem->ModifiedDate = $this->DateFactory->newStorageDate(strval($xml->modified_date));

        return $CMSNavItem;
    }

    public function cmsNavItemToXML(ModelObject $CMSNavItem)
    {
        $xml = new SimpleXMLExtended('<item/>');

        $xml->addAttribute('id', $CMSNavItem->CMSNavItemID);
        $xml->addAttribute('pluginid', $CMSNavItem->PluginID);
        $xml->addAttribute('uri', $CMSNavItem->URI);

        foreach(array('slug', 'label', 'sort_order', 'permissions') as $key)
        {
            $camel = StringUtils::camelize($key);
            if(!empty($CMSNavItem->$camel))
                $xml->addAttribute($key, $CMSNavItem->$camel);
        }

        $xml->addAttribute('enabled', ($CMSNavItem->isEnabled()?'true':'false'));

        if(!empty($CMSNavItem->DoAddLinksFor))
            $xml->addAttribute('create_add_menu', $CMSNavItem->DoAddLinksFor);

//        if($CMSNavItem->hasModifiedDate())
//            $xml->addChild('modified_date', $this->DateFactory->toStorageDate($CMSNavItem->ModifiedDate)->toMySQLDate());

//        if($CMSNavItem->hasCreationDate())
//            $xml->addChild('creation_date', $this->DateFactory->toStorageDate($CMSNavItem->CreationDate)->toMySQLDate());

        $sort_array = array();
        $children = $CMSNavItem->getChildren();
        if(!empty($children))
        {
            foreach($children as $child)
            {
                $sort_array[] = $child->SortOrder;
                $sort_array2[] = $child->Slug;
            }

            array_multisort($sort_array, SORT_ASC, $sort_array2, SORT_ASC, $children);

            foreach($children as $child)
                $xml->addXMLElement($this->cmsNavItemToXML($child));
        }

        return $xml;
    }


    public function siteToXML(ModelObject $site)
    {

        $xml = new SimpleXMLExtended('<site/>');

        foreach(array('Name', 'Description') as $key)
        {
            $xml->addChild(StringUtils::underscore($key), $site->$key);
        }

        if($site->SiteID == 1)
            $xml->addAttribute('anchor', 'true');

        $xml->addAttribute('slug', $site->Slug);
        $xml->addChild('domain', $site->PublicDomain);
//        $xml->addChild('domain_base_uri', '');
//        $xml->addChild('domain_aliases', '');
//        $xml->addChild('domain_redirects', '');

        return $xml;
    }


}

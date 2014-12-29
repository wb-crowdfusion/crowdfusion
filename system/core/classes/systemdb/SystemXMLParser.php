<?php
/**
 * SystemXMLParser
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
 * @version     $Id: SystemXMLParser.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SystemXMLParser
 *
 * @package     CrowdFusion
 */
class SystemXMLParser {


    protected $systemXMLFile;
    protected $environmentsXMLFile;
    protected $Events;
    protected $SimpleXMLParser;
    protected $TransactionManager;
    protected $DateFactory;

    protected $backupPath;

    protected $environment;
    protected $isAliasDomain;

    protected $ApplicationContext;
    protected $lockSystemChanges = false;

    public function setSystemXMLFile($systemXMLFile)
    {
        $this->systemXMLFile = $systemXMLFile;
    }

    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    public function setIsAliasDomain($isAliasDomain)
    {
        $this->isAliasDomain = $isAliasDomain;
    }

    public function setLockSystemChanges($lockSystemChanges)
    {
        $this->lockSystemChanges = $lockSystemChanges;
    }

    public function setEnvironmentsXMLFile($environmentsXMLFile)
    {
        $this->environmentsXMLFile = $environmentsXMLFile;
    }

    public function setSimpleXMLParser(SimpleXMLParserInterface $SimpleXMLParser)
    {
        $this->SimpleXMLParser = $SimpleXMLParser;
    }

    public function setSystemXMLConverter(SystemXMLConverter $SystemXMLConverter)
    {
        $this->SystemXMLConverter = $SystemXMLConverter;
    }

    public function setTransactionManager(TransactionManagerInterface $TransactionManager)
    {
        $this->TransactionManager = $TransactionManager;
    }

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    public function setBackupPath($backupPath)
    {
        $this->backupPath = $backupPath;
    }

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    protected $parsed = false;
    protected $changed = false;

    protected $contexts = array();
    protected $cmsNavItems = array();
    protected $plugins = array();
    protected $aspects = array();
    protected $elements = array();
    protected $site = null;
    protected $rewriteBase;
    protected $publicFilesBaseURI;

    protected function resetParsed()
    {
        $this->parsed = false;
    }

    protected function parse()
    {
        if($this->changed)
            return;

        if($this->parsed != true)
        {
            $this->cmsNavItems = array();
            $this->plugins = array();
            $this->aspects = array();
            $this->elements = array();
            $this->site = null;

            $xml = $this->SimpleXMLParser->parseXMLFile($this->environmentsXMLFile);

            foreach($xml as $ename => $enode) {

                switch((string)$ename) {

                    case 'environment':
                        foreach($enode->attributes() as $name => $value)
                            $environment[$name] = (string)$value;

                        if(!empty($environment['slug']) && strtolower($environment['slug']) != strtolower($_SERVER['ENVIRONMENT']))
                            continue;

                        $this->rewriteBase = strval($enode->rewrite_base);
                        $this->publicFilesBaseURI = strval($enode->public_files_base_uri);

                        foreach($enode as $name => $node) {

                            switch((string)$name) {

                                case 'context':
                                    $context = array('sites'=> array());
                                    foreach($node->attributes() as $name => $value)
                                        $context[$name] = (string)$value;

                                    foreach($node as $childNode) {

                                        if($childNode->getName() == 'sites')
                                        {

                                            foreach($childNode as $siteNode) {
                                                $site = array();

                                                foreach($siteNode->attributes() as $name => $value)
                                                    $site[$name] = (string)$value;

                                                // By default if a site is not specified as enabled, it is enabled.
                                                if (!array_key_exists('enabled', $site)) {
                                                    $site['enabled'] = true;
                                                }

                                                // If the context that contains the site is disabled
                                                // The site within it should always be disabled.
                                                if (!StringUtils::strToBool($context['enabled'])) {
                                                    $site['enabled'] = false;
                                                }

                                                foreach($siteNode as $siteChildNode)
                                                {

                                                    if((string)$siteChildNode->getName() == 'storage_facility')
                                                    {
                                                        $sf = array();

                                                        foreach($siteChildNode->attributes() as $name => $value)
                                                            $sf[$name] = (string)$value;

                                                        foreach($siteChildNode as $sfChildNode)
                                                            $sf[(string)$sfChildNode->getName()] = (string)$sfChildNode;

                                                        if(isset($sf['for']))
                                                            $site['storagefacilities'][$sf['for']] = $sf;
                                                    } else {

                                                        $site[(string)$siteChildNode->getName()] = (string)$siteChildNode;

                                                    }

                                                }
                                                $this->siteContext = $context;
                                                $this->site = $site;
                                                break;
                                            }


                                        } else {
                                            if($childNode->getName() == 'storage_facility')
                                            {
                                                $sf = array();

                                                foreach($childNode->attributes() as $name => $value)
                                                    $sf[$name] = (string)$value;

                                                foreach($childNode as $sfChildNode)
                                                    $sf[(string)$sfChildNode->getName()] = (string)$sfChildNode;

                                                if(isset($sf['for']))
                                                    $context['storagefacilities'][$sf['for']] = $sf;
                                            } else {

                                                $context[(string)$childNode->getName()] = (string)$childNode;
                                            }
                                        }

                                    }

                                    if(empty($context['sites']))
                                        unset($context['sites']);

                                    $contextObject = URLUtils::resolveContextFromArray($context, $this->isAliasDomain);

                                    $contextObject->ContextID = count($this->contexts)+1;

                                    $this->contexts[] = $contextObject;
                                    break;

                            }

                        }

                        break;

                }


            }

            $xml = $this->SimpleXMLParser->parseXMLFile($this->systemXMLFile);

            foreach($xml as $name => $node) {
                $array = array();
                $sort_array = array();
                $sort_array2 = array();

                switch((string)$name) {

                    case 'plugins':
                        foreach($node as $pluginNode) {

                            if($pluginNode->getName() == 'plugin')
                            {
                                $item = $this->SystemXMLConverter->xmlToPlugin(new Plugin(), $pluginNode);
                                $sort_array[] = $item->Slug;
                                $array[] = $item;
                            }
                        }

                        array_multisort($sort_array, SORT_ASC, $array);

                        $this->plugins = $array;
                        break;

                    case 'elements':
                        foreach($node as $elementNode) {

                            if($elementNode->getName() == 'element')
                            {
                                $item = $this->SystemXMLConverter->xmlToElement(new Element(), $elementNode);
                                $sort_array[] = $item->Slug;
                                $array[] = $item;
                            }
                        }
                        array_multisort($sort_array, SORT_ASC, $array);

                        $this->elements = $array;
                        break;

                    case 'aspects':
                        foreach($node as $aspectNode) {

                            if($aspectNode->getName() == 'aspect')
                            {
                                $item = $this->SystemXMLConverter->xmlToAspect(new Aspect(), $aspectNode);
                                $sort_array[] = $item->Slug;
                                $array[] = $item;
                            }
                        }
                        array_multisort($sort_array, SORT_ASC, $array);

                        $this->aspects = $array;

                        break;

                    case 'cmsnavitems':
                        foreach($node as $cmsNavNode) {

                            if($cmsNavNode->getName() == 'item')
                            {
                                $item = $this->SystemXMLConverter->xmlToCMSNavItem(new CMSNavItem(), $cmsNavNode);
                                $sort_array[] = $item->SortOrder;
                                $sort_array2[] = $item->Slug;
                                $array[] = $item;
                            }

                        }

                        array_multisort($sort_array, SORT_ASC, $sort_array2, SORT_ASC, $array);

                        $this->cmsNavItems = $array;
                        break;
                }

            }


            $contextArray = $this->siteContext;

            $site2 = array_merge($contextArray, $this->site);

            if(isset($contextArray['storagefacilities']))
                foreach($contextArray['storagefacilities'] as $key => $sf)
                    $site2['storagefacilities'][$key] = $sf;

            if(isset($site['storagefacilities']))
                foreach($site['storagefacilities'] as $key => $sf)
                    $site2['storagefacilities'][$key] = $sf;

            $site2['context'] = $contextArray['slug'];
            unset($site2['sites']);

            $siteArray = $site2;

            $site = URLUtils::resolveSiteFromArray($siteArray, $this->isAliasDomain);

            $site->SiteID = 1;

            $this->sites[$site->Slug] = $site;

            $this->parsed = true;
        }
    }

    public function getRewriteBase()
    {
        $this->parse();
        return $this->rewriteBase;
    }

    public function getPublicFilesBaseURI()
    {
        $this->parse();
        return $this->publicFilesBaseURI;
    }

    public function getContexts()
    {
        $this->parse();
        return $this->contexts;
    }

    public function getSites()
    {
        $this->parse();
        return $this->sites;
    }


    public function getCMSNavItems()
    {
        $this->parse();
        return $this->cmsNavItems;
    }

    public function getPlugins()
    {
        $this->parse();
        return $this->plugins;
    }

    public function getElements()
    {
        $this->parse();
        return $this->elements;
    }

    public function getAspects()
    {
        $this->parse();
        return $this->aspects;
    }


    public function save()
    {
        if($this->lockSystemChanges)
            return;

        if(!$this->changed)
            return;

        $xml = new SimpleXMLExtended('<system/>');

        $test = $xml->addChild('plugins');
        foreach ($this->plugins as $plugin)
            $test->addXMLElement($this->SystemXMLConverter->pluginToXML($plugin));

        $test = $xml->addChild('elements');
        foreach($this->elements as $element)
            $test->addXMLElement($this->SystemXMLConverter->elementToXML($element));

        $test = $xml->addChild('aspects');
        foreach($this->aspects as $aspect)
            $test->addXMLElement($this->SystemXMLConverter->aspectToXML($aspect));

        $test = $xml->addChild('cmsnavitems');
        foreach($this->cmsNavItems as $navItem)
            $test->addXMLElement($this->SystemXMLConverter->cmsNavItemToXML($navItem));

        $newContents = $xml->asPrettyXML();

        $backup = $this->backupPath.'/system.'.$this->DateFactory->newStorageDate()->format('Y_m_d_His').'.'.microtime(true).'.xml';

        FileSystemUtils::safeCopy($this->systemXMLFile, $backup);

        FileSystemUtils::safeFilePutContents($this->systemXMLFile, $newContents);

        $this->ApplicationContext->clearContextFiles();

        $this->changed = false;
        $this->resetParsed();
    }

    protected function triggerChanged()
    {
//        if(!$this->changed)
        $this->changed = true;

        if(!$this->TransactionManager->isTransactionInProgress())
        {
            $this->save();
        }

    }

    public function setCMSNavItems($array)
    {
        if($array == $this->cmsNavItems)
            return;

        $sort_array = array();
        $sort_array2 = array();
        foreach((array)$array as $item)
        {
            $sort_array[] = $item->SortOrder;
            $sort_array2[] = $item->Slug;
        }

        array_multisort($sort_array, SORT_ASC, $sort_array2, SORT_ASC, $array);

        $this->cmsNavItems = $array;

        // trigger system.xml save
        $this->triggerChanged();
    }

    public function setPlugins($array)
    {
        if($array == $this->plugins)
            return;

        $sort_array = array();
        foreach((array)$array as $item)
        {
            $sort_array[] = $item->Slug;
        }

        array_multisort($sort_array, SORT_ASC, $array);

        $this->plugins = $array;

        // trigger system.xml save
        $this->triggerChanged();
    }

    public function setElements($array)
    {
        if($array == $this->elements)
            return;

        $sort_array = array();
        foreach((array)$array as $item)
        {
            $sort_array[] = $item->Slug;
        }

        array_multisort($sort_array, SORT_ASC, $array);

        $this->elements = $array;

        // trigger system.xml save
        $this->triggerChanged();
    }

    public function setAspects($array)
    {
        if($array == $this->aspects)
            return;

        $sort_array = array();
        foreach((array)$array as $item)
        {
            $sort_array[] = $item->Slug;
        }

        array_multisort($sort_array, SORT_ASC, $array);

        $this->aspects = $array;

        // trigger system.xml save
        $this->triggerChanged();
    }


}

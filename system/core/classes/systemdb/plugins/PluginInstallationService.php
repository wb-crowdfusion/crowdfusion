<?php
/**
 * PluginInstallationService
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
 * @version     $Id: PluginInstallationService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PluginInstallationService
 *
 * @package     CrowdFusion
 */
class PluginInstallationService
{
    protected $rootPath           = null;
    protected $configFileLocation = null;
    protected $AspectService      = null;
    protected $PluginService      = null;
    protected $ElementService     = null;
    protected $NodeService        = null;
    protected $ApplicationContext = null;

    protected $lockSystemChanges = false;

    /**
     * [IoC] Injects the ApplicationContext
     *
     * @param ApplicationContext $ApplicationContext ApplicationContext
     *
     * @return void
     */
    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    /**
     * [IoC] Injects the DateFactory
     *
     * @param DateFactory $DateFactory DateFactory
     *
     * @return void
     */
    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    /**
     * [IoC] Injects AspectService
     *
     * @param AspectService $AspectService AspectService
     *
     * @return void
     */
    public function setAspectService(AspectService $AspectService)
    {
        $this->AspectService = $AspectService;
    }

    /**
     * [IoC] Injects PluginService
     *
     * @param PluginService $PluginService PluginService
     *
     * @return void
     */
    public function setPluginService(PluginService $PluginService)
    {
        $this->PluginService = $PluginService;
    }

    /**
     * [IoC] Injects ModelMapper
     *
     * @param ModelMapper $ModelMapper ModelMapper
     *
     * @return void
     */
    public function setModelMapper(ModelMapper $ModelMapper)
    {
        $this->ModelMapper = $ModelMapper;
    }

    /**
     * [IoC] Injects ElementService
     *
     * @param ElementService $ElementService ElementService
     *
     * @return void
     */
    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    /**
     * [IoC] Injects NodeService
     *
     * @param NodeService $NodeService NodeService
     *
     * @return void
     */
    public function setNodeService(NodeService $NodeService)
    {
        $this->NodeService = $NodeService;
    }

    /**
     * [IoC] Injects CMSNavItemService
     *
     * @param CMSNavItemService $CMSNavItemService CMSNavItemService
     *
     * @return void
     */
    public function setCMSNavItemService(CMSNavItemService $CMSNavItemService)
    {
        $this->CMSNavItemService = $CMSNavItemService;
    }

    /**
     * [IoC] Injects Permissions
     *
     * @param Permissions $Permissions Permissions
     *
     * @return void
     */
    public function setPermissions(Permissions $Permissions)
    {
        $this->Permissions = $Permissions;
    }

    /**
     * [IoC] Injects rootPath from the config
     *
     * @param string $rootPath The rootPath for plugins
     *
     * @return void
     */
    public function setRootPath($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * [IoC] Injects ConfigService
     *
     * @param ConfigService $ConfigService ConfigService
     *
     * @return void
     */
    public function setConfigService(ConfigService $ConfigService)
    {
        $this->ConfigService = $ConfigService;
    }

    /**
     * [IoC] Injects SimpleXMLParser
     *
     * @param SimpleXMLParserInterface $SimpleXMLParser SimpleXMLParser
     *
     * @return void
     */
    public function setSimpleXMLParser(SimpleXMLParserInterface $SimpleXMLParser)
    {
        $this->SimpleXMLParser = $SimpleXMLParser;
    }

    protected $TransactionManager;
    /**
     * [IoC] Injects TransactionManager
     *
     * @param TransactionManagerInterface $TransactionManager TransactionManager
     *
     * @return void
     */
    public function setTransactionManager(TransactionManagerInterface $TransactionManager)
    {
        $this->TransactionManager = $TransactionManager;
    }


    public function setSystemXMLConverter(SystemXMLConverter $SystemXMLConverter)
    {
        $this->SystemXMLConverter = $SystemXMLConverter;
    }

    public function setSiteService(SiteService $SiteService)
    {
        $this->SiteService = $SiteService;
    }

    public function setLockSystemChanges($lockSystemChanges)
    {
        $this->lockSystemChanges = $lockSystemChanges;
    }

    protected $NodeSchemaParser;

    public function setNodeSchemaParser(NodeSchemaParser $NodeSchemaParser)
    {
        $this->NodeSchemaParser = $NodeSchemaParser;
    }

    /**
     * Scans the plugin directories and installs plugins as needed
     *
     * @return void
     */
    public function scanInstall()
    {

        if(!$this->lockSystemChanges)
        {
            foreach ($this->ApplicationContext->detectPluginDirectories()->getPluginDirectories() as $pluginDir) {
                if (is_dir($pluginDir)) {
                    $slug = basename($pluginDir);
                    if (!$this->PluginService->slugExists($slug)) {

                        $xml = $this->loadXML($pluginDir);
                        if ($xml) {

                            if(!$this->TransactionManager->isTransactionInProgress())
                                $this->TransactionManager->begin();

                            //INSERT PLUGIN INTO DB

                            $plugin = new Plugin();
                            $this->ModelMapper->defaultsOnModel($plugin);

                            $plugin = $this->SystemXMLConverter->xmlToPlugin($plugin, $xml);

                            $plugin->Slug = $slug;
                            $plugin->Path = $pluginDir;
                            $plugin->Installed = false;

                            try {
                                $this->PluginService->add($plugin);
                            } catch(ValidationException $ve) {
                                throw new Exception('Plugins: '.$ve->getMessage());
                            }
                        }

                    }
                }
            }

    //        $triggerReload = false;

            foreach ($this->PluginService->findAll()->getResults() as $plugin) {
                if(!is_dir($plugin->Path)) {
                    $plugin->PathInvalid = true;
                    if($plugin->isEnabled())
                    {
                        if(!$this->TransactionManager->isTransactionInProgress())
                            $this->TransactionManager->begin();

                        $plugin->Enabled = false;
                        $this->PluginService->edit($plugin);
                    }
                    if(!$plugin->isInstalled()) {
                        if(!$this->TransactionManager->isTransactionInProgress())
                            $this->TransactionManager->begin();

                        $this->PluginService->delete($plugin->Slug);
                    }
                    continue;
                }

                $this->autoupgradePlugin($plugin);
            }

            if($this->TransactionManager->isTransactionInProgress())
                $this->TransactionManager->commit();

        }

        foreach($this->ElementService->findAll()->getResults() as $element)
            $this->NodeService->createDBSchema($element);

//        if ($triggerReload)
//            $this->ApplicationContext->reload();


    }

    /**
     * Processes the plugin's plugin.xml file
     *
     * @param Plugin            $plugin  The Plugin we're processing
     * @param Errors            &$errors An errors object to update on errors
     * @param string            &$log    A log to update with status details
     * @param SimpleXMLExtended $xml     If specified, use this XML
     *
     * @return void
     */
    public function processPluginXML(Plugin $plugin, Errors &$errors, &$log = '', $xml = false)
    {
        if(!$xml)
            $xml = $this->loadXML($plugin->Path);

        $this->processConfig($plugin, $log, $xml);

        $this->processPermissions($plugin, $log, $xml);

        $this->processCMSNavItems($plugin, $log, $xml);

        $this->processElements($plugin, $log, $xml);

    }

    /**
     * Stores all processed XMLs for cache
     *
     * @var array
     */
    protected $xmlCache = array();

    /**
     * Loads the xml from plugin.xml in the {@link $pluginDir}
     *
     * @param string $pluginDir The directory that contains the plugin.xml we want
     *
     * @return SimpleXMLExtended the loaded plugin xml
     */
    protected function loadXML($pluginDir)
    {
        $xml = $pluginDir.'/plugin.xml';

        if (!isset($this->xmlCache[$xml])) {
            if (file_exists($xml)) {
                try
                {
                    $this->xmlCache[$xml] = $this->SimpleXMLParser->parseXMLFile($xml);
                }catch (Exception $e){
                    throw new Exception('Unable to parse plugin.xml file ['.$xml.']');
                }
            } else {
                return false;
            }
        }

        return $this->xmlCache[$xml];
    }

    /**
     * Add all of the Plugin's permissions from the specified XML
     *
     * @param Plugin            $plugin The Plugin to process
     * @param string            &$log   A variable to update with log details
     * @param SimpleXMLExtended $xml    If specified, process this XML, otherwise load the XML from the plugin
     *
     * @return void
     */
    public function processPermissions(Plugin $plugin, &$log = '', $xml = false)
    {
        if(!$xml)
            $xml = $this->loadXML($plugin->Path);

        if(!$xml)
            throw new Exception('Missing plugin.xml file for plugin ['.$plugin->Slug.']');

        // add permissions
        $perms = $xml->permissions;
        if(!empty($perms))
        foreach ( $perms->permission as $perm_xml ) {
            foreach ( array('slug', 'title') as $required_attribute ) {
                if (!$perm_xml->attributes()->$required_attribute)
                    throw new Exception("Required permission attribute '$required_attribute' was not found.");
            }

            if(!$this->Permissions->permissionExists($perm_xml->attributes()->slug))
            {
                try {
                    $this->Permissions->addPermission($plugin, $perm_xml->attributes()->slug, $perm_xml->attributes()->title);
                } catch(ValidationException $ve) {
                    throw new Exception('Permissions: '.$ve->getMessage());
                }
            }


            $log .= "Permission created...\n";
            $log .= "Slug: " . $perm_xml->attributes()->slug . "\n";
            $log .= "Title: " . $perm_xml->attributes()->title . "\n";
            $log .= "\n";
        }
    }

    /**
     * Appends the plugin's config snippet to the config file
     *
     * @param Plugin            $plugin The plugin to process
     * @param string            &$log   A var that holds logging details
     * @param SimpleXMLExtended $xml    If specified, process this XML instead of loading the plugins
     *
     * @return void
     */
    public function processConfig(Plugin $plugin, &$log = '', $xml = false)
    {
        if (!$xml)
            $xml = $this->loadXML($plugin->Path);

        if(!$xml)
            throw new Exception('Missing plugin.xml file for plugin ['.$plugin->Slug.']');

        //process config.php
        if (!empty($xml->config)) {
            $snippet = trim(strval($xml->config));

            if ($this->ConfigService->insertSnippet($plugin, $snippet, $log))
                $log .= "Appended plugin config snippet to config file.\n\n";
        }
    }

    /**
     * Adds all the plugin's aspects
     *
     * @param Plugin $plugin The plugin to analyze
     * @param string &$log   A variable that will get logging detail appended
     *
     * @return void
     */
    public function processAspects(Plugin $plugin, &$log = '')
    {

        $anyChanged = false;
        $processedSlugs = array();

        $pluginPath = $plugin->Path;

        $aspectsPath = $pluginPath.'/aspects';
        if (file_exists($aspectsPath)) {
            $iter = new DirectoryIterator($aspectsPath);

            foreach ($iter as $aspectFile) {

                if ($aspectFile->isFile() && StringUtils::endsWith($aspectFile->getBasename(), '.xml')) {
                    $aspectSlug = $aspectFile->getBasename(".xml");

                    try
                    {

                        $log .= "Processing aspect descriptor: {$aspectFile->getBasename()}\n";

                        $ts = $this->DateFactory->newLocalDate(filemtime($aspectFile->getPathname()));
                        $md5 = md5_file($aspectFile->getPathname());

                        if ($this->AspectService->slugExists($aspectSlug)) {
                            $aspect = $this->AspectService->getBySlug($aspectSlug);

                            if ($aspect->Md5 !== $md5) {

                                $aspectXML = $this->SimpleXMLParser->parseXMLFile($aspectFile->getPathname());

                                if ($aspectXML == false)
                                    throw new Exception('Unable to load aspect XML ['.$aspectFile->getPathname().']');

                                $aspect = $this->SystemXMLConverter->xmlToAspect($aspect, $aspectXML);

                                if ($aspect->PluginID != $plugin->PluginID)
                                    throw new Exception('Aspect installed by another plugin: '.$aspect->Slug);

                                $aspect->ModifiedDate = $ts;
                                $aspect->Md5 = $md5;

                                $log .= "Aspect updated...\n";

                                $this->AspectService->edit($aspect);


                                $anyChanged = true;
                            }
                        } else {

                            $aspectXML = $this->SimpleXMLParser->parseXMLFile($aspectFile->getPathname());

                            if ($aspectXML == false)
                                throw new Exception('Unable to load aspect XML ['.$aspectFile->getPathname().']');

                            $aspect = new Aspect();
                            $this->ModelMapper->defaultsOnModel($aspect);
                            $aspect = $this->SystemXMLConverter->xmlToAspect($aspect, $aspectXML);
                            $aspect->Slug = $aspectSlug;
                            $aspect->PluginID = $plugin->PluginID;

                            $aspect->ModifiedDate = $ts;
                            $aspect->Md5 = $md5;

                            $log .= "Aspect created...\n";

                            $this->AspectService->add($aspect);


                            $anyChanged = true;
                        }

                        // resolve schema
                        if($aspect->getXMLSchema() != "")
                        {

                            $schemaXML = "<?xml version='1.0'?><schema>";
                            $schemaXML .= $aspect->getXMLSchema();
                            $schemaXML .= "</schema>";

                            try {
                                $this->NodeSchemaParser->parse($schemaXML);
                            }catch(Exception $e){
                                throw new SchemaException("Unable to parse schema for aspect [{$aspect->Slug}]:\n ". $e->getMessage());
                            }
                        }

                        $this->AspectService->getBySlug($aspect->Slug);

                        $log .= "ID: {$aspect->AspectID}\n";
                        $log .= "Slug: {$aspect->Slug}\n";
                        $log .= "Name: {$aspect->Name}\n";
                        $log .= "Description: {$aspect->Description}\n";
                        $log .= "Md5: {$aspect->Md5}\n";
                        $log .= "\n";

                    } catch(ValidationException $ve) {
                        throw new Exception('Aspect ['.$aspectSlug.']: '.$ve->getMessage());
                    }

                    $processedSlugs[] = $aspectSlug;

                }


            }
        }


        if($plugin->PluginID == '')
            return $anyChanged;

        $existing = $this->AspectService->findAll(new DTO(array('PluginID' => $plugin->PluginID)))->getResults();
        foreach($existing as $e)
        {
            if(!in_array($e->Slug, $processedSlugs))
                $this->AspectService->delete($e->Slug);
        }

        return $anyChanged;

    }

    /**
     * Add all the CMS nav items from the plugin
     *
     * @param Plugin            $plugin The plugin to analyze
     * @param string            &$log   A string that hold logging detail
     * @param SimpleXMLExtended $xml    If specified, used this instead of the plugin's xml file
     *
     * @return void
     */
    public function processCMSNavItems(Plugin $plugin, &$log = '', $xml = false)
    {
        if (!$xml)
            $xml = $this->loadXML($plugin->Path);

        if(!$xml)
            throw new Exception('Missing plugin.xml file for plugin ['.$plugin->Slug.']');

        $processedSlugs = array();
        // add cmsnavitems
        $navitems = $xml->cmsnavitems;
        if (!empty($navitems)) {
            foreach ( $navitems->item as $xml_item ) {
                $cms_nav_item = new CMSNavItem();
                $this->ModelMapper->defaultsOnModel($cms_nav_item);
                $cms_nav_item = $this->SystemXMLConverter->xmlToCMSNavItem($cms_nav_item, $xml_item);

                if ($this->insertCMSNavItem($cms_nav_item, $plugin)) {
                    $log .= "CMSNavItem created...\n";
                    $log .= "ID: {$cms_nav_item->CMSNavItemID}\n";
                    $log .= "Slug: {$cms_nav_item->Slug}\n";
                    $log .= "Label: {$cms_nav_item->Label}\n";
                    $log .= "URI: {$cms_nav_item->URI}\n";
                    $log .= "\n";
                }

                $processedSlugs[] = $cms_nav_item->Slug;
                foreach($cms_nav_item->getChildren() as $child) {
                    if ($this->insertCMSNavItem($child, $plugin)) {
                        $log .= "CMSNavItem created...\n";
                        $log .= "ID: {$child->CMSNavItemID}\n";
                        $log .= "Slug: {$child->Slug}\n";
                        $log .= "Label: {$child->Label}\n";
                        $log .= "URI: {$child->URI}\n";
                        $log .= "\n";
                    }

                    $processedSlugs[] = $child->Slug;
                }
            }
        }

        if($plugin->PluginID == '')
            return;

        $existing = $this->CMSNavItemService->findAll(new DTO(array('PluginID' => $plugin->PluginID)))->getResults();
        foreach($existing as $e)
        {
            if(!in_array($e->Slug, $processedSlugs))
                $this->CMSNavItemService->delete($e->Slug);
        }

    }

    /**
     * Inserts the specified CMS Nav Item
     *
     * @param CMSNavItem $cms_nav_item The item to insert
     * @param Plugin     $plugin       The plugin that owns the nav item
     *
     * @return boolean true on success
     */
    protected function insertCMSNavItem(CMSNavItem $cms_nav_item, Plugin $plugin)
    {
        $cms_nav_item->PluginID = $plugin->PluginID;

//        if($cms_nav_item->Slug == '')
//            $cms_nav_item->Slug = SlugUtils::createSlug($cms_nav_item->Label);
//
//        if (empty($cms_nav_item->SortOrder))
//            $cms_nav_item->SortOrder = PHP_INT_MAX;

        $cms_nav_item->ModifiedDate = $plugin->ModifiedDate;

        try {

            if (!$this->CMSNavItemService->slugExists($cms_nav_item->Slug)) {
                $this->CMSNavItemService->add($cms_nav_item);
                return true;
            } else {
                $existing_nav_item = $this->CMSNavItemService->getBySlug($cms_nav_item->Slug);
                if($existing_nav_item->PluginID == $cms_nav_item->PluginID)
                {
                    $cms_nav_item->CMSNavItemID = $existing_nav_item->CMSNavItemID;
                    $this->CMSNavItemService->edit($cms_nav_item);
                }
            }

        } catch(ValidationException $ve) {
            throw new Exception('CMSNavItem ['.$cms_nav_item->Slug.']: '.$ve->getMessage());
        }
        return false;
    }

    /**
     * Add all the elements defined by this plugin
     *
     * @param Plugin $plugin The plugin to analyze
     * @param string &$log   A string to hold logging detail
     *
     * @return void
     */
    protected function processElements(Plugin $plugin, &$log = '', $xml = false)
    {

        if (!$xml)
            $xml = $this->loadXML($plugin->Path);

        if(!$xml)
            throw new Exception('Missing plugin.xml file for plugin ['.$plugin->Slug.']');

        $processedSlugs = array();

        // add elements
        $elements = $xml->elements;
        if (!empty($elements)) {
            foreach ( $elements->element as $elementNode ) {

                $element = new Element();
                $this->ModelMapper->defaultsOnModel($element);

                $this->SystemXMLConverter->xmlToElement($element, $elementNode);

                $log .= "Processing element descriptor: {$element->Slug}\n";

                if ($this->ElementService->slugExists($element->Slug)) {
                    $existingElement = $this->ElementService->getBySlug($element->Slug);

                    if ($existingElement->PluginID != $plugin->PluginID)
                    {
                        $log .= 'WARNING: Element already exists for slug: '.$existingElement->Slug."\n";
                        continue;
                    }

                    $element->ElementID = $existingElement->ElementID;
                }

                $element->PluginID = $plugin->PluginID;
                $anchorSite = $this->SiteService->getAnchoredSite();
                if(empty($anchorSite))
                    throw new Exception('Cannot install element without anchor site');
                $element->AnchoredSiteSlug = $anchorSite->getSlug();
                $element->AnchoredSiteSlugOverride = $anchorSite->getSlug();
                $element->AnchoredSite = $anchorSite;

                if ($this->ElementService->slugExists($element->Slug)) {
                    $log .= "Element updated...\n";
                    $this->ElementService->edit($element);
                } else {
                    $log .= "Element created...\n";
                    $this->ElementService->add($element);
                }

                $log .= "ID: {$element->ElementID}\n";
                $log .= "Slug: {$element->Slug}\n";
                $log .= "Name: {$element->Name}\n";
                $log .= "Description: {$element->Description}\n";
                $log .= "\n";

                $log .= "Creating element DB schema...";
                $element = $this->ElementService->getBySlug($element->Slug); //refresh schema
                $this->NodeService->createDBSchema($element);
                $log .= "done.\n\n";
            }
        }
    }

    /**
     * Runs the install script from the plugin
     *
     * @param Plugin $plugin The Plugin to analyze
     * @param string &$log   A log string that can be updated
     *
     * @return void
     */
    public function processInstallScript(Plugin $plugin, &$log)
    {
        $script = $plugin->Path .'/install.php';
        if(file_exists($script)) {
            include_once $script;
        }
    }

    /**
     * Runs the upgrade script for the plugin
     *
     * @param Plugin $plugin The Plugin to analyze
     * @param string &$log   A helpful string that will be appended with details of actions
     *
     * @return void
     */
    public function processUpgradeScript(Plugin $plugin, &$log, $installedVersion)
    {
        $script = $plugin->Path .'/upgrade.php';

        if(file_exists($script))
            include_once $script;

    }

    /**
     * Install the plugin from the specified path
     *
     * @param string $pluginPath The path for the plugin to install
     * @param Errors &$errors    An errors object to update if there are errors
     *
     * @return array 2 items in this array. First, the log, second is a boolean indicating success (true on successful install)
     */
    public function installPlugin($pluginPath, Errors &$errors)
    {
        if(!file_exists($pluginPath))
            throw new Exception('Cannot install plugin, path does not exist: '.$pluginPath);

        if($this->TransactionManager->isTransactionInProgress())
            $this->TransactionManager->commit();

        $this->TransactionManager->begin();

        $log = "";

        $pluginSlug = basename($pluginPath);

        $plugin = $this->PluginService->slugExists($pluginSlug) ? $this->PluginService->getBySlug($pluginSlug) : null;

        if (!empty($plugin) && $plugin->isInstalled()) {
            $log .= "Plugin already installed.";
            return array($log, 'fail');
        }

        $pluginPath = rtrim($pluginPath, '/');

        if (is_null($plugin)) {
            $plugin = new Plugin();
            $this->ModelMapper->defaultsOnModel($plugin);
        }


        $xml = $pluginPath.'/plugin.xml';

        $log .= "Installing plugin: {$pluginSlug}\n";

        if (!file_exists($xml))
            throw new Exception("Plugin descriptor missing. Verify plugin.xml exists and try again.");

        $log .= "Processing plugin descriptor: {$xml}\n";

        $xml = $this->SimpleXMLParser->parseXMLFile($xml);

        $plugin = $this->SystemXMLConverter->xmlToPlugin($plugin, $xml);

        $plugin->Slug = $pluginSlug;
        $plugin->Path = $pluginPath;
        $plugin->Enabled = true;
        $plugin->Installed = true;
        $plugin->ModifiedDate = $this->DateFactory->newStorageDate(filemtime($pluginPath.'/plugin.xml'));
        $plugin->Md5 = md5_file($pluginPath.'/plugin.xml');

        try
        {
            if ($this->PluginService->slugExists($pluginSlug))
                $this->PluginService->edit($plugin);
            else
                $this->PluginService->add($plugin);
        } catch(ValidationException $ve) {
            throw new Exception('Plugin ['.$pluginSlug.']: '.$ve->getMessage());
        }
        $plugin = $this->PluginService->getBySlug($pluginSlug);

        $log .= "Plugin created...\n";
        $log .= "ID: {$plugin->PluginID}\n";
        $log .= "Slug: {$plugin->Slug}\n";
        $log .= "Title: {$plugin->Title}\n";
        $log .= "Description: {$plugin->Description}\n";
        $log .= "Md5: {$plugin->Md5}\n";
        $log .= "\n";

        $this->processAspects($plugin, $log);

        $this->processPluginXML($plugin, $errors, $log, $xml);

        $errors->throwOnError();

        $this->TransactionManager->commit();


        try {

            $constant = "PATH_PLUGIN_".strtoupper(str_replace('-','_',$plugin->Slug));
            if(!defined($constant))
                define($constant, $pluginPath);

            $this->processInstallScript($plugin, $log);

        }catch (Exception $e) {

            $plugin->Enabled = false;
            $plugin->Installed = false;
            $this->PluginService->edit($plugin);

            die($e->getMessage());
        }


        // TODO: need to figure out custom installation screens

        //SUCCESS!

        $this->ApplicationContext->clearContextFiles();

        $log .= "\nSUCCESS!\n\n";
        return array($log, strpos($log, 'WARNING')=== FALSE?'success':'warn');
    }

    /**
     * Reruns the aspect installation to upgrade the plugin if needed
     *
     * @param Plugin $plugin The plugin to upgrade
     *
     * @return void
     */
    public function autoupgradePlugin(Plugin $plugin)
    {

        if(empty($plugin) || !$plugin->isInstalled() || !$plugin->isEnabled())
            return;

        $xml = $this->loadXML($plugin->Path);

        if(empty($xml))
            return;

        if($plugin->Version != ($newversion = strval($xml->info->version))) {
            $plugin->NewVersion = $newversion;
            return;
        }

        if(!$this->TransactionManager->isTransactionInProgress())
            $this->TransactionManager->begin();

        $xmlFile = $plugin->Path.'/plugin.xml';
        $ts = $this->DateFactory->newLocalDate(filemtime($xmlFile));
        $md5 = md5_file($xmlFile);

        $changed = $this->processAspects($plugin, $log);

        if($plugin->Md5 !== $md5)
        {
            $plugin->Md5 = $md5;
            $this->processCMSNavItems($plugin, $log, $xml);
            $this->processElements($plugin, $log, $xml);

            $changed = true;
        }

        // rerun element schemas
        if($changed) {

            $plugin->AutoUpgraded = true;

            if(!$this->TransactionManager->isTransactionInProgress())
                $this->TransactionManager->begin();

//            foreach($this->ElementService->findAll()->getResults() as $element)
//                $this->NodeService->createDBSchema($element);

            $plugin = $this->PluginService->edit($plugin);
        }

    }

    /**
     * Force an upgrade of the specified plugin
     *
     * @param string $pluginSlug The slug for the installed plugin
     * @param string $pluginPath The path to the plugin
     * @param Errors &$errors    An errors object to update on error
     *
     * @return array 2 items in this array. First, the log, second is a boolean indicating success (true on successful install)
     */
    public function upgradePlugin($pluginSlug, $pluginPath, Errors &$errors)
    {
        $log = "";

        $plugin = $this->PluginService->getBySlug($pluginSlug);
        $existingPriority = $plugin->Priority;

        if (empty($plugin) || !$plugin->isInstalled()) {
            $log .= "Plugin is not installed.";
            return array($log, 'fail');
        }

        $log .= "Upgrading plugin [$pluginSlug]...\n";

        $xml = $this->loadXML($plugin->Path);
        $originalVersion = $plugin->Version;

        $plugin = $this->SystemXMLConverter->xmlToPlugin($plugin, $xml);
        $plugin->Priority = $existingPriority;
        $plugin->ModifiedDate = $this->DateFactory->newStorageDate(filemtime($pluginPath.'/plugin.xml'));
        $plugin->Md5 = md5_file($pluginPath.'/plugin.xml');

        $this->PluginService->edit($plugin);

        $plugin = $this->PluginService->getBySlug($pluginSlug);


        $this->processAspects($plugin, $log);

        foreach($this->ElementService->findAll()->getResults() as $element)
            $this->NodeService->createDBSchema($element);

        $this->processPluginXML($plugin, $errors, $log, $xml);


        $errors->throwOnError();

        $this->processUpgradeScript($plugin, $log, $originalVersion);


        $this->ApplicationContext->clearContextFiles();

        return array($log, strpos($log, 'WARNING')=== FALSE?'success':'warn');
    }


    /**
     * Removes the specified plugin
     *
     * @param string  $pluginSlug The slug of the plugin to remove
     * @param Errors  $errors     An errors object to update if things go wrong
     * @param boolean $purge      If true, then uninstall all elements and aspects from the plugin as well
     *
     * @return array 2 items in this array. First, the log, second is a boolean indicating success (true on successful install)
     */
    public function uninstallPlugin($pluginSlug, Errors $errors, $purge = false)
    {

        $log = "";

        $plugin = $this->PluginService->getBySlug($pluginSlug);

        if (empty($plugin) || !$plugin->isInstalled()) {
            $log .= "Plugin not installed.";
            return array($log,'fail');
        }

        $pluginPath = $plugin->getPath();

        $log .= "Uninstalling plugin: {$pluginSlug}\n";

        $plugin->Enabled = false;
        $plugin->Installed = false;

        $this->PluginService->edit($plugin);

        $log .= "Plugin uninstalled...\n";
        $log .= "ID: {$plugin->PluginID}\n";
        $log .= "Slug: {$plugin->Slug}\n";
        $log .= "Title: {$plugin->Title}\n";
        $log .= "Description: {$plugin->Description}\n";
        $log .= "Md5: {$plugin->Md5}\n";
        $log .= "\n";


        if ($purge) {
            $this->uninstallAspects($plugin);
            $this->uninstallCMSNavItems($plugin);
        }

        //SUCCESS!

        $this->ApplicationContext->clearContextFiles();

        $log .= "\nSUCCESS!\n\n";
        return array($log, strpos($log, 'WARNING')=== FALSE?'success':'warn');

    }

    /**
     * Removes all aspects that were installed by this plugin
     *
     * @param Plugin $plugin The plugin with aspects to remove
     *
     * @return void
     */
    protected function uninstallAspects(Plugin $plugin)
    {
        $dto     = new DTO(array('PluginID'=>$plugin->PluginID));
        $aspects = $this->AspectService->findAll($dto)->getResults();

        foreach ($aspects as $aspect) {
            $this->AspectService->delete($aspect->Slug);
        }

    }

    /**
     * Removes all cms nav items that were installed by this plugin
     *
     * @param Plugin $plugin The plugin with cms nav items to remove
     *
     * @return void
     */
    protected function uninstallCMSNavItems(Plugin $plugin)
    {
        $dto = new DTO(array('PluginID' => $plugin->PluginID));
        $navitems = $this->CMSNavItemService->findAll($dto)->getResults();

        rsort($navitems);

        foreach ($navitems as $navitem) {
            $this->CMSNavItemService->delete($navitem->Slug);
        }
    }
}

<?php
/**
 * Dependency Injection application context container
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
 * @package   CrowdFusion
 * @copyright 2009-2010 Crowd Fusion Inc.
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   $Id: ApplicationContext.php 2012 2010-02-17 13:34:44Z ryans $
 */

include('ApplicationContextException.php');
include('ObjectCurrentlyInCreationException.php');
include('ClassLoader.php');
include('Configuration.php');
include('Instantiator.php');
include('ObjectService.php');
include('patterns/AbstractPattern.php');
include('patterns/PrototypePattern.php');
include('patterns/SingletonPattern.php');
include('Events.php');
include('EventException.php');
include('ContextUtils.php');

/**
 * Dependency Injection application context container
 *
 * @package   CrowdFusion
 */
class ApplicationContext {

    private $context = array();

    private $contextFile = null;
    private $changedContext = false;

    private $numInstances = 0;

    private $propertyResources = array();
    private $objects = array();

    private $properties = array();
    private $aliasMap = array();

    private $plugins;
    private $contextResources;
    private $originalContextResources;
    private $pluginDirectories;
    private $enabledPluginDirectories;

    private $lastPriority = 0;
    private $clearedContextFiles = false;

    private $writeCache = true;

    private $cacheDir;
    private $hotDeploy;
    private $oneOffRedeploy = false;
    private $autoloadExtension;
    private $bypassDirectoriesForAutoload;
    private $pluginContextFile;
    private $sharedContextFile;
    private $pluginInstallFile;
    private $bootstrapFile;

    private $systemFile;
    private $environmentsFile;

    private $events = array();
    private $contextEvents = array();

    private $deploymentBase = null;
    private $determineContext = false;

    private $env = 'default';

    public function __construct($contextResources, $config = array(), $postSystemContextResources = array()) {

        $this->contextResources = $contextResources;
        $this->originalContextResources = $contextResources;

        $this->cacheDir = isset($config['cacheDir']) ?
                              $config['cacheDir']
                            : realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'cache'.DIRECTORY_SEPARATOR;
        $this->hotDeploy = isset($config['hotDeploy']) ? $config['hotDeploy']:false;

        $this->autoloadExtension = isset($config['autoloadExtension'])?$config['autoloadExtension']:'.php';
        $this->bypassDirectoriesForAutoload = isset($config['bypassDirectoriesForAutoload'])?$config['bypassDirectoriesForAutoload']:array('.AppleDouble', 'aspects', 'context', 'tests', 'view', 'vendors', 'src');

        $this->pluginContextFile = isset($config['pluginContextFile'])?$config['pluginContextFile']:'context/context.xml';
        $this->sharedContextFile = isset($config['sharedContextFile'])?$config['sharedContextFile']:'context/shared-context.xml';

        $this->bootstrapFile = array_key_exists('bootstrapFile', $config)?$config['bootstrapFile']:'bootstrap.php';
        $this->writeCache = array_key_exists('writeCache', $config)?$config['writeCache']:true;
        $this->systemContextDir = array_key_exists('systemContextDir', $config)?$config['systemContextDir']:'../config/context';

        if(isset($config['determineContext']) && $config['determineContext'] == true)
        {
            $this->determineContext = true;
            if(!isset($config['environmentsFile']))
                throw new ApplicationContextException('Environments file is not configured');

            $this->environmentsFile = $config['environmentsFile'];

            if(!isset($config['systemFile']))
                throw new ApplicationContextException('System file is not configured');

            $this->systemFile = $config['systemFile'];

            $this->loadSystem($config['environmentsFile'], $config['systemFile']);
        } else {
            $this->deploymentBase = $this->cacheDir;
        }

        if(!empty($postSystemContextResources))
            foreach($postSystemContextResources as $contextResource)
                $this->contextResources[] = $contextResource;

        $this->load();
    }

    public function reload() {

        $this->clearContextFiles();

        $this->contextResources = array_merge(array(), $this->originalContextResources);

        $this->objects = array();

        $this->oneOffRedeploy = false;
        $this->lastPriority = 0;
        $this->clearedContextFiles = false;
        $this->plugins = array();
        $this->pluginDirectories = array();
        $this->enabledPluginDirectories = array();
        ClassLoader::setClassNames(array());
        $this->context = array();
        $this->aliasMap = array();
        $this->events = array();

        if($this->determineContext)
            $this->reloadSystem();

        $this->load();
    }

    private $masterCacheFile = null;

    // variables cached in masterCacheFile
    private $uris = null;
    private $masterTimestamp = false;
    private $systemTimestamp = false;
    private $enabledPluginsByID = array();
    private $rewriteBase = '';
    private $anchorSite = null;

    public function reloadSystem()
    {

        $this->uris = null;
        $this->enabledPluginsByID = array();
        $this->rewriteBase = '';
        $this->anchorSite = null;
        $this->deploymentBase = null;

        if(!isset($this->environmentsFile))
            throw new ApplicationContextException('Environments file is not configured');
        if(!isset($this->systemFile))
            throw new ApplicationContextException('System file is not configured');

        $envFile = $this->environmentsFile;
        $sysFile = $this->systemFile;

        $this->loadSystem($envFile, $sysFile);
    }

    public function loadSystem($environmentsFile, $systemFile)
    {

//        $start = microtime(TRUE);

        // determine DESIGN from cookie
        if(!array_key_exists('DESIGN', $_SERVER))
            $_SERVER['DESIGN'] = ((!empty($_COOKIE['DESIGN']) && !array_key_exists('design_switch', $_GET) )?(string)$_COOKIE['DESIGN']:'default');

        // determine DEVICE_VIEW from cookie
        if(!array_key_exists('DEVICE_VIEW', $_SERVER))
            $_SERVER['DEVICE_VIEW'] = ((!empty($_COOKIE['DEVICE_VIEW']) && !array_key_exists('device_view', $_GET) )?(string)$_COOKIE['DEVICE_VIEW']:'main');

        if(!array_key_exists('ENVIRONMENT', $_SERVER))
            $_SERVER['ENVIRONMENT'] = 'default';

        $this->env = $_SERVER['ENVIRONMENT'];

        // read master.xml
        $masterCacheFile = $this->getMasterCacheFile();

        $regen = true;
        if(file_exists($masterCacheFile) && (include($masterCacheFile)) === true)
        {
            if($this->hotDeploy)
            {
                //check timestamp
                $timestamp = filemtime($environmentsFile);
                $timestamp2 = filemtime($systemFile);

                if($this->masterTimestamp == max($timestamp, $timestamp2))
                    $regen = false;
            } else {
                $regen = false;
            }

            $this->setProperty('system.version', $this->systemTimestamp);
        }

        if($regen)
        {

            if(!file_exists($environmentsFile))
                if(file_exists(PATH_BUILD.'/contexts.xml'))
                {
                    // upgrade deprecated contexts.xml
                    $contextsContent = file_get_contents(PATH_BUILD.'/contexts.xml');

                    $contextsContent = str_replace('<contexts>',
        '<environments>
    <environment slug="default">', $contextsContent);

                    $contextsContent = str_replace('</contexts>',
        '    </environment>
</environments>', $contextsContent);

                    $contextsContent = preg_replace("/\<context slug=\"([^\"]+)\"\>/", "<context slug=\"$1\" enabled=\"true\">", $contextsContent);

                    $contextsContent = str_replace("<context slug=\"cli\"", "<context slug=\"cli\" cli=\"true\"", $contextsContent);

                    $contextsContent = str_replace("domain_aliases", "domain_alias", $contextsContent);

                    file_put_contents($environmentsFile, $contextsContent, LOCK_EX);

                    die("Deprecated contexts.xml for environments.xml, please refresh");
                } else {
                    throw new Exception('Missing environments.xml file: '.$environmentsFile);
                }


            if(!file_exists($systemFile))
                throw new Exception('Missing system.xml file: '.$systemFile);



//            error_log('regen');

            $this->uris = null;
            $this->enabledPluginsByID = array();
            $this->rewriteBase = '';
            $this->anchorSite = null;
            $this->deploymentBase = null;

            $this->oneOffRedeploy = true;

            //check timestamp
            $timestamp = filemtime($environmentsFile);
            $timestamp2 = filemtime($systemFile);

            $this->masterTimestamp = max($timestamp, $timestamp2);
            $this->systemTimestamp = $timestamp2;

            $this->setProperty('system.version', $timestamp2);

            $config = array();

            // parse XML file
            $xml = ContextUtils::parseXMLFile($environmentsFile);

            foreach($xml as $ename => $enode) {

                switch((string)$ename) {

                    case 'environment':
                        foreach($enode->attributes() as $name => $value)
                            $environment[$name] = (string)$value;

                        if(!empty($environment['slug']) && strtolower($environment['slug']) != strtolower($_SERVER['ENVIRONMENT']))
                            continue;

                        foreach($enode as $name => $node)
                        {

                            switch((string)$name) {

                                case 'rewrite_base':
                                    $this->rewriteBase = (string)$node;
                                    break;

                                case 'context':
                                    $context = array('sites'=> array());
                                    foreach($node->attributes() as $name => $value)
                                        $context[$name] = (string)$value;

                                    if(empty($context['enabled']) || !$this->strBool($context['enabled']))
                                        break;

                                    foreach($node as $childNode) {

                                        if($childNode->getName() == 'sites')
                                        {

                                            foreach($childNode as $siteNode) {
                                                $site = array();

                                                foreach($siteNode->attributes() as $name => $value)
                                                    $site[$name] = (string)$value;

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
                                                $context['sites'][] = $site;
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

                                    $config['contexts'][] = $context;
                                    break;
                            }
                        }




                        break;


                }
            }


            // parse XML file
            $xml = ContextUtils::parseXMLFile($systemFile);

            foreach($xml as $name => $node) {
                switch((string)$name) {

                    case 'plugins':
                        foreach($node as $pluginNode) {

                            if($pluginNode->getName() == 'plugin') {
                                $plugin = array();

                                foreach($pluginNode->attributes() as $name => $value)
                                    $plugin[$name] = (string)$value;

                                $plugin['path'] = strval($pluginNode->path);
                                $plugin['priority'] = intval($pluginNode->info->priority);

                                $plugin['installed'] = $this->strBool($plugin['installed']);
                                $plugin['enabled'] = $this->strBool($plugin['enabled']);

                                if($plugin['installed'] == true && $plugin['enabled'] == true)
                                    $this->enabledPluginsByID[$plugin['slug']] = $plugin;

                                //$config['plugins'][] = $plugin;
                            }
                        }

                        break;
                }
            }

            if(empty($config['contexts']))
               throw new Exception('No environment found for slug: '.$_SERVER['ENVIRONMENT']);

            $uris = array();
            $sites = array();

            foreach($config['contexts'] as $context2)
            {
                if(isset($context2['sites']))
                {
                    foreach($context2['sites'] as $site)
                    {
                        $site2 = array_merge($context2, $site);

                        if(isset($context2['storagefacilities']))
                            foreach($context2['storagefacilities'] as $key => $sf)
                                $site2['storagefacilities'][$key] = $sf;

                        if(isset($site['storagefacilities']))
                            foreach($site['storagefacilities'] as $key => $sf)
                                $site2['storagefacilities'][$key] = $sf;

                        $site2['context'] = $context2['slug'];
                        unset($site2['sites']);

                        $sites[] = $site2;
                    }
                } else {
                    $clone = array_merge(array(), $context2);
                    $clone['context'] = $clone['slug'];
                    $sites[] = $clone;
                }
            }

            foreach($sites as $site)
            {
                if(!isset($site['domain']))
                    continue;


                if(isset($site['domain_base_uri']))
                {
                    $uri = str_replace(
                                array('%REWRITE_BASE%', '%CONTEXT%'),
                                array($this->rewriteBase, $site['context']),
                                $site['domain_base_uri']);
                     $uri = preg_replace("/\/[\/]+/", "/", $uri);
                } else {
                    $uri = '';
                }


                $site['uri'] = $uri;
                $uri = trim($site['uri'], '/');

                if(!array_key_exists($uri, $uris))
                    $uris[$uri] = array();

                if(isset($site['anchor']) && $this->strBool($site['anchor']) == true)
                    $this->anchorSite = $site;


                $uris[$uri][] = $site;

            }

            // sort uri's by length (longest first) for matching
            uksort($uris, array(__CLASS__, '_urilenCompare'));

            $this->uris = $uris;


            $cacheContents = '<?php
                $this->masterTimestamp = '.var_export($this->masterTimestamp, true).';
                $this->systemTimestamp = '.var_export($this->systemTimestamp, true).';
                $this->rewriteBase = '.var_export($this->rewriteBase, true).';
                $this->uris = '.var_export($this->uris, true).';
                $this->enabledPluginsByID = '.var_export($this->enabledPluginsByID, true).';
                $this->anchorSite = '.var_export($this->anchorSite, true).';';

            $cacheContents .= "\n\n";
            $cacheContents .= "return true;";

            ContextUtils::safeFilePutContents($masterCacheFile, $cacheContents);

        }


        $found = false;

        foreach($this->uris as $uri => $sites)
        {
            if(substr(trim($_SERVER['REQUEST_URI'], '/'), 0, strlen($uri)) == $uri) {

                foreach((array)$sites as $site)
                {

                    if(isset($site['domain_alias']))
                    {
                        $domain_alias = strtolower($site['domain_alias']);
                        if(strtolower($_SERVER['SERVER_NAME']) == $domain_alias)
                        {
                            $site['matched_alias'] = true;
                            $found = true;
                            break 2;
                        }

                    }

                    if(isset($site['server_domain']))
                    {
                        $server_domains = explode(',', strtolower($site['server_domain']));
                        if(in_array(strtolower($_SERVER['SERVER_NAME']), $server_domains))
                        {
                            $found = true;
                            break 2;
                        }
                    }

                    if(isset($site['server_domain_alias']))
                    {
                        $domain_aliases = explode(',', strtolower($site['server_domain_alias']));
                        if(in_array(strtolower($_SERVER['SERVER_NAME']), $domain_aliases))
                        {
                            $site['matched_alias'] = true;
                            $found = true;
                            break 2;
                        }

                    }

                    $domain = $site['domain'];

                    if($domain == $_SERVER['SERVER_NAME'])
                    {
                        $found = true;
                        break 2;
                    }

                    if(isset($site['domain_redirects']))
                    {
                        $domain_redirects = explode(',', strtolower($site['domain_redirects']));
                        if(in_array(strtolower($_SERVER['SERVER_NAME']), $domain_redirects))
                            $this->redirect($domain,'/'.ltrim($site['uri'], '/'), isset($site['ssl']) && $this->strBool($site['ssl']) == true);
                   }

                }

            } else {

                foreach((array)$sites as $site)
                {
                    $domain = $site['domain'];

                    if(isset($site['domain_redirects']))
                    {
                        $domain_redirects = explode(',', strtolower($site['domain_redirects']));
                        if(in_array(strtolower($_SERVER['SERVER_NAME']), $domain_redirects))
                            $this->redirect($domain, '/'.ltrim($site['uri'], '/'), isset($site['ssl']) && $this->strBool($site['ssl']) == true);
                    }
                }
            }

        }


        if (!$found) {
            if (!is_null($this->anchorSite)) {
                $this->redirect($this->anchorSite['domain'],'/'.ltrim($this->anchorSite['uri'], '/'), isset($this->anchorSite['ssl']) && $this->strBool($this->anchorSite['ssl']) == true);
            } else {
                echo 'Site not found: ' . $_SERVER['SERVER_NAME'];
                exit;
            }
        }


        // determine CONTEXT, ROUTER_BASE from SERVER_NAME and REQUEST_URI

        $_SERVER['SYSTEM_VERSION'] = $this->getSystemVersionTimestamp();
        $_SERVER['DOMAIN'] = $site['domain'];
        $_SERVER['CONTEXT'] = $site['context'];
        $_SERVER['ROUTER_BASE'] = $site['uri'];
        $_SERVER['MATCHED_ALIAS'] = !empty($site['matched_alias'])?$site['matched_alias']:false;
        $_SERVER['REWRITE_BASE'] = $this->rewriteBase;

        if(!empty($site['ssl']) && $this->strBool($site['ssl']) == true && (empty($_SERVER["HTTPS"]) && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] != 'https') && (empty($_SERVER['CLI_REQUEST']) || $_SERVER['CLI_REQUEST'] !== true))
            $this->redirect($_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI'], true);

        if(!empty($site['cli']) && $this->strBool($site['cli']) && (empty($_SERVER['CLI_REQUEST']) || $_SERVER['CLI_REQUEST'] !== true))
            $this->redirect($this->anchorSite['domain'],'/'.ltrim($this->anchorSite['uri'], '/'), isset($this->anchorSite['ssl']) && $this->strBool($this->anchorSite['ssl']) == true);


        $this->deploymentBase = $this->_replaceVars($site['deployment_base_path']);

        if(strpos($this->deploymentBase, $this->cacheDir) === FALSE)
            throw new Exception('deployment_base_path must be located in: '.$this->cacheDir);

        $_SERVER['DEPLOYMENT_BASE_PATH'] = $this->deploymentBase;

        array_walk_recursive($site, array($this, '_replaceVars'));
        $_SERVER['SITE'] = $site;

        $this->pluginContextFile = array_key_exists('plugin_context_file', $site)?$site['plugin_context_file']:'context/'.$_SERVER['CONTEXT'].'-context.xml';

        if(is_dir($this->systemContextDir))
            if(file_exists($this->systemContextDir.'/'.$_SERVER['CONTEXT'].'-context.xml'))
                $this->contextResources[] = $this->systemContextDir.'/'.$_SERVER['CONTEXT'].'-context.xml';
            else
                $this->contextResources[] = $this->systemContextDir.'/shared-context.xml';

        $contextFile = array_key_exists('context_file', $site)?$site['context_file']:PATH_BUILD.'/context/'.$_SERVER['CONTEXT'].'-context.xml';
        if(!empty($contextFile))
            $this->contextResources[] = $contextFile;

//        error_log(((microtime(TRUE) - $start)*1000).'ms');
    }

    public function getSystemVersionTimestamp()
    {
        return $this->systemTimestamp;
    }

    protected function strBool($value)
    {
        if (in_array(strtolower($value),array('true', 'on', '+', 'yes', 'y'))) {
            $value = TRUE;
        } elseif (in_array(strtolower($value), array('false', 'off', '-', 'no', 'n'))) {
            $value = FALSE;
        }

        return $value;
    }

    protected function _replaceVars(&$value, $key = null)
    {
        $value = str_replace(
                array('%SYSTEM_VERSION%', '%PATH_BUILD%', '%CONTEXT%', '%REWRITE_BASE%', '%SERVER_NAME%', '%DEVICE_VIEW%', '%DESIGN%', '%DOMAIN%', '%DOMAIN_BASE_URI%', '%DEPLOYMENT_BASE_PATH%'),
                array($_SERVER['SYSTEM_VERSION'], PATH_BUILD, $_SERVER['CONTEXT'], $_SERVER['REWRITE_BASE'], $_SERVER['SERVER_NAME'], $_SERVER['DEVICE_VIEW'], $_SERVER['DESIGN'], $_SERVER['DOMAIN'], $_SERVER['ROUTER_BASE'], isset($_SERVER['DEPLOYMENT_BASE_PATH'])?$_SERVER['DEPLOYMENT_BASE_PATH']:''),
                $value
                );

        if(is_null($key) || stripos($key, 'base') !== FALSE)
            $value = preg_replace("/\/[\/]+/", "/", $value);

        return $value;
    }

    protected function redirect($domain, $uri, $forceSSL = false)
    {
        if($forceSSL)
            $s = 's';
        else
            $s        = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";

        $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos($_SERVER["SERVER_PROTOCOL"], '/')).$s;
        $port     = ($_SERVER["SERVER_PORT"] == "80" || $_SERVER["SERVER_PORT"] == "443") ? "" : (":".$_SERVER["SERVER_PORT"]);

        $url = $protocol."://".$domain.$port.$uri;
        if (empty($_SERVER['SERVER_PROTOCOL'])) {
            header('HTTP/1.0 301 Moved Permanently');
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently');
        }
        header("Location: ".$url);
        header("Connection: close");
        exit;
    }

    protected function getMasterCacheFile()
    {
        if (is_null($this->masterCacheFile)) {
            if (!is_object($this) || is_null($this->cacheDir)) {
                throw new Exception('Directory recursion error on [null, $this is non-object]' . (ini_get('safe_mode') == true?', SAFE MODE restrictions in effect':''));
            }
            $cacheDir = $this->cacheDir;
            ContextUtils::recursiveMkdir($cacheDir);
            $this->masterCacheFile = $cacheDir .DIRECTORY_SEPARATOR.'.master.'.$this->env.'.php';
        }

        return $this->masterCacheFile;
    }

    protected function load()
    {
        $this->loadResources();

        $this->addPropertyFile(PATH_SYSTEM . '/config/sysconfig.php');

        /*
         * first attempt to load a very specific context and environment file.  if that
         * fails, load the required (CONTEXT_config.php file, i.e. web_config.php, cms_config.php)
         */
        $filename = sprintf('%s/%s_config_%s.php', PATH_BUILD, $_SERVER['CONTEXT'], $this->env);
        if (file_exists($filename)) {
            $this->addPropertyFile($filename, false);
        } else {
            $filename = sprintf('%s/%s_config.php', PATH_BUILD, $_SERVER['CONTEXT']);
            $this->addPropertyFile($filename);
        }

        $this->activatePlugins();
    }

    public function isHotDeploy()
    {
       return $this->hotDeploy;
    }

    public function isOneOffRedeploy()
    {
       return $this->oneOffRedeploy;
    }

    public function getContextResources()
    {
        return $this->contextResources;
    }

    public function getPluginDirectories()
    {
        return $this->pluginDirectories;
    }

    public function getEnabledPluginDirectories()
    {
        return $this->enabledPluginDirectories;
    }


    /*
     * CONTEXT METHODS
     */
    public function autowireObject($className, $objectName = '', $pattern = 'Singleton')
    {

        if($objectName == '') $objectName = $className;
        if(!$this->objectAlreadyLoaded($objectName) && !$this->objectExistsInContext($objectName)) {
            $instantiator = new Instantiator(array('id'=>$objectName, 'class'=>$className),$this);
            $this->addObjectContext($instantiator->getNewDefinition());

            $pClass = $pattern.'Pattern';

            $cp = new ObjectService($objectName);
            $cp->setPattern(new $pClass($instantiator));

            $this->addObject($cp);
        }
        return $this->object($objectName);
    }

    // TODO: Remove this method, can't find it used anywhere 2012-12-11
    public function associateObject($obj, $objectName = '') {
        $className = get_class($obj);
        if($objectName == '') $objectName = $className;
        //if(!$this->objectAlreadyLoaded($objectName) && !$this->objectExistsInContext($objectName)) {

            $instantiator = new Instantiator(array('id'=>$objectName, 'class'=>$className),$this);
            $this->addObjectContext($instantiator->getNewDefinition());

            $pClass = 'SingletonPattern';

            $p = new $pClass($instantiator);
            $p->instance =& $obj;

            $cp = new ObjectService($objectName);
            $cp->setPattern($p);
            $this->addObject($cp);
            return $obj;
        //}
        //return $this->object($objectName);
    }


    // object aliases
    public function isAlias($name) {
        return array_key_exists($name, $this->aliasMap);
    }

    public function addAlias($alias, $name) {
        $this->aliasMap[$alias] = $name;
    }

    public function property($name) {
        if(array_key_exists($name, $this->properties))
          return $this->properties[$name];

        return null;
    }

    public function propertyExists($name) {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Returns the properties effective on the container.
     * This is ONLY for debugging!
     *
     * @return array
     */
    public function dumpProperties()
    {
        return $this->properties;
    }

    public function object($name) {
        if ($this->isAlias($name))
            $name = $this->aliasMap[$name];

        if (array_key_exists($name, $this->objects)) {
            $point = $this->objects[$name];
            return $point->instance();
        } else {
            if ($this->objectExistsInContext($name)) {
                $this->loadObjectFromContext($name);
                return $this->object($name);
            } else if (ClassLoader::classExists($name)) {
                return $this->autowireObject($name);
            } else {
                throw new ApplicationContextException('Object not found: '.$name);
            }
        }

    }

    public function objectExists($name) {
        return  $this->objectAlreadyLoaded($name) || $this->objectExistsInContext($name) || ClassLoader::classExists($name);
    }

    public function getNumInstancesCreated() {
        return $this->numInstances;
    }

    /**
     * @param string $filename
     * @param bool $checkIfExists
     * @throws ApplicationContextException
     */
    protected function addPropertyFile($filename, $checkIfExists = true)
    {
        if ($checkIfExists && !file_exists($filename)) {
            throw new ApplicationContextException('Property file does not exist: ' . $filename);
        }

        include $filename;

        $this->propertyResources[] = $filename;

        if (isset($properties) && is_array($properties)) {
            foreach($properties as $name => $value) {
                $this->setProperty($name, $value);
            }
        }
    }

    /**
     * Sets a property on this container.
     *
     * @param $name
     * @param $value
     */
    protected function setProperty($name, $value)
    {
        // adds both camelized key and unchanged key to properties
        $this->properties[str_replace(' ', '', ltrim(ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', 'z' . $name)), 'Z'))] = $value;
        $this->properties[str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $name)))] = $value;
        $this->properties[$name] = $value;
    }

    protected function addObjectContext($context) {
        if(empty($context['id']))
            throw new ApplicationContextException('Cannot add object without id');

        $objectName = $context['id'];
        if(isset($context['aliases']))
            foreach((array)$context['aliases'] as $alias)
                $this->addAlias($alias, $objectName);

        $this->context[$objectName] = $context;

        if(!$this->changedContext)
        {
            $this->changedContext = true;

            //ContextUtils::recursiveMkdir(dirname($this->getContextCacheFile()));
            //if(!is_writable(dirname($this->getContextCacheFile())))
            //    throw new Exception('Unable to write context cache file, check permissions ['.dirname($this->getContextCacheFile()).']');

            register_shutdown_function(array($this, 'writeContextCache'));
        }
    }

    protected function objectExistsInContext($object) {
        return array_key_exists($object, $this->context);
    }

    protected function loadObjectFromContext($name)
    {
        if(!array_key_exists($name, $this->context))
            throw new ApplicationContextException('Cannot load object, not found in context: '.$name);

        $objectContext = $this->context[$name];

        $instantiator = new Instantiator($objectContext, $this);
        $newContext = $instantiator->getNewDefinition();

        if($newContext != $objectContext)
            $this->addObjectContext($newContext);

        $objectName = $objectContext['id'];
        $pattern = array_key_exists('scope', $objectContext)?$objectContext['scope']:'Singleton';
        $pClass = ucfirst($pattern.'Pattern');

        $cp = new ObjectService($objectName);
        $cp->setPattern(new $pClass($instantiator));

        $this->addObject($cp);
    }

    public function getPropertyFiles() {
        return $this->propertyResources;
    }

    public function getObjectNamesInContext() {
        return array_keys($this->context);
    }

    public function getObjectContext()
    {
        return $this->context;
    }

    public function getObjectsByNameMatchEnd($nameEnd)
    {
        $foundServices = array();

        $nl = strlen($nameEnd);

        foreach(array_keys($this->context) as $name)
        {
            $hl = strlen($name);
            if(substr($name, $hl-$nl, $hl) == $nameEnd)
            {
                $foundServices[$name] = $this->object($name);
            }
        }
        return $foundServices;
    }

    protected function addObject(ObjectService $comp) {
        $this->objects[$comp->getName()] = $comp;
    }

    protected function objectAlreadyLoaded($name) {
        return isset ($this->objects[$name])||($this->isAlias($name));
    }

    /*
     * LOADING PLUGINS
     */

    protected function getContextCacheFile()
    {
        if(is_null($this->contextFile))
        {
            $cacheDir = $this->deploymentBase;

            ContextUtils::recursiveMkdir($cacheDir);

            $this->contextFile = $cacheDir .DIRECTORY_SEPARATOR.'.context.'.$this->env.'.php';
        }

        return $this->contextFile;
    }

    public function detectPluginDirectories()
    {
        $this->pluginDirectories = array();
        foreach($this->contextResources as $contextResource) {
            if(is_dir($contextResource) && !file_exists($contextResource.DIRECTORY_SEPARATOR.'plugin.xml')) {
                $objects = new DirectoryIterator($contextResource);
                foreach($objects as $object){
                    if(!$object->isDot() && $object->isDir() && substr($object->getFilename(),0,1) != '.') {
                        $this->pluginDirectories[] = $object->getPathname().DIRECTORY_SEPARATOR;
                    }
                }
            }
        }
        return $this;
    }

    protected function loadResources() {

        $contextFile = $this->getContextCacheFile();

        if(!$this->hotDeploy && !$this->oneOffRedeploy && file_exists($contextFile) && (include($contextFile)) == true) {

        } else {
            $this->oneOffRedeploy = true;

            // ADD PSR-0 Compliant Classes from the APP
            /*
             * For the application itself we'll piggy back on composer's autoload class map if there's a composer.json
             * present.  If not, the old school method.  It is recommended to use composer though, for real mane.
             *
             */
            if (defined('PATH_ROOT') && file_exists(PATH_ROOT . '/composer.json')) {
                if (!file_exists(PATH_ROOT . '/composer.lock') || !file_exists(PATH_ROOT . '/vendor/composer/autoload_classmap.php')) {
                    throw new Exception('You must run "composer install -o" before booting the application.');
                }
                $classMap = include PATH_ROOT . '/vendor/composer/autoload_classmap.php';
                foreach ($classMap as $className => $classPath) {
                    ClassLoader::addClass($className, $classPath);
                }
            } else if (defined('PATH_APP') && is_dir(PATH_APP . DIRECTORY_SEPARATOR . 'src')) {
                ClassLoader::addClassDirectory(PATH_APP . DIRECTORY_SEPARATOR . 'src');
            }

            //add new variable here
            $contextResources = $this->contextResources;
            $configurationLoader = new Configuration();
            foreach($contextResources as $contextResource) {
                if (!is_dir($contextResource)) {

                    if (file_exists($contextResource) && strrchr($contextResource, '.') === '.xml') {
                        $plugin = array();
                        $plugin['enabled'] = true;
                        $plugin['id'] = basename($contextResource);
                        $plugin['context'] = $this->loadContextFile($configurationLoader, $contextResource);
                        if (array_key_exists('priority', $plugin['context']))
                            $plugin['priority'] = $plugin['context']['priority'];
                        else
                            $plugin['priority'] = ++$this->lastPriority;

                        if($plugin['priority'] > $this->lastPriority)
                            $this->lastPriority = $plugin['priority'];

                        $this->plugins[] = $plugin;
                        continue;
                    } else
                        throw new ApplicationContextException('Context resource does not exist: '.$contextResource);
                }

                //LOAD EXPLICIT PLUGIN DIRECTORY (NOT A DIRECTORY OF PLUGIN DIRECTORIES)
                if(file_exists($contextResource.DIRECTORY_SEPARATOR.'plugin.xml')) {
                    //$this->pluginDirectories[] = $contextResource.DIRECTORY_SEPARATOR;
                    $plugin = $this->loadPluginDirectory($configurationLoader, new SplFileInfo($contextResource), $checkStatus = false);
                    if($plugin != null) {
                        $this->plugins[] = $plugin;
                    }
                } else {

                    $objects = new DirectoryIterator($contextResource);
                    foreach($objects as $object){
                        if(!$object->isDot() && $object->isDir() && substr($object->getFilename(),0,1) != '.') {
                            $this->pluginDirectories[] = $object->getPathname().DIRECTORY_SEPARATOR;
                            $plugin = $this->loadPluginDirectory($configurationLoader, new SplFileInfo($object->getPathname()));
                            if($plugin != null) {
                                $this->plugins[] = $plugin;
                            }
                        }
                    }
                }
            }
            usort($this->plugins, array(__CLASS__, '_priorityCompare'));

            foreach($this->plugins as &$plugin) {
                if(!empty($plugin['directory'])) {
                    $this->enabledPluginDirectories[] = $plugin['directory'];

                    $const = $this->getPluginConstant($plugin);
                    if(defined($const) === FALSE)
                        define($const,$plugin['directory']);
                }

                if(!empty($plugin['context'])) {
                    $objectsContext = $plugin['context'];

                    if(isset($objectsContext['objects']))
                    {
                        foreach((array)$objectsContext['objects'] as $object)
                            $this->addObjectContext($object);

                        unset($plugin['context']['objects']);
                    }

                    if(isset($objectsContext['events']))
                    {
                        foreach((array)$objectsContext['events'] as $mode => $events)
                            foreach($events as $event)
                                if(strtolower($mode) == 'unbind')
                                    $this->addUnbindEventContext($event);
                                else
                                    $this->addBindEventContext($event);


                        unset($plugin['context']['events']);
                    }
                }
            }

            ClassLoader::addClass('Events', dirname(__FILE__).'Events.php');
        }

        $this->contextEvents = $this->events;

    }

    public function writeContextCache()
    {
        if($this->clearedContextFiles == true)
            return;

        if($this->writeCache == false)
            return;

        $contextFile = $this->getContextCacheFile();

//        error_log('Wrote context cache');

        $cacheContents = '<?php
            $this->plugins = '.var_export($this->plugins, true).';
            ClassLoader::setClassNames('.var_export(ClassLoader::getClassNames(), true).');
            $this->context = '.var_export($this->context, true).';
            $this->aliasMap = '.var_export($this->aliasMap, true).';
            $this->events = '.var_export($this->contextEvents, true).';
            $this->pluginDirectories = '.var_export($this->pluginDirectories, true).';
            $this->enabledPluginDirectories = '.var_export($this->enabledPluginDirectories, true).';';

        $cacheContents .= "\n\n";

        foreach($this->plugins as $plugin) {
            if(!empty($plugin['directory'])) {
                $const = $this->getPluginConstant($plugin);
                $cacheContents .= "if(defined(\"{$const}\") === FALSE) define(\"{$const}\",\"{$plugin['directory']}\");\n";
            }
        }

        $cacheContents .= "return true;";

        ContextUtils::safeFilePutContents($contextFile, $cacheContents);
    }

    protected function getPluginConstant($plugin) {
        return "PATH_PLUGIN_".strtoupper(str_replace('-','_',$plugin['id']));
    }

    public function clearContextFiles()
    {

        if($this->clearedContextFiles == true)
            return;

        if(file_exists($this->getMasterCacheFile()))
            if(!@unlink($this->getMasterCacheFile()))
                throw new Exception('Unable to remove master cache file: '.$this->getMasterCacheFile());

        $cacheDir = $this->cacheDir;

        ContextUtils::recursiveMkdir($cacheDir);

        // remove .context files
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cacheDir), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $item) {
            if ($item == '.' || $item == '..')
                continue;

            if(!is_dir($item) && $item->getFilename() == '.context.'.$this->env.'.php') {
                if (!@unlink($item->getPathname()))
                    throw new Exception('Unable to remove context file: '.$item);
            }
        }

        $this->clearedContextFiles = true;
    }

    public function getPluginStatus($pluginName)
    {
        if(!array_key_exists($pluginName, $this->enabledPluginsByID))
            return null;

        return $this->enabledPluginsByID[$pluginName];
    }

    protected function activatePlugins()
    {

        foreach($this->plugins as $plugin) {
            if(!isset($plugin['enabled']) || $plugin['enabled'] != true || empty($plugin['context']))
                continue;

            foreach((array)$plugin['context']['propertyFiles'] as $filename)
                $this->addPropertyFile($filename);
        }

        foreach($this->plugins as $plugin) {
            if(!isset($plugin['enabled']) || $plugin['enabled'] != true || empty($plugin['directory']) || !isset($plugin['bootstrapped']))
                continue;

            $plugindir = $plugin['directory'];

            // check for bootstrap.php
            include($plugindir.DIRECTORY_SEPARATOR.$this->bootstrapFile);
        }

    }

    protected function loadPluginDirectory(Configuration $configurationLoader, SplFileInfo $dir, $checkStatus = true) {

        $pluginName = $dir->getBasename();
        $realPath =  $dir->getRealPath();

        if($checkStatus) {
            // read .plugin file for plugin info
            $plugin = $this->getPluginStatus($pluginName);

            if(is_null($plugin) || $plugin['enabled'] !== true)
                return null; // no plugin file, plugin is not installed

            if($plugin['priority'] > $this->lastPriority)
                $this->lastPriority = $plugin['priority'];

        } else {
            $plugin = array("enabled" => true, "priority" => ++$this->lastPriority);
        }

        $plugin = array_merge(array('directory'=> $realPath), $plugin);

        // add classes to classpath
        ClassLoader::addDirectory($realPath, $this->autoloadExtension, $this->bypassDirectoriesForAutoload);

        // ADD PSR-0/PEAR Compliant Classes
        if (file_exists($realPath . DIRECTORY_SEPARATOR . 'autoload_psr-0.php')) {
            $psrDirectories = include $realPath . DIRECTORY_SEPARATOR . 'autoload_psr-0.php';
            foreach ($psrDirectories as $psrDir) {
                ClassLoader::addClassDirectory($realPath . DIRECTORY_SEPARATOR . $psrDir);
            }
        }

        if (file_exists($realPath . DIRECTORY_SEPARATOR . 'autoload_pear.php')) {
            $pearDirectories = include $realPath . DIRECTORY_SEPARATOR . 'autoload_pear.php';
            foreach ($pearDirectories as $pearPrefix => $pearDir) {
                ClassLoader::addClassDirectory($realPath . DIRECTORY_SEPARATOR . $pearDir, $this->autoloadExtension, false, $pearPrefix);
            }
        }

        $plugin['id'] = $pluginName;

        if(!empty($this->bootstrapFile) && file_exists($realPath.DIRECTORY_SEPARATOR.$this->bootstrapFile))
            $plugin['bootstrapped'] = true;

        // locate plugin context
        if(!empty($this->pluginContextFile) && ($contextXML = $realPath.DIRECTORY_SEPARATOR.$this->pluginContextFile) && file_exists($contextXML)) {
            $plugin['context'] = $this->loadContextFile($configurationLoader, $contextXML);
        } else {
            if(file_exists($realPath.DIRECTORY_SEPARATOR.$this->sharedContextFile))
                $plugin['context'] = $this->loadContextFile($configurationLoader, $realPath.DIRECTORY_SEPARATOR.$this->sharedContextFile);
        }

        return $plugin;
    }

    protected function loadContextFile($configurationLoader, $contextXML)
    {
        return $configurationLoader->loadContextFile($contextXML);
    }

    /**
     * Bind a callback function to an event, executed by priority ascending
     *
     * If two callbacks are bound with the same priority, they are executed in
     * the order in which they were bound.
     *
     * For information on callback type, see
     * {@link http://us.php.net/manual/en/language.pseudo-types.php#language.types.callback PHP documentation}
     *
     * @param string   $eventName Unique event name to bind function to
     * @param callback $function  Function to be called
     * @param int      $priority  Integer priority affects execution order
     *
     * @return void
     */
    protected function addBindEventContext($eventContext)
    {
        if(empty($eventContext['name']))
            throw new ApplicationContextException('Cannot add event without name');
        if(empty($eventContext['ref']))
            throw new ApplicationContextException('Cannot add event without ref');
        if(empty($eventContext['method']))
            throw new ApplicationContextException('Cannot add event without method');

        $eventName = $eventContext['name'];
        $eventRef = $eventContext['ref'];
        $eventMethod = $eventContext['method'];
        $passContext = (!empty($eventContext['pass-context']) && strtolower($eventContext['pass-context'])=='true')?true:false;

        $eventPriority = !empty($eventContext['priority'])?$eventContext['priority']:PHP_INT_MAX;

        $this->bindEvent($eventName, $eventRef, $eventMethod, $eventPriority, $passContext);
    }

    protected function addUnbindEventContext($eventContext)
    {
        if(empty($eventContext['name']))
            throw new ApplicationContextException('Cannot add event without name');
        if(empty($eventContext['ref']))
            throw new ApplicationContextException('Cannot add event without ref');
        if(empty($eventContext['method']))
            throw new ApplicationContextException('Cannot add event without method');

        $eventName = $eventContext['name'];
        $eventRef = $eventContext['ref'];
        $eventMethod = $eventContext['method'];

        $this->unbindEvent($eventName, $eventRef, $eventMethod);
    }

    /**
     * Bind a callback function to an event, executed by priority ascending
     *
     * If two callbacks are bound with the same priority, they are executed in
     * the order in which they were bound.
     *
     * For information on callback type, see
     * {@link http://us.php.net/manual/en/language.pseudo-types.php#language.types.callback PHP documentation}
     *
     * @param string   $eventName Unique event name to bind function to
     * @param callback $function  Function to be called
     * @param int      $priority  Integer priority affects execution order
     *
     * @return void
     */
    public function bindEvent($eventName, $objectNameOrObject, $eventMethod, $priority = PHP_INT_MAX, $passContext = false)
    {
        //ensure priority is an integer
        $priority = intval($priority);

        if (!array_key_exists($eventName, $this->events)) {
            $this->events[$eventName] = array();
        }

        if (!array_key_exists($priority, $this->events[$eventName])) {
            $this->events[$eventName][$priority] = array();
            ksort($this->events[$eventName]); //keep the priorities in order
        }

        $this->events[$eventName][$priority][] = array($objectNameOrObject, $eventMethod, is_string($objectNameOrObject)?false:true, $passContext);
    }

    public function unbindEvent($eventName, $objectName, $eventMethod)
    {
        if (array_key_exists($eventName, $this->events)) {
            foreach($this->events[$eventName] as $priority => $events)
                foreach($events as $i => $callback)
                    if($callback[0] == $objectName && $callback[1] == $eventMethod){
                        unset($this->events[$eventName][$priority][$i]);
                        return;
                    }
        }
    }

    public function hasEvent($eventName)
    {
        return array_key_exists($eventName, $this->events);
    }

    public function getEvent($eventName)
    {
        return array_key_exists($eventName, $this->events)?$this->events[$eventName]:false;
    }

    private function _urilenCompare($a, $b) {
        $a = (int)strlen($a);
        $b = (int)strlen($b);

        if ($a == $b) {
            return 0;
        }
        return ($a > $b) ? -1 : 1;
    }

    private function _priorityCompare($a, $b) {
        $a = (int)array_key_exists('priority', $a)?$a['priority']:PHP_INT_MAX;
        $b = (int)array_key_exists('priority', $b)?$b['priority']:PHP_INT_MAX;

        if ($a == $b) {
            return 0;
        }
        return ($a < $b) ? -1 : 1;
    }
}

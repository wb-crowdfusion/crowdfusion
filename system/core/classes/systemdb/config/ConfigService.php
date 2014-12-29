<?php
/**
 * ConfigService
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
 * @version     $Id: ConfigService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Service to store and retrieve Config items
 *
 * @package     CrowdFusion
 */
class ConfigService
{

    protected $configFileLocation;
    protected $VersionService;
    protected $ApplicationContext;

    protected $backupPath;

    /**
     * [IoC] Injects the configFileLocation
     *
     * @param string $configFileLocation The location of the config file
     *
     * @return void
     */
    public function setConfigFileLocation($configFileLocation)
    {
        $this->configFileLocation = $configFileLocation;
    }

    /**
     * [IoC] Injects the VersionService
     *
     * @param VersionService $VersionService VersionService
     *
     * @return void
     */
    public function setVersionService(VersionService $VersionService)
    {
        $this->VersionService = $VersionService;
    }

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

    public function setBackupPath($backupPath)
    {
        $this->backupPath = $backupPath;
    }

    /**
     * Write the specified text to the config file
     *
     * @param Plugin $plugin The plugin that requested the addition
     * @param string $text   The text to add
     * @param string &$log   Holds a log of the changes made
     *
     * @return true
     */
    public function insertSnippet(Plugin $plugin, $text, &$log = '')
    {
        $snippetProperties = $this->readProperties($text);
        $configContents    = file_get_contents($this->configFileLocation);

        if (stripos($configContents, "BEGIN PLUGIN CONFIG: {$plugin->Slug}") === false) {

            /* foreach($snippetProperties as $name => $value) {
                if($this->ApplicationContext->propertyExists($name))
                    $log .= "
                            /////////////////////////////
                            WARNING, plugin configuration property exists in context already: ['.$name.']
                            /////////////////////////////\n\n";
            } */
            $configContents .= "\n\n// BEGIN PLUGIN CONFIG: {$plugin->Slug}\n";
            $configContents .= $text."\n";
            $configContents .= "// END PLUGIN CONFIG: {$plugin->Slug}\n";

            if(!is_writable($this->configFileLocation))
            {
                $log .= "/////////////////////////////
WARNING, UNABLE TO WRITE TO YOUR CONFIG FILE.
PLEASE ADD THE FOLLOWING TO [".$this->configFileLocation."]:\n
".$configContents."\n
/////////////////////////////";

                return false;
            } else {

                FileSystemUtils::safeFilePutContents($this->configFileLocation, $configContents);
            }
        } else {
            foreach($snippetProperties as $name => $value)
                if (!$this->ApplicationContext->propertyExists($name))
                    $log .= "
/////////////////////////////
WARNING, YOU MUST ADD THE NEW CONFIG PROPERTY TO ".$this->configFileLocation.":
\$properties['$name'] = ".$value.";
/////////////////////////////\n\n";

            return false;
        }

        return true;
    }

    /**
     * Parses the properties file.
     *
     * NOTE: Even though the properties file looks like php, and ends in a .php extension,
     * we just parse it out all by hand here.
     *
     * @param string $config The contents of the config file to parse.
     *                       If not specified, it will use the config file from $this->configFileLocation
     * @param string $filter If specified, no config items with this in the name will be returned
     *
     * @return array Returns an array of the config properties.
     */
    public function readProperties($config = null, $filter = null)
    {
        if(is_null($config))
            $config = file_get_contents($this->configFileLocation);

        $props = preg_match_all('/\s*\$properties\[[\'\"]([^\"^\']+)[\'\"]\]\s*=\s*(.*?);[\n\r\t]?/s', $config, $m);

        $config = array();

        foreach ($m[1] as $i => $key) {
            if ($filter == null || stripos($key, $filter) !== false || stripos($m[2][$i], $filter) !== false)
                $config[(string)$key] = (string)$m[2][$i];
        }

        return $config;
    }

    /**
     * Writes the contents to the config file, after first
     * backing up the current config
     *
     * @param string $contents The contents to write to the config file
     *
     * @return void
     */
    public function saveContents($contents)
    {

        $path = pathinfo($this->configFileLocation);

        //BACKUP CURRENT config.php
        $backup = $this->backupPath.'/pluginconfig.'.date('Y_m_d_His').'.php';
        FileSystemUtils::safeCopy($this->configFileLocation, $backup);

        //OVERWRITE config.php WITH POSTED CONTENTS
        FileSystemUtils::safeFilePutContents($this->configFileLocation, $contents);

        $this->VersionService->incrementDeploymentRevision('Saved config.php');

    }

}

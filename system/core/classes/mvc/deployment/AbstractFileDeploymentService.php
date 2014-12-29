<?php
/**
 * AbstractFileDeploymentService
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
 * @version     $Id: AbstractFileDeploymentService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractFileDeploymentService
 *
 * @package     CrowdFusion
 */
/**
 * Crowd Fusion
 *
 * @package CrowdFusion-Services
 * @version $Id: AbstractFileDeploymentService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Abstract File Deployment Service.
 *
 * @package CrowdFusion-Services
 */
abstract class AbstractFileDeploymentService extends AbstractDeploymentService
{

    protected $deployedFiles;

    /**
     * Aggregates the files if necessary.
     *
     * @return void
     */
    public function deploy()
    {
        if (empty($this->context) || empty($this->siteDomain) || empty($this->deviceView) || empty($this->design))
            return;

        $files = null;
        $md5 = null;
        $contents = null;

        $aggregatePath = $this->getAggregatePath();

        $this->Logger->debug("Aggregate Path: {$aggregatePath}");

        if ($this->isDevelopmentMode || $this->isOneOffRedeploy || !$this->deploymentCacheExists($aggregatePath)) {
            $this->Logger->debug("Retrieving file list...");
            list($files,$md5) = $this->getFiles();
        }

        if ($this->deploymentCacheExists($aggregatePath)) {

            //if this is NOT development mode, the only way to force a refresh is to delete the .json file
            if($this->isDevelopmentMode === false && $this->isOneOffRedeploy === false)
                return;

            $this->Logger->debug("Checking cache JSON...");
            $this->getDeployedFiles();

            //if this IS development mode, a refresh is performed everytime the md5's dont' match
            if (($this->isDevelopmentMode === true || $this->isOneOffRedeploy === true) && $md5 === $this->deployedFiles['md5']) {
                //files haven't changed
                return;
            }
        }

        $this->Logger->debug("Rebuilding...");

        //rebuild file
        $this->aggregateFiles($md5, $files, $aggregatePath);
    }

    /**
     * Returns the name of the aggregate file that we'll write our aggregate to.
     *
     * @return string
     */
    protected function getAggregatePath()
    {
        $aggregatePath = $this->getBaseDeployDirectory().'.'.$this->subject.'.json';

        return $aggregatePath;
    }

    /**
     * Combines all the listed files into a single file at {@link $aggregatePath}
     *
     * @param string $md5           The md5 (stored in the aggregate)
     * @param array  $files         A list of files to store
     * @param string $aggregatePath The name of the file to write our aggregate to
     *
     * @return void
     */
    protected function aggregateFiles($md5, array $files, $aggregatePath)
    {
        $file = array("md5"=>$md5,$this->subject=>array());

        try {
            $this->getDeployedFiles();
        }catch(Exception $e){}

        $deployedFiles = array();
        if(!empty($this->deployedFiles[$this->subject]))
            $deployedFiles = $this->deployedFiles[$this->subject];

        foreach ($files as $relpath => $filename) {
            $ts = filemtime($filename);

            $prevFile = null;
            if(isset($deployedFiles[$relpath]))
                $prevFile = $deployedFiles[$relpath];

            if($prevFile == null || $prevFile['ts'] != $ts){
                $this->putFile($relpath, $filename, $ts);
            }

            $file[$this->subject][$relpath] = array('ts' => $ts, 'origin' =>  str_replace($this->rootPath.DIRECTORY_SEPARATOR, '', $filename));
        }

        foreach((array)$deployedFiles as $relpath => $deployedfile)
        {
            if(!array_key_exists($relpath, $file[$this->subject]))
            {
                $this->deleteFile($relpath);
            }
        }

        $this->deployedFiles = $file;

        $this->storeDeploymentCache($aggregatePath, $file);
    }

    protected function deploymentCacheExists($aggregatePath) {
        return file_exists($aggregatePath);
    }

    protected function storeDeploymentCache($aggregatePath, $cacheArray)
    {
        FileSystemUtils::safeFilePutContents($aggregatePath, JSONUtils::encode($cacheArray, true));
    }

    protected function getDeploymentCache($aggregatePath)
    {
        return JSONUtils::decode(file_get_contents($aggregatePath), true);
    }

    public function getOriginTimestamp($relpath)
    {
        $relpath = '/'.ltrim($relpath, '/');

        $files = $this->getDeployedFiles();
        if(isset($files[$relpath]))
            return $files[$relpath]['ts'];

        throw new Exception('Origin file ['.$relpath.'] is missing');
    }

    public function getOriginPath($relpath)
    {
        $relpath = '/'.ltrim($relpath, '/');

        $files = $this->getDeployedFiles();
        if(isset($files[$relpath]))
            return $this->rootPath.DIRECTORY_SEPARATOR.$files[$relpath]['origin'];

        throw new Exception('Origin file ['.$relpath.'] is missing');

    }

    protected function getDeployedFiles()
    {
        if(is_null($this->deployedFiles))
        {
            if (!$this->deploymentCacheExists($this->getAggregatePath()))
                throw new Exception('Deployment cache is missing, Path: ' . $this->getAggregatePath());

            $this->deployedFiles = $this->getDeploymentCache($this->getAggregatePath());
        }

        if(isset($this->deployedFiles[$this->subject]))
            return $this->deployedFiles[$this->subject];

    }

    /**
     * Returns the full filename of an existing deployed file with the name specified.
     *
     * If the file does not exist, it returns null.
     *
     * @param string $name The filename to expand (full path)
     *
     * @return StorageFacilityFile
     */
    public function resolveFile($name)
    {
        $resolvedFilename = $this->getBaseDeployDirectory().$this->subject.'/'.ltrim($name, '/');
        return new StorageFacilityFile('/'.ltrim($name, '/'), $resolvedFilename);
    }

    public function fileExists($relpath)
    {
        return file_exists($this->resolveFile($relpath)->getLocalPath());
    }

    /**
     * Moves the file from the themes to the public app directory
     *
     * NOTE: the following code will overwrite files
     *
     * @param string $relpath  The relative path where the file lives
     * @param string $filename The file to move
     *
     * @return string the full path and filename of the moved file
     */
    public function putFile($relpath, $filename, $ts)
    {
        $basedir = $this->getBaseDeployDirectory().$this->subject.'/';

        //move files from themes to public app directory
        $newfile = $basedir.$relpath;

//        @FileSystemUtils::safeCopy($filename, $newfile);

//        $ts = filemtime($filename);
        if(!file_exists($newfile) || $ts != filemtime($newfile)) {
            @FileSystemUtils::safeCopy($filename, $newfile);
            //@touch($newfile,$ts); //preserve original modified time
        }

        return new StorageFacilityFile('/'.ltrim($relpath, '/'), $newfile);
    }

    public function deleteFile($relpath)
    {
        @unlink($this->getBaseDeployDirectory().$this->subject.'/'.$relpath);
    }

    /**
     * Returns a list of all the files in the theme directory combined with all the asset files of all installed plugins
     *
     * @return array Contains 2 items, the first being an array of all the files, the second is a unique md5 identifier of the fileset
     */
    protected function getFiles()
    {
        if ($this->isSiteDeployment) {
            if ($this->deviceView != 'main' && !is_dir("{$this->viewDirectory}/{$this->context}/{$this->siteDomain}/{$this->deviceView}")) {
                $this->deviceView = 'main';
            }

            if ($this->design != 'default' && !is_dir("{$this->viewDirectory}/{$this->context}/{$this->siteDomain}/{$this->deviceView}/{$this->design}")) {
                $this->design = 'default';
            }
        }

        $list = array();
        if (!empty($this->viewDirectory))
            $list[] = $this->viewDirectory;

        // Build the list from all the plugin directorys, and the theme directory
        $list = array_merge($this->ApplicationContext->getEnabledPluginDirectories(),$list);

        if (count($list) > 0) {
            $files = array();
            foreach ($list as $tp) {
                if (is_dir($tp)) {
                    $paths = $this->getPathsToCheck($tp, $this->subject);
                    foreach ($paths as $basepath) {
                        if(!is_dir($basepath))
                            continue;

                        $cutoffDepth = null;
                        $objects     = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basepath),
                                                                     RecursiveIteratorIterator::SELF_FIRST);

                        foreach ($objects as $name => $object) {
                            if($cutoffDepth !== null && $objects->getDepth() > $cutoffDepth)
                                continue;
                            else
                                $cutoffDepth = null;

                            if ($cutoffDepth === null && $object->isDir() && substr($object->getBasename(), 0, 1) == '.') {
                                $cutoffDepth = $objects->getDepth();
                                continue;
                            }

                            if (!$object->isFile() || substr($object->getBasename(), 0, 1) == '.')
                                continue;

                            $files[substr($name, strlen($basepath))] = $name;
                        }
                    }

                } else {
                    $this->Logger->warn('Directory does not exist: '.$tp);
                }
            }

            if(empty($files))
                return array(array(),null);

            $timestamps = array();
            foreach ($files as $relpath => $filename) {
                $timestamps[] = filemtime($filename);
            }

            $md5 = md5(serialize(array($files,$timestamps/*, $this->VersionService->getSystemVersion(), $this->VersionService->getDeploymentRevision()*/)));

            return array($files, $md5);
        }

        throw new Exception('No active themes or plugins.');
    }
}

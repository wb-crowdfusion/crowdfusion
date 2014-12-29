<?php
/**
 * SubversionTools
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
 * @version     $Id: SubversionTools.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SubversionTools
 *
 * @package     CrowdFusion
 */
class SubversionTools
{

    protected $svnEnabled = false;
    protected $svnUsername = false;
    protected $svnPassword = false;
    protected $svnRepository = false;

    /**
     * @var LoggerInterface
     */
    protected $Logger;
    /**
     * @var DateFactory
     */
    protected $DateFactory;

    /**
     * @var SimpleXMLParser
     */
    protected $SimpleXMLParser;

    public function __construct($svnEnabled, $svnUsername, $svnPassword, LoggerInterface $Logger)
    {
        $this->svnEnabled = $svnEnabled;
        $this->svnUsername = $svnUsername;
        $this->svnPassword = $svnPassword;
        $this->Logger = $Logger;
    }

    public function setSvnRepository($svnRepository)
    {
        $this->svnRepository = rtrim($svnRepository,'/');
    }

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    public function setSimpleXMLParser(SimpleXMLParserInterface $xmlParser)
    {
        $this->SimpleXMLParser = $xmlParser;
    }

    /**
     * Performs an svn info on the provided path.
     * Returns an array of SVN commit data, or false on failure.
     *
     * @param string $path
     * @return array|bool
     */
    public function getInfo($path = '.')
    {
        if(!$this->svnEnabled)
            return false;

        $user = escapeshellarg($this->svnUsername);
        $pw = escapeshellarg($this->svnPassword);

        $creds = !empty($this->svnUsername) && !empty($this->svnPassword) ? "--username {$user} --password {$pw}" : "";

        $version = trim(shell_exec("svn info {$creds} ".PATH_ROOT.'/'.$path));
        $this->Logger->debug($version);
        if(empty($version))
            return false;

        $status = trim(shell_exec("svn log {$creds} -r HEAD ".PATH_ROOT.'/'.$path));
        $this->Logger->debug($status);

        $data = array();
        $lines = explode("\n", $version);
        foreach($lines as $line) {
            $ld = explode(": ",$line,  2);
            if(count($ld) == 2)
            {
                list($name, $value) = $ld;
                $name = str_replace(' ', '', $name);
                if($name == 'LastChangedDate')
                    $value = substr($value, 0, strpos($value,'('));
                $data[$name] = $value;
            }
        }

        if(!empty($status))
        {
            $lines = explode("\n", $status);
            if(count($lines) <= 1)
                return false;

            $line2 = $lines[1];
            $logData = explode(' | ', $line2);


            $crevision = ltrim($logData[0], 'r');
            $cuser = $logData[1];
            $cdate = substr($logData[2], 0, strpos($logData[2],'('));

            $data['LatestRevision'] = $crevision;
            $data['LatestChangedDate'] = $cdate;
            $data['LatestChangedAuthor'] = $cuser;
        }

        return $data;
    }

    /**
     * performs an svn update of the path provided
     *
     * @param string $path
     * @return bool status of update
     */
    public function update($path = '.')
    {

        if(!$this->svnEnabled)
            return false;

        $user = escapeshellarg($this->svnUsername);
        $pw = escapeshellarg($this->svnPassword);

        $creds = !empty($this->svnUsername) && !empty($this->svnPassword) ? "--username {$user} --password {$pw}" : "";

        $cmd = "svn update {$creds} -r HEAD ".PATH_ROOT.'/'.$path;
        $this->Logger->debug($cmd);

        $updateRes = trim(shell_exec($cmd));

        if(empty($updateRes))
            return false;

        $this->Logger->debug($updateRes);

        return true;
    }

     /**
      * Performs an svn copy of a repository path to another svn path in the same repository
      *
      * fromPath and toPath should contain only the most specific portion of the
      * SVN repository URL needed to make the copy.
      *
      * For example, a from path could be 'trunk' and to path 'tags'. Another example
      * would be copying from trunk to a branch, such as:
      * $fromPath: 'trunk'
      * $toPath: 'branches/plugins/crowdfusion-plugin/1.1.0'
      * $commitMessage 'tagging crowdfusion-plugin for development'
      *
      * @param string $fromPath repository path to be copied (i.e. "trunk")
      * @param string $toPath repository path used to store the copy (i.e. "tags/tagname")
      * @param string $commitMessage svn commit message used when creating the copy
      * @throws SubversionToolsException
      * @return string Full SVN repository URL of the new path
      */
    public function copyPath($fromPath, $toPath, $commitMessage)
    {
        $this->_validateSvnConfig();

        $user = escapeshellarg($this->svnUsername);
        $pw = escapeshellarg($this->svnPassword);
        $fromPath = trim($fromPath, '/');
        $toPath = trim($toPath, '/');

        $fromPath = "{$this->svnRepository}/$fromPath/";
        $toPath = "{$this->svnRepository}/{$toPath}/";

        $svn = "svn copy --username {$user} --password {$pw} {$fromPath} {$toPath} --non-interactive -m \"{$commitMessage}\"";

        $this->Logger->debug($svn);

        $revision = trim(shell_exec($svn));

        $this->Logger->debug($revision);

        if(strpos($revision,'Committed revision') === FALSE) {
            throw new SubversionToolsException("Subversion copy failed.");
        }

        return $toPath;
    }

    /**
     * gets a list of log entries from the current svn repo, for the specified path
     * returns the list in a SimpleXMLExtended object, with the hierarchy:
     *
     * logentry /
     *   (attributes: revision)
     *   author
     *   date
     *   paths/
     *    path (prefixed with /parent_pathname/)
     *     (attributes: kind,copyfrom-path,copyfrom-rev,action)
     *   msg
     *
     * @param string $svnPath svn path of list to retrieve (i.e. "tags")
     * @throws SubversionToolsException
     * @return SimpleXMLExtended list of svn log entries and metadata.
     */
    public function getLog($svnPath, $limit)
    {
        $this->_validateSvnConfig();

        $user = escapeshellarg($this->svnUsername);
        $pw = escapeshellarg($this->svnPassword);
        $svnPath = trim($svnPath, '/');

        $svn = "svn log -v --limit {$limit} --xml --username {$user} --password {$pw} {$this->svnRepository}/{$svnPath}";
        $this->Logger->debug($svn);

        $rawPathList = trim(shell_exec($svn));

        $this->Logger->debug($rawPathList);

        try {
            $parsedPathList = $this->SimpleXMLParser->parseXMLString($rawPathList);
        }
        catch(SimpleXMLParserException $e) {
            throw new SubversionToolsException('Requested path not available in repository!');
        }

        return $parsedPathList;
    }
    /**
     * gets a list of paths from the current svn repo
     * returns the list in a SimpleXMLExtended object, with the hierarchy:
     *
     * list /
     *  (attributes: path)
     *  entry /
     *   (attributes: kind)
     *   name
     *   commit /
     *    (attributes: revision)
     *    author
     *    date
     *  entry /
     *  ... etc
     *
     * @deprecated use getLog
     * @param string $svnPath svn path of list to retrieve (i.e. "tags")
     * @throws SubversionToolsException
     * @return SimpleXMLExtended list of svn paths & commit metadata
     */
    public function getPathList($svnPath)
    {
        $this->_validateSvnConfig();

        $user = escapeshellarg($this->svnUsername);
        $pw = escapeshellarg($this->svnPassword);
        $svnPath = trim($svnPath, '/');

        $svn = "svn list --xml --username {$user} --password {$pw} {$this->svnRepository}/{$svnPath}";

        $this->Logger->debug($svn);

        $rawPathList = trim(shell_exec($svn));

        $this->Logger->debug($rawPathList);

        try {
            $parsedPathList = $this->SimpleXMLParser->parseXMLString($rawPathList);
        }
        catch(SimpleXMLParserException $e) {
            throw new SubversionToolsException('Requested path not available in repository!');
        }

        return $parsedPathList;
    }

    /**
     * gets svn info on the provided path from the remote repo
     * returns the info in a SimpleXMLExtended object, with the hierarchy:
     *
     * entry /
     *  (attributes: kind, path, revision)
     *  url
     *  repository /
     *   root
     *   uuid
     *  commit /
     *   (attributes: revision)
     *   author
     *   date
     *
     * @param string $svnPath SVN path to retrieve (i.e. "tags/2011-03-30_00-00-00")
     * @throws SubversionToolsException
     * @return SimpleXMLExtended commit metadata
     */
    public function getPathInfo($svnPath)
    {
        $this->_validateSvnConfig();

        $user = escapeshellarg($this->svnUsername);
        $pw = escapeshellarg($this->svnPassword);
        $svnPath = trim($svnPath, '/');

        $svn = "svn info --xml --username {$user} --password {$pw} {$this->svnRepository}/{$svnPath}";

        $this->Logger->debug($svn);

        $rawPathInfo = trim(shell_exec($svn));

        $this->Logger->debug($rawPathInfo);

        try {
            $parsedPathInfo = $this->SimpleXMLParser->parseXMLString($rawPathInfo);
            if(!isset($parsedPathInfo->entry)) {
                throw new Exception();
            }
        }
        catch(Exception $e) {
            throw new SubversionToolsException('Path not found in repository!');
        }

        return $parsedPathInfo;
    }

    /**
     * validates that all properties needed to work with the svn repository are populated
     *
     * @throws SubversionToolsException
     * @return bool
     */
    protected function _validateSvnConfig()
    {
        if(!$this->svnEnabled)
            throw new SubversionToolsException("Subversion support must be enabled for this action!");

        if(empty($this->svnUsername))
            throw new SubversionToolsException("Property svn.username is required");

        if(empty($this->svnPassword))
            throw new SubversionToolsException("Property svn.password is required");

        if(empty($this->svnRepository))
            throw new SubversionToolsException("Property svn.repository is required");

        return true;
    }
}
<?php
/**
 * PluginDAO
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
 * @version     $Id: PluginDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PluginDAO
 *
 * @package     CrowdFusion
 */
class PluginDAO extends AbstractSystemXMLDAO
{
    protected $rootPath;

    /**
     * Creates the DAO
     */
    public function __construct()
    {
        parent::__construct(new Plugin());
    }

    /**
     * [IoC] Injects the rootPath (from config)
     *
     * @param string $rootPath The root path for plugins
     *
     * @return void
     */
    public function setRootPath($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    protected function persistObject($object)
    {
        $object->setPath(str_replace($this->rootPath.DIRECTORY_SEPARATOR, '', $object->getPath()));
        return $object;
    }

    /**
     * Sets the path and returns the object
     *
     * @param ModelObject $obj The plugin
     *
     * @return ModelObject
     */
    public function postCacheTranslateObject(ModelObject $obj)
    {
        if(strpos($obj->Path, $this->rootPath) === FALSE)
            $obj->setPath($this->rootPath.DIRECTORY_SEPARATOR.$obj->Path);
        return $obj;
    }

}

<?php
/**
 * AbstractSystemXMLDAO
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
 * @version     $Id: AbstractSystemXMLDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractSystemXMLDAO
 *
 * @package     CrowdFusion
 */
class AbstractSystemXMLDAO implements DAOInterface
{

    protected $SystemXMLParser;
    protected $Events;
    protected $SystemCache;
    protected $ModelMapper;

    protected $model;

    protected $idToSlugCache = array();


    /**
     * Creates the DAO around the given ModelObject
     *
     * @param ModelObject $model The ModelObject we'll base this DAO around.
     */
    public function __construct(ModelObject $model)
    {
        $this->model = $model;
    }

    /**
     * [IoC] Inject the Events
     *
     * @param Events $Events Events Object
     *
     * @return void
     */
    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    /**
     * [IoC] Inject the SystemCache
     *
     * @param SystemCacheInterface $SystemCache The SystemCache
     *
     * @return void
     */
    public function setSystemCache(SystemCacheInterface $SystemCache)
    {
        $this->SystemCache = $SystemCache;
    }

    /**
     * [IoC] Inject the ModelMapper
     *
     * @param ModelMapper $ModelMapper The ModelMapper
     *
     * @return void
     */
    public function setModelMapper(ModelMapper $ModelMapper)
    {
        $this->ModelMapper = $ModelMapper;
    }

    public function setSystemXMLParser(SystemXMLParser $SystemXMLParser)
    {
        $this->SystemXMLParser = $SystemXMLParser;
    }

    /**
     * Returns the ModelObject this DAO has been built around
     *
     * @return ModelObject
     */
    public function getModel()
    {
        return $this->model;
    }

    protected $loaded = false;

    protected $slugsByPluginID = array();
    protected $slugsByID = array();
    protected $objectsBySlug = array();

    protected $maxID = 0;

    protected function loadSource()
    {
        if(!$this->loaded)
        {
            $this->objectsBySlug = array();
            $this->slugsByID = array();
            $this->slugsByPluginID = array();
            $this->maxID = 0;

            $meth = 'get'.ucfirst($this->getModel()->getTableName());
            $objects = $this->SystemXMLParser->$meth();

            foreach($objects as $object)
            {
                $this->loadObject(clone $object);
            }

            ksort($this->objectsBySlug);
            $this->loaded = true;
        }

        return $this;
    }

    protected function loadObject($object)
    {
        if(!isset($this->slugsByPluginID[$object->PluginID]))
            $this->slugsByPluginID[$object->PluginID] = array();

        $this->slugsByPluginID[$object->PluginID][] = $object->Slug;

        if(array_key_exists($object->{$this->getModel()->getPrimaryKey()}, $this->slugsByID))
            throw new Exception('ID conflict for '.get_class($this->getModel()).' ['.($object->{$this->getModel()->getPrimaryKey()}).'], slug is ['.$object->Slug.']');

        $this->slugsByID[$object->{$this->getModel()->getPrimaryKey()}] = $object->Slug;

        $this->objectsBySlug[$object->Slug] = $object;

        if($object->{$this->getModel()->getPrimaryKey()} > $this->maxID)
            $this->maxID = $object->{$this->getModel()->getPrimaryKey()};

    }


    protected function persistSource()
    {
        $objs = array();
        foreach($this->objectsBySlug as $obj)
        {
            $obj = $this->persistObject( clone $obj);
            if(!empty($obj))
                $objs[] = $obj;
        }

        $meth = 'set'.ucfirst($this->getModel()->getTableName());
        $this->SystemXMLParser->$meth($objs);

        $this->loaded = false;
    }

    protected function persistObject($object)
    {
        return $object;
    }

    public function add(ModelObject $obj)
    {
        $this->Events->trigger(get_class($this).'.add.pre', $this, $obj);
        $this->Events->trigger('AbstractSystemXMLDAO.add.pre', $this, $obj);

        $this->loadSource();
        $this->maxID = ($this->maxID + 1);

        $obj->{$this->getModel()->getPrimaryKey()} = $this->maxID;

        $this->objectsBySlug[$obj->Slug] = $obj;
        $this->slugsByID[$obj->{$this->getModel()->getPrimaryKey()}] = $obj->Slug;

        if(!isset($this->slugsByPluginID[$obj->PluginID]))
            $this->slugsByPluginID[$obj->PluginID] = array();

        $this->slugsByPluginID[$obj->PluginID][] = $obj->Slug;

        $this->persistSource();

        $this->Events->trigger(get_class($this).'.add.post', $this, $obj);
        $this->Events->trigger('AbstractSystemXMLDAO.add.post', $this, $obj);

        return $obj;
    }

    public function edit(ModelObject $obj)
    {
        $this->Events->trigger(get_class($this).'.edit.pre', $this, $obj);
        $this->Events->trigger('AbstractSystemXMLDAO.edit.pre', $this, $obj);

        $this->loadSource();

        $this->objectsBySlug[$obj->Slug] = $obj;
        $this->slugsByID[$obj->{$this->getModel()->getPrimaryKey()}] = $obj->Slug;

        if(!isset($this->slugsByPluginID[$obj->PluginID]))
            $this->slugsByPluginID[$obj->PluginID] = array();

        $this->slugsByPluginID[$obj->PluginID][] = $obj->Slug;
        $this->slugsByPluginID[$obj->PluginID] = array_unique($this->slugsByPluginID[$obj->PluginID]);

        $this->persistSource();

        $this->Events->trigger(get_class($this).'.edit.post', $this, $obj);
        $this->Events->trigger('AbstractSystemXMLDAO.edit.post', $this, $obj);

        return $obj;
    }



    /**
     * Removes the aspect and all the aspect relations
     *
     * @param string $slug The slug of the aspect to remove
     *
     * @return void
     */
    public function delete($slug)
    {
        if(is_object($slug) && $slug instanceof ModelObject)
            $slug = $slug->Slug;

        $this->Events->trigger(get_class($this).'.delete.pre', $this, $slug);
        $this->Events->trigger('AbstractSystemXMLDAO.delete.pre', $this, $slug);

        $this->loadSource();

        if(isset($this->objectsBySlug[$slug]))
        {
            $obj = $this->objectsBySlug[$slug];

            unset($this->objectsBySlug[$slug]);
            unset($this->slugsByID[$obj->AspectID]);

            $pKey = array_search($slug, $this->slugsByPluginID[$obj->PluginID]);
            unset($this->slugsByPluginID[$pKey]);

        }

        $this->persistSource();

        $this->Events->trigger(get_class($this).'.delete.post', $this, $slug);
        $this->Events->trigger('AbstractSystemXMLDAO.delete.post', $this, $slug);

        return true;
    }


    /**
     * Determines if a ModelObject with the specified {@link $slug} exists
     *
     * @param string  $slug      The slug of the ModelObject to lookup
     * @param integer $excludeID If specified, then the instance with the id specified will not be considered in the search.
     *
     * @return boolean
     */
    public function slugExists($slug, $excludeID = false)
    {
        $allwithslug = $this->multiGetBySlug((array)$slug);

        if (empty($allwithslug))
            return false;

        foreach ($allwithslug as $row) {
            if ($excludeID !== false && $row[$this->getModel()->getPrimaryKey()] == $excludeID)
                continue;

            return true;
        }

        return false;
    }


    /**
     * Returns the ModelObject containing the specified ID
     *
     * @param integer $id The ID of the ModelObject to locate
     *
     * @return ModelObject
     * @throws Exception if the Object doesn't exist
     */
    public function getByID($id)
    {
        if(array_key_exists($id, $this->idToSlugCache))
            $slug = $this->idToSlugCache[$id];
        else
            $slug = $this->SystemCache->get($this->getModel()->getTableName().':id:'.$id);

        if (!empty($slug)) {
            return $this->getBySlug($slug);
        }

        $this->loadSource();

        if(!isset($this->slugsByID[$id]))
            return null;

        $slug = $this->slugsByID[$id];

        return $this->getBySlug($slug);
    }

    /**
     * Retrieves the ModelObject with the slug specified by {@link $slug}
     *
     * @param string $slug The slug of the object to fetch
     *
     * @return ModelObject
     * @throws Exception if the object cannot be found
     */
    public function getBySlug($slug)
    {
        if (empty($slug))
            throw new Exception("Cannot retrieve object [".get_class($this->getModel())."] without slug");

        $results = $this->multiGetBySlug((array)$slug);
        if (isset($results[$slug]))
            return $results[$slug];

        throw new Exception(get_class($this->getModel()).' not found for slug: '.$slug);

    }

    /**
     * Returns an array of matching objects containing the slugs specified by {@link $slugs}
     *
     * @param array $slugs An array of slugs to match
     *
     * @return ModelObject[] Filled with one or more matching objects, with slugs as keys.
     *                       If an object is not found, the key will not be in the returned array
     */
    public function multiGetBySlug(array $slugs)
    {

        $class = get_class($this->getModel());

        $cacheKeys = array();

        $results = array();

        $cacheKeys = array();
        foreach ($slugs as $slug) {
            $cacheKeys[] = $this->getModel()->getTableName().':'.$slug;
        }

        $rows = $this->SystemCache->multiGet($cacheKeys);

        if (!empty($rows))
            foreach($rows as $row) {
                $row = $this->postCacheTranslateObject($row);
                $this->idToSlugCache[$row[$this->getModel()->getPrimaryKey()]] = $row['Slug'];
                $results[$row['Slug']] = $row;
            }

        $remainingSlugs = array_diff($slugs, array_keys($results));

        if (!empty($remainingSlugs)) {

            $this->loadSource();

            foreach($remainingSlugs as $slug)
            {
                if(!isset($this->objectsBySlug[$slug]))
                    continue;

                $row = $this->objectsBySlug[$slug];

                //$model = new $class();
                //$row = $this->ModelMapper->persistentArrayToModel($row, $model);
                $row = $this->preCacheTranslateObject($row);
                if(empty($row))
                    continue;

                $this->SystemCache->put($this->getModel()->getTableName().':'.$slug, $row, 0);
                $this->SystemCache->put($this->getModel()->getTableName().':id:'.$row[$this->getModel()->getPrimaryKey()], $slug, 0);
                $this->idToSlugCache[$row[$this->getModel()->getPrimaryKey()]] = $slug;

                $row = $this->postCacheTranslateObject($row);

                $results[$slug] = $row;
            }

        }

        return $results;
    }



    /**
     * Finds Aspects.
     *
     * @param DTO $dto DTO with supported parameters:
     *                      <PrimaryKey>
     *                      Slug
     *                      PluginID
     *                      StartDate    string ModifiedDate is greater than this value
     *                      EndDate      string ModifiedDate is less than this value
     *                      Search       string Matches part of Name, Slug, or Description
     *
     * @return DTO A filled DTO
     */
    public function findAll(DTO $dto)
    {
        $sd = get_class($this).(string)serialize($dto);
        $slugs = $this->SystemCache->get($sd);

        if ($slugs === false) {

            $this->loadSource();

            $slugs = array();
            $sort_array = array();

            $dir = 'ASC';

            $orderbys = $dto->getOrderBys();
            if(!empty($orderbys))
                foreach($orderbys as $col => $dir) {}
            else
                $col = 'Slug';

            foreach($this->objectsBySlug as $slug => $obj)
            {

                if(($val = $dto->getParameter('Slug')) != null)
                {
                    if($obj->Slug != $val)
                        continue;
                }

                if(($val = $dto->getParameter('PluginID')) != null)
                {
                    if($obj->PluginID != $val)
                        continue;
                }

                $slugs[] = $slug;

                $sort_array[] = $obj[$col];
            }

            array_multisort($sort_array, strtolower($dir)=='asc'?SORT_ASC:SORT_DESC, SORT_REGULAR, $slugs);
            $this->SystemCache->put($sd, $slugs, 0);
        }


        // retrieve objects
        $rows = $this->multiGetBySlug($slugs);

        $results = array();
        foreach ($slugs as $slug) {
            if(isset($rows[$slug]))
               $results[] = $rows[$slug];
        }

        return $dto->setResults($results);
    }

    /**
     * Supplements database object with derived values
     *
     * @param ModelObject $obj The object to return
     *
     * @return ModelObject $obj
     */
    public function preCacheTranslateObject(ModelObject $obj)
    {
        return $obj;
    }

    /**
     * Supplements cached object with derived values
     *
     * @param ModelObject $obj The object to return
     *
     * @return ModelObject $obj
     */
    public function postCacheTranslateObject(ModelObject $obj)
    {
        return $obj;
    }


}
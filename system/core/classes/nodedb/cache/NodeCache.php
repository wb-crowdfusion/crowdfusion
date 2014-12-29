<?php
/**
 * NodeCache
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
 * @version     $Id: NodeCache.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeCache: Stores nodes to cache
 *
 * @package     CrowdFusion
 */
class NodeCache extends AbstractCache implements NodeCacheInterface
{

    protected $VersionService;
    protected $duration = 0;

    /**
     * Creates the NodeStore object
     *
     * @param CacheStoreInterface $NodeCacheStore The CacheStore to use to store cached nodes
     */
    public function __construct(CacheStoreInterface $PrimaryCacheStore, $nodeCacheKeepLocal = true)
    {
        parent::__construct($PrimaryCacheStore, 'nc', $nodeCacheKeepLocal);
    }

    /**
     * Injects the VersionService
     *
     * @param VersionService $VersionService The VersionService to inject
     *
     * @return void
     */
    public function setVersionService($VersionService)
    {
        $this->VersionService = $VersionService;
    }

    protected function cacheKey($key)
    {
        $key = parent::cacheKey($key);
        return "{$key}-{$this->VersionService->getSystemVersion()}";
    }

    protected $keysToDelete = array();

    public function onCommit()
    {
        if(!empty($this->keysToDelete))
        {
            $this->keysToDelete = array_unique($this->keysToDelete);
            foreach($this->keysToDelete as $key)
            {
                $this->delete($key);
            }
            $this->keysToDelete = array();
        }
    }

    /**
     * Returns the nodes specified by {@link $nodeRef} and {@link $slugs} from cache
     *
     * @param NodeRef $nodeRef The nodeRef that we'll used to find our stored nodes. Should not contain a Slug
     * @param array   $slugs   An array of slugs that when combined with the NodeRef specifies the nodes to get
     *
     * @return array An array containing 2 items. First, an array of cachedSlugs and second an array of cachedRows.
     */
    public function getNodes(NodeRef $nodeRef, $slugs, $localOnly = false)
    {
        $cacheKeys   = array();
        $nodeRefPart = $nodeRef->getElement()->getSlug();

        foreach ((array)$slugs as $slug) {
            $cacheKeys[] = 'node-'.$nodeRefPart.':'.$slug;
        }

        if (!empty($cacheKeys)) {
            if (($cachedRows = $this->multiGet($cacheKeys, $localOnly)) && !empty($cachedRows)) {
                $cachedSlugs = ArrayUtils::arrayMultiColumn($cachedRows, 'Slug');

                return array($cachedSlugs, $cachedRows);
            }
        }

        return array(array(), array());

    }

    /**
     * Stores a node to cache
     *
     * @param NodeRef $nodeRef The NodeRef of the node that's stored
     * @param array   $row     An array containing our Node data
     *
     * @return void
     */
    public function putNode(NodeRef $nodeRef, $row, $localOnly = false)
    {
        $this->put('node-'.$nodeRef->getElement()->getSlug().':'.$row['Slug'], $row, $this->duration, $localOnly);
    }

    /**
     * Deletes the node specified by NodeRef from cache
     *
     * @param NodeRef $nodeRef The NodeRef of the node to remove
     *
     * @return void
     */
    public function deleteNode(NodeRef $nodeRef, $localOnly = false)
    {
        $this->delete('node-'.$nodeRef->getRefURL(), $localOnly);

        $this->keysToDelete[] = 'node-'.$nodeRef->getRefURL();
    }

    /**
     * Retrieves the specified meta data from cache
     *
     * @param string $datatype The datatype of the meta data we'll retrieve
     * @param array  $ids      An array like ['favorite-color' => 51, 'meta-id' => 52, $meta_id => $id, ...]
     *
     * @return array An array like [array $cachedIds, array $rows]
     */
    public function getMeta($datatype, array $ids, $localOnly = false)
    {
        //$datatype = '';
        $cacheKeys = array();

        // retrieve from cache
        $rows = array();
        $cachedIds = array();

        foreach ($ids as $nodeStr => $id) {
            $cacheKeys[] = 'node-meta-'.$nodeStr;
        }

        if (($cachedRows = $this->multiGet($cacheKeys, $localOnly)) && !empty($cachedRows)) {
            foreach ($cachedRows as $cachedRow) {
                $cachedIds[] = $cachedRow['ID'];
                unset($cachedRow['ID']);
                $rows = array_merge($rows, $cachedRow);
            }
        }

        return array($cachedIds, $rows);
    }

    /**
     * Stores meta data to cache
     *
     * @param string $datatype      The datatype of the meta data
     * @param array  $ids           An array containing $nodeStr's as keys and $id's as values
     * @param array  $dbRowsToCache An array of $id's in $ids that should be cached
     *
     * @return void
     */
    public function putMeta($datatype, array $ids, array $dbRowsToCache, $localOnly = false)
    {
        //$datatype = '';

        foreach ($ids as $nodeStr => $id) {
            $toCacheRows = array_key_exists($id, $dbRowsToCache) ? $dbRowsToCache[$id] : array();
            $toCacheRows['ID'] = $id;
            $this->put('node-meta-'.$nodeStr, $toCacheRows, $this->duration, $localOnly);
            unset($toCacheRows);
            unset($id);
            unset($nodeStr);
        }
        unset($ids);
        unset($dbRowsToCache);
    }

    /**
     * Removes the specified meta item from cache
     *
     * @param string  $datatype The datatype of the meta item to remove
     * @param NodeRef $nodeRef  The nodeRef for the node that we will remove meta data for
     *
     * @return void
     */
    public function deleteMeta($datatype, NodeRef $nodeRef, $localOnly = false)
    {
        //$datatype = '';
        $this->delete('node-meta-'.$nodeRef->getRefURL(), $localOnly);
        $this->keysToDelete[] = 'node-meta-'.$nodeRef->getRefURL();

    }

    /**
     * Retrieves the specified tags from cache
     *
     * @param string $direction The cached tag direction. Should be 'in' or 'out'
     * @param array  $ids       An array containing $nodeStr's as keys and $id's as values
     *
     * @return array An array like [array $cachedIds, array $rows]
     */
    public function getTags($direction, array $ids, $localOnly = false)
    {
        $cacheKeys = array();

        // retrieve from cache
        $rows = array();
        $cachedIds = array();

        foreach ($ids as $nodeStr => $id) {
            $cacheKeys[] = 'node-'.$direction.'-tags-'.$nodeStr;
        }

        if (($cachedRows = $this->multiGet($cacheKeys, $localOnly)) && !empty($cachedRows)) {
            foreach ($cachedRows as $cachedRow) {
                $cachedIds[] = $cachedRow['ID'];
                unset($cachedRow['ID']);
                $rows= array_merge($rows, $cachedRow);
            }
        }

        return array($cachedIds, $rows);
    }

    /**
     * Stores the specified tags to cache
     *
     * @param string $direction     The direction of the tags we're caching. Should be 'in' or 'out'.
     * @param array  $ids           An array containing $nodeStr's as keys and $id's as values. This is a list of all tags.
     * @param array  $dbRowsToCache An array of $id's in $ids that we want to store to cache.
     *
     * @return void
     */
    public function putTags($direction, array $ids, array $dbRowsToCache, $localOnly = false)
    {
        foreach ($ids as $nodeStr => $id) {
            $toCacheRows = array_key_exists($id, $dbRowsToCache)?$dbRowsToCache[$id]:array();
            $toCacheRows['ID'] = $id;
            $this->put('node-'.$direction.'-tags-'.$nodeStr, $toCacheRows, $this->duration, $localOnly);
            unset($toCacheRows);
            unset($id);
            unset($nodeStr);
        }
        unset($ids);
        unset($dbRowsToCache);
    }

    /**
     * Removes the cached tag data for the nodeRefs specified
     *
     * @param string  $direction The direction of the tags on $nodeRef to clear
     * @param NodeRef $nodeRef   The nodeRef containing tags in {@link $direction} that we want to clear from cache
     * @param NodeRef $nodeRef2  The corresponding opposite-direction NodeRef that we need to clear tags from
     *
     * @return void
     */
    public function deleteTags($direction, NodeRef $nodeRef,  NodeRef $nodeRef2, $localOnly = false)
    {
        $oppositeTagDirection = $direction == 'out' ? 'in' : 'out';
        $this->delete('node-'.$direction.'-tags-'.$nodeRef->getRefURL(), $localOnly);
        $this->delete('node-'.$oppositeTagDirection.'-tags-'.$nodeRef2->getRefURL(), $localOnly);

        $this->keysToDelete[] = 'node-'.$direction.'-tags-'.$nodeRef->getRefURL();
        $this->keysToDelete[] = 'node-'.$oppositeTagDirection.'-tags-'.$nodeRef2->getRefURL();
    }


    /**
     * Retrieves the specified sections from cache
     *
     * @param array $ids An array containing section-type's as keys and section-id's as values.
     *
     * @return array An array like [array $cachedIds, array $rows]
     */
//    public function getSections(array $ids)
//    {
//        $cacheKeys = array();
//
//        // retrieve from cache
//        $rows = array();
//        $cachedIds = array();
//
//        foreach ($ids as $nodeStr => $id) {
//            $cacheKeys[] = 'node-sections-'.$nodeStr;
//        }
//
//        if (($cachedRows = $this->multiGet($cacheKeys)) && !empty($cachedRows)) {
//            foreach ($cachedRows as $cachedRow) {
//                $id = $cachedRow['ID'];
//                $cachedIds[] = $id;
//                unset($cachedRow['ID']);
//                $rows[$id] = $cachedRow;
//            }
//        }
//
//        return array($cachedIds, $rows);
//    }

    /**
     * Stores the specified sections to cache
     *
     * @param array $ids           An array containing section-type's as keys and section-id's as values.
     * @param array $dbRowsToCache An array of section-id's in {@link $ids} that will be stored to cache.
     *
     * @return void
     */
//    public function putSections(array $ids, array $dbRowsToCache)
//    {
//
//        foreach ($ids as $nodeStr => $id) {
//            $toCacheRows = array_key_exists($id, $dbRowsToCache) ? $dbRowsToCache[$id] : array();
//            $toCacheRows['ID'] = $id;
//            $this->put('node-sections-'.$nodeStr, $toCacheRows, $this->duration);
//        }
//
//    }

    /**
     * Removes sections for the specified NodeRef
     *
     * @param NodeRef $nodeRef The NodeRef to remove sections for
     *
     * @return void
     */
//    public function deleteSections(NodeRef $nodeRef)
//    {
//        $this->delete('node-sections-'.$nodeRef->getRefURL());
//    }


}

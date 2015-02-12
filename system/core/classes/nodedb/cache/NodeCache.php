<?php

class NodeCache extends AbstractCache implements NodeCacheInterface
{
    /** @var VersionService */
    protected $VersionService;

    /**
     * If this value is populated it will be used instead of the system version.
     * @see VersionService::getSystemVersion
     *
     * This prevents wholesale node cache busting (a very bad thing) on releases
     * where the schema didn't change, which is most releases.
     *
     * @var string
     */
    protected $nodeSchemaVersion;

    protected $duration = 0;
    protected $keysToDelete = array();

    /**
     * @param CacheStoreInterface $PrimaryCacheStore
     * @param VersionService $VersionService
     * @param string $nodeSchemaVersion
     * @param bool $nodeCacheKeepLocal
     */
    public function __construct(
        CacheStoreInterface $PrimaryCacheStore,
        VersionService $VersionService,
        $nodeSchemaVersion = null,
        $nodeCacheKeepLocal = true
    ) {
        parent::__construct($PrimaryCacheStore, 'nc', $nodeCacheKeepLocal);
        $this->VersionService = $VersionService;
        $this->nodeSchemaVersion = $nodeSchemaVersion ?: $this->VersionService->getSystemVersion();
    }

    /**
     * @param string $key
     * @return string
     */
    protected function cacheKey($key)
    {
        $key = parent::cacheKey($key);
        return "{$key}-{$this->nodeSchemaVersion}";
    }

    /**
     * Bound to "TransactionManager.commit" event.
     */
    public function onCommit()
    {
        if (!empty($this->keysToDelete)) {
            $this->keysToDelete = array_unique($this->keysToDelete);
            foreach ($this->keysToDelete as $key) {
                $this->delete($key);
            }
            $this->keysToDelete = array();
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function putNode(NodeRef $nodeRef, $row, $localOnly = false)
    {
        $this->put('node-'.$nodeRef->getElement()->getSlug().':'.$row['Slug'], $row, $this->duration, $localOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteNode(NodeRef $nodeRef, $localOnly = false)
    {
        $this->delete('node-'.$nodeRef->getRefURL(), $localOnly);
        $this->keysToDelete[] = 'node-'.$nodeRef->getRefURL();
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta($datatype, array $ids, $localOnly = false)
    {
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
     * {@inheritdoc}
     */
    public function putMeta($datatype, array $ids, array $dbRowsToCache, $localOnly = false)
    {
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
     * {@inheritdoc}
     */
    public function deleteMeta($datatype, NodeRef $nodeRef, $localOnly = false)
    {
        $this->delete('node-meta-'.$nodeRef->getRefURL(), $localOnly);
        $this->keysToDelete[] = 'node-meta-'.$nodeRef->getRefURL();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function deleteTags($direction, NodeRef $nodeRef,  NodeRef $nodeRef2, $localOnly = false)
    {
        $oppositeTagDirection = $direction == 'out' ? 'in' : 'out';
        $this->delete('node-'.$direction.'-tags-'.$nodeRef->getRefURL(), $localOnly);
        $this->delete('node-'.$oppositeTagDirection.'-tags-'.$nodeRef2->getRefURL(), $localOnly);

        $this->keysToDelete[] = 'node-'.$direction.'-tags-'.$nodeRef->getRefURL();
        $this->keysToDelete[] = 'node-'.$oppositeTagDirection.'-tags-'.$nodeRef2->getRefURL();
    }
}

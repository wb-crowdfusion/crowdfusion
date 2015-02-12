<?php

interface NodeCacheInterface
{
    /**
     * Returns the nodes specified by {@link $nodeRef} and {@link $slugs} from cache
     *
     * @param NodeRef $nodeRef The nodeRef that we'll used to find our stored nodes. Should not contain a Slug
     * @param array   $slugs   An array of slugs that when combined with the NodeRef specifies the nodes to get
     * @param bool $localOnly
     *
     * @return array An array containing 2 items. First, an array of cachedSlugs and second an array of cachedRows.
     */
    public function getNodes(NodeRef $nodeRef, $slugs, $localOnly = false);

    /**
     * Stores a node to cache
     *
     * @param NodeRef $nodeRef The NodeRef of the node that's stored
     * @param array   $row     An array containing our Node data
     * @param bool $localOnly
     *
     * @return void
     */
    public function putNode(NodeRef $nodeRef, $row, $localOnly = false);

    /**
     * Deletes the node specified by NodeRef from cache
     *
     * @param NodeRef $nodeRef The NodeRef of the node to remove
     * @param bool $localOnly
     *
     * @return void
     */
    public function deleteNode(NodeRef $nodeRef, $localOnly = false);

    /**
     * Retrieves the specified meta data from cache
     *
     * @param string $datatype The datatype of the meta data we'll retrieve
     * @param array  $ids      An array like ['favorite-color' => 51, 'meta-id' => 52, $meta_id => $id, ...]
     * @param bool $localOnly
     *
     * @return array An array like [array $cachedIds, array $rows]
     */
    public function getMeta($datatype, array $ids, $localOnly = false);

    /**
     * Stores meta data to cache
     *
     * @param string $datatype      The datatype of the meta data
     * @param array  $ids           An array containing $nodeStr's as keys and $id's as values
     * @param array  $dbRowsToCache An array of $id's in $ids that should be cached
     * @param bool $localOnly
     *
     * @return void
     */
    public function putMeta($datatype, array $ids, array $dbRowsToCache, $localOnly = false);

    /**
     * Removes the specified meta item from cache
     *
     * @param string  $datatype The datatype of the meta item to remove
     * @param NodeRef $nodeRef  The nodeRef for the node that we will remove meta data for
     * @param bool $localOnly
     *
     * @return void
     */
    public function deleteMeta($datatype, NodeRef $nodeRef, $localOnly = false);

    /**
     * Retrieves the specified tags from cache
     *
     * @param string $direction The cached tag direction. Should be 'in' or 'out'
     * @param array  $ids       An array containing $nodeStr's as keys and $id's as values
     * @param bool $localOnly
     *
     * @return array An array like [array $cachedIds, array $rows]
     */
    public function getTags($direction, array $ids, $localOnly = false);

    /**
     * Stores the specified tags to cache
     *
     * @param string $direction     The direction of the tags we're caching. Should be 'in' or 'out'.
     * @param array  $ids           An array containing $nodeStr's as keys and $id's as values. This is a list of all tags.
     * @param array  $dbRowsToCache An array of $id's in $ids that we want to store to cache.
     * @param bool $localOnly
     *
     * @return void
     */
    public function putTags($direction, array $ids, array $dbRowsToCache, $localOnly = false);

    /**
     * Removes the cached tag data for the nodeRefs specified
     *
     * @param string  $direction The direction of the tags on $nodeRef to clear
     * @param NodeRef $nodeRef   The nodeRef containing tags in {@link $direction} that we want to clear from cache
     * @param NodeRef $nodeRef2  The corresponding opposite-direction NodeRef that we need to clear tags from
     * @param bool $localOnly
     *
     * @return void
     */
    public function deleteTags($direction, NodeRef $nodeRef,  NodeRef $nodeRef2, $localOnly = false);
}
<?php
/**
 * NodeServiceInterface interface
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
 * @version     $Id: NodeServiceInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Interface definition for interacting with the Node Content Repository
 *
 * @package     CrowdFusion
 */
interface NodeServiceInterface
{

    /**
     * Adds the node, fails if NodeRef exists already
     *
     * @abstract
     * @param Node $node
     * @return Node
     */
    public function add(Node $node);

    /**
     * Adds the node, iterating NodeRef Slug with '-1' if NodeRef exists
     *
     * @abstract
     * @param Node $node
     * @return Node
     */
    public function quickAdd(Node $node);

    /**
     * Modifies the Node, saving any changes
     *
     * @abstract
     * @param Node $node
     * @return void
     */
    public function edit(Node $node);

    /**
     * Edits the Node if it exists, otherwises adds the Node
     *
     * @abstract
     * @param Node $node
     * @return Node
     */
    public function replace(Node $node);

    /**
     * Change the Slug on an existing Node
     *
     * @abstract
     * @param NodeRef $nodeRef Existing NodeRef to rename
     * @param  $newSlug New slug
     * @return Node
     */
    public function rename(NodeRef $nodeRef, $newSlug);

    /**
     * Delete a Node, move tags to a new Node
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param  $mergeSlug Slug of Node with same Element to move tags to
     * @return void
     */
    public function delete(NodeRef $nodeRef, $mergeSlug = null);

    /**
     * Undelete a previously deleted Node
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @return void
     */
    public function undelete(NodeRef $nodeRef);

    /**
     * Permanently delete a deleted Node from the storage engine
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @return void
     */
    public function purge(NodeRef $nodeRef);

    /**
     * Add a Tag to the Node, used when the direction is unknown
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param Tag $tag Tag to add
     * @return void
     */
    public function addTag(NodeRef $nodeRef, Tag $tag);

    /**
     * Remove a Tag from the Node, used when the direction is unknown
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param Tag $tag Tag to remove
     * @return void
     */
    public function removeTag(NodeRef $nodeRef, Tag $tag);


    /**
     * Add an outbound Tag to the Node
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param Tag $tag Tag to add
     * @return void
     */
    public function addOutTag(NodeRef $nodeRef, Tag $tag);

    /**
     * Remove an outbound Tag from the Node
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param Tag $tag Tag to remove
     * @return void
     */
    public function removeOutTag(NodeRef $nodeRef, Tag $tag);

    /**
     * Add an inbound Tag to the Node
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param Tag $tag Tag to add
     * @return void
     */
    public function addInTag(NodeRef $nodeRef, Tag $tag);

    /**
     * Remove an inbound Tag from the Node
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param Tag $tag Tag to remove
     * @return void
     */
    public function removeInTag(NodeRef $nodeRef, Tag $tag);

    /**
     * Update a Meta value on the Node
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param  $id Identifier for the meta field, eg. #contents
     * @param  $value The new value for the Meta
     * @return void
     */
    public function updateMeta(NodeRef $nodeRef, $id, $value = null);

    /**
     * Increase the value of the Meta by 1
     *
     * If the meta value does not exist, this function will set its value to 1
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param string $metaID Identifier for the meta field, eg. #contents
     * @param int $value Number to increment by
     * @return void
     */
    public function incrementMeta(NodeRef $nodeRef, $metaID, $value = 1);

    /**
     * Decrease the value of the Meta by 1
     *
     * If the meta value does not exist, this function will set its value to -1
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param string $metaID Identifier for the meta field, eg. #contents
     * @param int $value Number to decrement by
     * @return void
     */
    public function decrementMeta(NodeRef $nodeRef, $metaID, $value = 1);

    /**
     * Return a unique NodeRef, based on Slug or specified $title
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param string $title    Basis for slug generation if Slug is not set on NodeRef
     * @param boolean $useTime Add microtime digits to end of slug, good for high concurrency
     * @return NodeRef
     */
    public function generateUniqueNodeRef(NodeRef $nodeRef, $title = null, $useTime = false);

    /**
     * Returns true if Node at NodeRef exists
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @return boolean
     */
    public function refExists(NodeRef $nodeRef);

    /**
     * Returns Node object existing at given NodeRef
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param NodePartials $nodePartials Meta and tags to populate
     * @param boolean $forceReadWrite Set to true to force reading from RW master
     * @return Node
     */
    public function getByNodeRef(NodeRef $nodeRef, NodePartials $nodePartials = null, $forceReadWrite = true);

    /**
     * Returns array containing Node objects for each given NodeRef
     *
     * @abstract
     * @param mixed $nodeRefs Array of NodeRefs, or a single NodeRef
     * @param NodePartials $nodePartials Meta and tags to populate
     * @param boolean $forceReadWrite Set to true to force reading from RW master
     * @param boolean $allowDeleted If set to true, returns Nodes with "deleted" Status
     * @return array Array of Nodes with NodeRef's as key
     */
    public function multiGet($nodeRefs, NodePartials $nodePartials = null, $forceReadWrite = false, $allowDeleted = false);

    /**
     * Finds Nodes according to given query parameters
     *
     * @abstract
     * @param NodeQuery $nodeQuery Query to execute
     * @param boolean $forceReadWrite Set to true to force querying from RW master
     * @return NodeQuery Populated NodeQuery object
     */
    public function findAll(NodeQuery $nodeQuery, $forceReadWrite = false);

    /**
     * Create the necessary database schema for the given Element structure
     *
     * @abstract
     * @param Element $element
     * @return void
     */
    public function createDBSchema(Element $element);


    /**
     * Drop the database schema for the given Element structure
     *
     * NOTE: will not drop a table if rows exist
     *
     * @abstract
     * @param Element $element
     * @return void
     */
    public function dropDBSchema(Element $element);


    /**
     * Copies all meta data from one underlying storage table to another (e.g. varchar to text)
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param string $metaID
     * @param string $oldStorageDatatype
     * @param string $newStorageDatatype
     * @param bool $force Whether to overwrite existing data (useful if you're migrating back to an older format)
     * @return int Number of affected rows
     */
    public function migrateMetaStorage(NodeRef $nodeRef, $metaID, $oldStorageDatatype, $newStorageDatatype, $force = false);


    /**
     * Removes deprecated meta data from underlying storage
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param string $metaID
     * @param string $datatype
     * @return int Number of affected rows
     */
    public function deprecateMeta(NodeRef $nodeRef, $metaID, $datatype);

    /**
     * Populates tags and partial jumps with actual TagLinkNode records according to the supplied NodePartials
     *
     * @abstract
     * @param Node $node
     * @param NodePartials $nodePartials Meta and tags to populate
     * @param boolean $forceReadWrite Set to true to force reading from RW master
     * @return Node
     */
    public function resolveLinkedNodes(Node $node, NodePartials $nodePartials = null, $forceReadWrite = true);


    /**
     * Copies a node from one element into another, preserving all shared tags and meta
     *
     * @abstract
     * @param NodeRef $nodeRef
     * @param Element $newNodeElement New element for node
     * @return Node
     */
    public function copy(NodeRef $nodeRef, Element $newNodeElement);
}
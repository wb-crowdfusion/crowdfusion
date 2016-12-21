<?php
/**
 * RegulatedNodeService
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
 * @version     $Id: RegulatedNodeService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * RegulatedNodeService
 *
 * @package     CrowdFusion
 */
class RegulatedNodeService extends NodeService
{
    public function setNodePermissions(NodePermissions $NodePermissions)
    {
        $this->NodePermissions = $NodePermissions;
    }

    public function setNodeRefService($NodeRefService)
    {
        $this->NodeRefService = $NodeRefService;
    }

    public function add(Node $node)
    {
        $nodeRef = $node->getNodeRef();
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $node, false);
        if ($node->Status == 'published')
            $this->NodePermissions->checkThrow('publish', $nodeRef, $node, false);

        return parent::add($node);
    }

    public function edit(Node $node)
    {
        $nodeRef = $node->getNodeRef();
        $existingNode = $this->getByNodeRef($nodeRef);

        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $node, false);

        if ($nodeRef->getSlug() != $node->getSlug()) {
            $newNodeRef = new NodeRef( $nodeRef->getElement(), $node->getSlug());
            $this->NodePermissions->checkThrow('rename', $nodeRef, $newNodeRef, false);
        }

        if($existingNode->Status == 'deleted' && $node->Status != 'deleted')
            return $this->undelete($nodeRef);

        if($existingNode->Status != 'deleted' && $node->Status == 'deleted')
            return $this->delete($nodeRef);

        if ($existingNode->Status != 'published' && $node->Status == 'published')
            $this->NodePermissions->checkThrow('publish', $nodeRef, $node, false);

        if ($existingNode->Status == 'published' && $node->Status != 'published')
            $this->NodePermissions->checkThrow('unpublish', $nodeRef, $node, false);


        return parent::edit($node);
    }

    public function rename(NodeRef $nodeRef, $newSlug)
    {
        $newNodeRef = new NodeRef( $nodeRef->getElement(), $node->getSlug());
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $newNodeRef, false);
        return parent::rename($nodeRef, $newSlug);
    }

    public function delete(NodeRef $nodeRef, $mergeSlug = null)
    {
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $null = null, false);

        return parent::delete($nodeRef, $mergeSlug);
    }

    public function undelete(NodeRef $nodeRef)
    {
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $null = null, false);

        return parent::undelete($nodeRef);
    }

    public function getByNodeRef(NodeRef $nodeRef, NodePartials $nodePartials = null, $forceReadWrite = true)
    {
        return $this->NodeLookupDAO->getByNodeRef($nodeRef, $nodePartials, $forceReadWrite, true);
    }

    public function addTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $tag, false);
        return parent::addTag($nodeRef, $tag);
    }

    public function removeTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $tag, false);
        return parent::removeTag($nodeRef, $tag);
    }


    public function addOutTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $tag, false);
        return parent::addOutTag($nodeRef, $tag);
    }

    public function removeOutTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $tag, false);
        return parent::removeOutTag($nodeRef, $tag);
    }

    public function addInTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $tag, false);
        return parent::addInTag($nodeRef, $tag);
    }

    public function removeInTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $tag, false);
        return parent::removeInTag($nodeRef, $tag);
    }

    public function updateMeta(NodeRef $nodeRef, $metaID, $value = null)
    {
        $metaID = ltrim($metaID, '#');
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $metaID, false);
        parent::updateMeta($nodeRef, $metaID, $value);
    }

    public function incrementMeta(NodeRef $nodeRef, $metaID, $value = 1)
    {
        $metaID = ltrim($metaID, '#');
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $metaID, false);
        parent::incrementMeta($nodeRef, $metaID, $value);
    }

    public function decrementMeta(NodeRef $nodeRef, $metaID, $value = 1)
    {
        $metaID = ltrim($metaID, '#');
        $this->NodePermissions->checkThrow(__FUNCTION__, $nodeRef, $metaID, false);
        parent::decrementMeta($nodeRef, $metaID, $value);
    }

    public function multiGet($nodeRefs, NodePartials $nodePartials = null, $forceReadWrite = false, $allowDeleted = false)
    {
        return $this->NodeMultiGetDAO->multiGet($nodeRefs, $nodePartials, $forceReadWrite, true, false);
    }

    public function findAll(NodeQuery $nodeQuery, $forceReadWrite = false)
    {

        // NODEREFS
        $nodeQuery2 = $this->NodeRefService->normalizeNodeQuery($nodeQuery);

        $nodeRefs = $nodeQuery2->getParameter('NodeRefs.normalized');
        $nodePartials = $nodeQuery2->getParameter('NodePartials.eq');

        foreach ((array)$nodeRefs as $k => $nodeRef)
            if (!$this->NodePermissions->check('get', $nodeRef, $nodePartials, true))
                unset($nodeRefs[$k]);

        $nodeQuery->setParameter('NodeRefs.normalized', $nodeRefs);
        $nodeQuery->setParameter('Permissions.check', true);

        return parent::findAll($nodeQuery, $forceReadWrite);
    }

    public function createDBSchema(Element $element)
    {
        throw new PermissionsException('Permission denied to create DB schema');
    }

    public function dropDBSchema(Element $element)
    {
        throw new PermissionsException('Permission denied to drop DB schema');
    }

    public function migrateMetaStorage(NodeRef $nodeRef, $metaID, $oldStorageDatatype, $newStorageDatatype, $force = false)
    {
        throw new PermissionsException('Permission denied to migrate meta storage');
    }

    public function deprecateMeta(NodeRef $nodeRef, $metaID, $datatype)
    {
        throw new PermissionsException('Permission denied to deprecate meta');
    }

    public function resolveLinkedNodes(Node $node, NodePartials $nodePartials = null, $forceReadWrite = true)
    {
        return $this->NodeLookupDAO->resolveLinkedNodes($node, $nodePartials, $forceReadWrite, true);
    }

    public function purge(NodeRef $nodeRef)
    {
        throw new PermissionsException('Permission denied to purge');
    }
}

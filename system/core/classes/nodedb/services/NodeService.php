<?php
/**
 * NodeService
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
 * @version     $Id: NodeService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeService
 *
 * @package     CrowdFusion
 */
class NodeService implements NodeServiceInterface
{
    protected $NodeDAO;
    protected $NodeLookupDAO;
    protected $NodeMultiGetDAO;
    protected $NodeFindAllDAO;
    protected $NodeAdminDAO;

    public function setNodeDAO($NodeDAO)
    {
        $this->NodeDAO = $NodeDAO;
    }

    public function setNodeLookupDAO(NodeLookupDAO $NodeLookupDAO)
    {
        $this->NodeLookupDAO = $NodeLookupDAO;
    }

    public function setNodeMultiGetDAO($NodeMultiGetDAO)
    {
        $this->NodeMultiGetDAO = $NodeMultiGetDAO;
    }

    public function setNodeFindAllDAO($NodeFindAllDAO)
    {
        $this->NodeFindAllDAO = $NodeFindAllDAO;
    }

    public function setNodeAdminDAO($NodeAdminDAO)
    {
        $this->NodeAdminDAO = $NodeAdminDAO;
    }




    public function add(Node $node)
    {
        return $this->NodeDAO->add($node);
    }

    public function quickAdd(Node $node)
    {
        $nodeRef = $node->getResolvedNodeRef();
        $title = $node->Title;

        do {

            try {

                $uniqueNodeRef = $this->NodeLookupDAO->generateUniqueNodeRef($nodeRef, $title);

                $node->setNodeRef($uniqueNodeRef);

                return $this->add($node);

            } catch (ValidationException $ve)
            {
                if(!$ve->getErrors()->hasError($uniqueNodeRef->getElement()->getSlug().'.Slug.exists'))
                    throw $ve;
                else
                    $nodeRef = $node->getResolvedNodeRef();
            }

        } while(true);

    }

    public function edit(Node $node)
    {
        return $this->NodeDAO->edit($node);
    }

    public function replace(Node $node)
    {
        if(!$node->getNodeRef()->isFullyQualified())
            return $this->add($node);

        if($this->refExists($node->getNodeRef()))
            return $this->edit($node);
        else
            return $this->add($node);
    }

    public function rename(NodeRef $nodeRef, $newSlug)
    {
        return $this->NodeDAO->rename($nodeRef, $newSlug);
    }

    public function delete(NodeRef $nodeRef, $mergeSlug = null)
    {
        return $this->NodeDAO->delete($nodeRef, $mergeSlug);
    }

    public function undelete(NodeRef $nodeRef)
    {
        return $this->NodeDAO->undelete($nodeRef);
    }

    public function purge(NodeRef $nodeRef)
    {
        return $this->NodeDAO->purge($nodeRef);
    }

    public function refExists(NodeRef $nodeRef)
    {
        return $this->NodeLookupDAO->refExists($nodeRef);
    }

    public function getByNodeRef(NodeRef $nodeRef, NodePartials $nodePartials = null, $forceReadWrite = true)
    {
        return $this->NodeLookupDAO->getByNodeRef($nodeRef, $nodePartials, $forceReadWrite);
    }

    public function addTag(NodeRef $nodeRef, Tag $tag)
    {
        return $this->NodeDAO->addTag($nodeRef, $tag);
    }

    public function removeTag(NodeRef $nodeRef, Tag $tag)
    {
        return $this->NodeDAO->removeTag($nodeRef, $tag);
    }


    public function addOutTag(NodeRef $nodeRef, Tag $tag)
    {
        return $this->NodeDAO->addOutTag($nodeRef, $tag);
    }

    public function removeOutTag(NodeRef $nodeRef, Tag $tag)
    {
        return $this->NodeDAO->removeOutTag($nodeRef, $tag);
    }

    public function addInTag(NodeRef $nodeRef, Tag $tag)
    {
        return $this->NodeDAO->addInTag($nodeRef, $tag);
    }

    public function removeInTag(NodeRef $nodeRef, Tag $tag)
    {
        return $this->NodeDAO->removeInTag($nodeRef, $tag);
    }

    public function updateMeta(NodeRef $nodeRef, $id, $value = null)
    {
        $this->NodeDAO->updateMeta($nodeRef, $id, $value);
    }

    public function incrementMeta(NodeRef $nodeRef, $metaID, $value = 1)
    {
        $this->NodeDAO->incrementMeta($nodeRef, $metaID, $value);
    }

    public function decrementMeta(NodeRef $nodeRef, $metaID, $value = 1)
    {
        $this->NodeDAO->decrementMeta($nodeRef, $metaID, $value);
    }

    public function multiGet($nodeRefs, NodePartials $nodePartials = null, $forceReadWrite = false, $allowDeleted = false)
    {
        return $this->NodeMultiGetDAO->multiGet($nodeRefs, $nodePartials, $forceReadWrite, $allowDeleted);
    }

    public function findAll(NodeQuery $nodeQuery, $forceReadWrite = false)
    {
        return $this->NodeFindAllDAO->findAll($nodeQuery, $forceReadWrite);
    }

    public function createDBSchema(Element $element)
    {
        $this->NodeAdminDAO->createDBSchema($element);
    }

    public function dropDBSchema(Element $element)
    {
        $this->NodeAdminDAO->dropDBSchema($element);
    }

    public function migrateMetaStorage(NodeRef $nodeRef, $metaID, $oldStorageDatatype, $newStorageDatatype, $force = false)
    {
        return $this->NodeDAO->migrateMetaStorage($nodeRef, $metaID, $oldStorageDatatype, $newStorageDatatype, $force);
    }

    public function deprecateMeta(NodeRef $nodeRef, $metaID, $datatype)
    {
        return $this->NodeDAO->deprecateMeta($nodeRef, $metaID, $datatype);
    }

    public function generateUniqueNodeRef(NodeRef $nodeRef, $title = null, $useTime = false)
    {
        return $this->NodeLookupDAO->generateUniqueNodeRef($nodeRef, $title, $useTime);
    }

    public function resolveLinkedNodes(Node $node, NodePartials $nodePartials = null, $forceReadWrite = true)
    {
        return $this->NodeLookupDAO->resolveLinkedNodes($node, $nodePartials, $forceReadWrite);
    }

    public function copy(NodeRef $nodeRef, Element $newElement)
    {
        return $this->NodeDAO->copy($nodeRef, $newElement);
    }
}
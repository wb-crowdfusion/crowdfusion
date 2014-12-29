<?php
/**
 * NodeDAO
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
 * @version     $Id: NodeDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeDAO
 *
 * @package     CrowdFusion
 */
class NodeDAO extends AbstractNodeDAO
{

    protected $NodeTagsDAO;
    protected $NodeMetaDAO;
    protected $NodeValidator;
    protected $NodeLookupDAO;
    protected $NodeFindAllDAO;
    protected $Locks;

    public function setNodeTagsDAO(NodeTagsDAO $NodeTagsDAO)
    {
        $this->NodeTagsDAO = $NodeTagsDAO;
    }

    public function setNodeMetaDAO(NodeMetaDAO $NodeMetaDAO)
    {
        $this->NodeMetaDAO = $NodeMetaDAO;
    }

//    public function setNodeSectionsDAO(NodeSectionsDAO $NodeSectionsDAO)
//    {
//        $this->NodeSectionsDAO = $NodeSectionsDAO;
//    }

    public function setNodeValidator(NodeValidator $NodeValidator)
    {
        $this->NodeValidator = $NodeValidator;
    }

    public function setNodeLookupDAO(NodeLookupDAO $NodeLookupDAO)
    {
        $this->NodeLookupDAO = $NodeLookupDAO;
    }

    public function setNodeFindAllDAO(NodeFindAllDAO $NodeFindAllDAO)
    {
        $this->NodeFindAllDAO = $NodeFindAllDAO;
    }

    public function setLocks(LocksInterface $Locks)
    {
        $this->Locks = $Locks;
    }


    public function __construct()
    {

    }

    public function add(Node $node)
    {
        $now = $this->DateFactory->newStorageDate();
        if(!$node->hasCreationDate())
            $node->CreationDate = $now;

        $node->ModifiedDate = $now;

        $this->NodeValidator
            ->validateFor('add', $node)
            ->throwOnError();

        $nodeRef = $node->getNodeRef();

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'pre', $nodeRef, $node);

        if($node->Status == 'published')
            $this->NodeEvents->fireNodeEvents('publish', 'pre', $nodeRef, $node);

        if($nodeRef->getSlug() != $node->getSlug())
        {
            $newNodeRef = new NodeRef($nodeRef->getElement(), $node->getSlug());

            $node->setNodeRef($newNodeRef);

            $nodeRef = $node->getNodeRef();
        }

        if(empty($node->TreeID) && $nodeRef->getElement()->getSchema()->hasTreeOriginTagDef())
            $this->insertIntoTree($node);
//        sleep(15);

        $this->addInternal($node);

        // fire events
        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'post', $nodeRef, $node);
        if($node->Status == 'published')
            $this->NodeEvents->fireNodeEvents('publish', 'post', $nodeRef, $node);


        return $node;

    }


    public function edit(Node $node)
    {

        // validate the object
        $this->NodeValidator
            ->validateFor('edit', $node)
            ->throwOnError();

        $nodeRef = $node->getNodeRef();

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'pre', $nodeRef, $node);


        // rename the node
        if($nodeRef->getSlug() != $node->getSlug())
        {

            $newNode = $this->rename($nodeRef, $node->getSlug());

            $node->setNodeRef($newNode->getNodeRef());

            // edit the new node
            return $this->edit($node);
        }

        $existingNode = $this->NodeLookupDAO->getByNodeRef($nodeRef);

        if($existingNode->Status == 'deleted' && $node->Status != 'deleted')
        {
            $this->undelete($nodeRef);
            return $this->edit($node);
        }

        if($existingNode->Status != 'deleted' && $node->Status == 'deleted')
        {
            $this->delete($nodeRef);
            return $this->edit($node);
        }

        if($existingNode->Status != 'published' && $node->Status == 'published')
            $this->NodeEvents->fireNodeEvents('publish', 'pre', $nodeRef, $node);

        if($existingNode->Status == 'published' && $node->Status != 'published')
            $this->NodeEvents->fireNodeEvents('unpublish', 'pre', $nodeRef, $node);

        if(empty($node->TreeID) && $nodeRef->getElement()->getSchema()->hasTreeOriginTagDef())
            $this->insertIntoTree($node, $existingNode);

        $this->editInternal($node);

        // fire events
        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'post', $nodeRef, $node, $existingNode);

        if($existingNode->Status != 'published' && $node->Status == 'published')
            $this->NodeEvents->fireNodeEvents('publish', 'post', $nodeRef, $node, $existingNode);

        if($existingNode->Status == 'published' && $node->Status != 'published')
            $this->NodeEvents->fireNodeEvents('unpublish', 'post', $nodeRef, $node, $existingNode);


        return $node;

    }


    public function rename(NodeRef $nodeRef, $newSlug)
    {
        $newNodeRef = new NodeRef($nodeRef->getElement(), $newSlug);

        $this->NodeValidator
            ->validateFor('rename', $nodeRef, $newNodeRef)
            ->throwOnError();

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'pre', $nodeRef, $newNodeRef);
//        $this->NodeEvents->fireNodeEvents('delete', 'pre', $nodeRef);
//        $this->NodeEvents->fireNodeEvents('add', 'pre', $newNodeRef);

        // select all data from the original node
        $fullNode = $this->NodeLookupDAO->getByNodeRef($nodeRef, new NodePartials('all', 'all', 'all'));

        // don't fire tag/meta events during a rename
        $currentlyEnabled = $this->NodeEvents->areEventsEnabled();

        $this->NodeEvents->disableEvents();

        // delete the old node, but not all its data
        $this->deleteInternal($nodeRef);

        // adjust new node's ref
        $fullNode->setNodeRef($newNodeRef);

        // add the new node with all its original data
        $this->addInternal($fullNode);

        if($currentlyEnabled)
            $this->NodeEvents->enableEvents();

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'post', $nodeRef, $newNodeRef);
//        $this->NodeEvents->fireNodeEvents('delete', 'post', $nodeRef);
//        $this->NodeEvents->fireNodeEvents('add', 'post', $newNodeRef);

        return $fullNode;
    }


    public function delete(NodeRef $nodeRef, $mergeSlug = null)
    {
        // validate for delete
        $this->NodeValidator
            ->validateFor('delete', $nodeRef)
            ->throwOnError();

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'pre', $nodeRef);

        // if merging, rename tag links to new record
        if(!empty($mergeSlug)) {

            $mergeRef = new NodeRef($nodeRef->getElement(), $mergeSlug);

            $oldNode = $this->NodeLookupDAO->getByNodeRef($nodeRef, new NodePartials('', '', 'all'), true);
            if(empty($oldNode))
                throw new NodeException('Unable to merge deleted node to non-existent slug ['.$mergeSlug.']');

            $inTags = $oldNode->getInTags();

            $newPartials = new NodePartials();
            foreach($inTags as $inTag)
                $newPartials->increaseInPartials($inTag->getTagRole());

            $mergeNode = $this->NodeLookupDAO->getByNodeRef($mergeRef, $newPartials);
            $mergeNode->addInTags($inTags);

            // add all node's in tags to the merged node as in tags (aka copy in tags)
            $this->editInternal($mergeNode);
        }

        $existingNode = $this->NodeLookupDAO->getByNodeRef($nodeRef);

        if($existingNode->Status == 'published')
            $this->NodeEvents->fireNodeEvents('unpublish', 'pre', $nodeRef, $existingNode);

        $this->deleteInternal($nodeRef);

        // fire events
        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'post', $nodeRef);

        if($existingNode->Status == 'published')
            $this->NodeEvents->fireNodeEvents('unpublish', 'post', $nodeRef, $existingNode);

        return;
    }

    public function undelete(NodeRef $nodeRef)
    {
        if(!$nodeRef->isFullyQualified())
            throw new NodeException('Cannot undelete node without fully-qualified NodeRef');

        $existingNode = $this->NodeLookupDAO->getByNodeRef($nodeRef);

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'pre', $nodeRef, $existingNode);
        $this->NodeEvents->fireNodeEvents('add', 'pre', $nodeRef, $existingNode);


        // undelete
        $this->undeleteInternal($nodeRef);
        $existingNode->Status = 'draft';
        $existingNode->ModifiedDate = $this->DateFactory->newStorageDate();

        // fire events
        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'post', $nodeRef, $existingNode);
        $this->NodeEvents->fireNodeEvents('add', 'post', $nodeRef, $existingNode);

        return;
    }

    public function purge(NodeRef $nodeRef)
    {
        if(!$nodeRef->isFullyQualified())
            throw new NodeException('Cannot purge node without fully-qualified NodeRef');

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'pre', $nodeRef);

        $this->purgeInternal($nodeRef);

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, 'post', $nodeRef);
    }


    public function updateMeta(NodeRef $nodeRef, $metaID, $value = null)
    {
        $metaID = ltrim($metaID, '#');

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $metaID);

        // retrieve the DB connection and table from DataSourceManager
        $db = $this->getConnectionForWrite($nodeRef);

        $id = $this->getRecordIDFromNodeRef($nodeRef);

        // Ensure the meta has a meta_def
        $metaDef = $nodeRef->getElement()->getSchema()->getMetaDef($metaID);

        $fieldTitle = $metaDef->Title;
        if(!$metaDef->Validation->isValid($value))
            throw new NodeException("$fieldTitle {$metaDef->Validation->getFailureMessage()}.");

        $this->NodeMetaDAO->saveMeta($db, $nodeRef, $id, '#'.$metaID, array(new Meta($metaID, $value)));
    }

    public function incrementMeta(NodeRef $nodeRef, $metaID, $value = 1)
    {
        return $this->NodeMetaDAO->incrementMeta($nodeRef, $metaID, $value);
    }

    public function decrementMeta(NodeRef $nodeRef, $metaID, $value = 1)
    {
        return $this->NodeMetaDAO->decrementMeta($nodeRef, $metaID, $value);
    }



    public function migrateMetaStorage(NodeRef $nodeRef, $metaID, $oldDatatype, $newDatatype, $force = false)
    {
        $metaID = ltrim($metaID, '#');

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $metaID);

        $connectionCouplets = $this->getResolvedConnectionCouplets(array($nodeRef), true);

        $affected = 0;
        foreach($connectionCouplets as $connectionCouplet)
        {
            // retrieve the DB connection and table from DataSourceManager
            $db = $connectionCouplet->getConnection();

            // Ensure the meta has a meta_def
            $nodeRef->getElement()->getSchema()->getMetaDef($metaID);

            $affected += $this->NodeMetaDAO->migrateMetaStorage($db, $nodeRef, $metaID, $oldDatatype, $newDatatype, $force);

        }

        return $affected;
    }


    public function deprecateMeta(NodeRef $nodeRef, $metaID, $datatype)
    {
        $metaID = ltrim($metaID, '#');

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $metaID);

        $connectionCouplets = $this->getResolvedConnectionCouplets(array($nodeRef), true);

        $affected = 0;
        foreach($connectionCouplets as $connectionCouplet)
        {
            // retrieve the DB connection and table from DataSourceManager
            $db = $connectionCouplet->getConnection();

            $affected += $this->NodeMetaDAO->deprecateMeta($db, $nodeRef, $metaID, $datatype);
        }

        return $affected;
    }


    public function addTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $tag);


        $tagId = $tag->getTagRole();
        $schema = $nodeRef->getElement()->getSchema();
        $tagDef = $schema->getTagDef($tagId);

        $tag = TagUtils::filterTagAgainstDef($tag, $tagDef);

        // retrieve the DB connection and table from DataSourceManager
        $db = $this->getConnectionForWrite($nodeRef);

        // Make sure the node we're adding to exists
        $id = $this->getRecordIDFromNodeRef($nodeRef);

        // If the role being tagged to does not allow multiple tags, we need to get the node and ensure that
        // a tag isn't already tagged to it. If there is a tag already, the NodeValidator will throw a ValidationException.
        if(!StringUtils::strToBool($tagDef->Multiple)) {
            if($tagDef->Direction == 'in') {
                $existingNode = $this->NodeLookupDAO->getByNodeRef($nodeRef, new NodePartials('', '', '#'.$tagId));
                $existingNode->addInTag($tag);
            }
            else {
                $existingNode = $this->NodeLookupDAO->getByNodeRef($nodeRef, new NodePartials('', '#'.$tagId));
                $existingNode->addOutTag($tag);
            }

            $this->NodeValidator
                ->validateFor('edit', $existingNode)
                ->throwOnError();
        }

        // save the tags
        if($tagDef->Direction == 'in')
            $this->NodeTagsDAO->saveInTags($db, $nodeRef, $id, $tag->getMatchPartial()->toString(), array($tag));
        else
            $this->NodeTagsDAO->saveOutTags($db, $nodeRef, $id, $tag->getMatchPartial()->toString(), array($tag));
    }

    public function removeTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $tag);

        $id = $tag->getTagRole();
        $schema = $nodeRef->getElement()->getSchema();
        $tagDef = $schema->getTagDef($id);

        $tag = TagUtils::filterTagAgainstDef($tag, $tagDef);

        // retrieve the DB connection and table from DataSourceManager
        $db = $this->getConnectionForWrite($nodeRef);

        $id = $this->getRecordIDFromNodeRef($nodeRef);

        // save the tags
        if($tagDef->Direction == 'in')
            $this->NodeTagsDAO->saveInTags($db, $nodeRef, $id, $tag->getMatchPartial()->toString(), array());
        else
            $this->NodeTagsDAO->saveOutTags($db, $nodeRef, $id, $tag->getMatchPartial()->toString(), array());
    }


    public function addOutTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $tag);


        $tagId = $tag->getTagRole();
        $schema = $nodeRef->getElement()->getSchema();
        $tagDef = $schema->getTagDef($tagId);

        $tag = TagUtils::filterTagAgainstDef($tag, $tagDef);

        // retrieve the DB connection and table from DataSourceManager
        $db = $this->getConnectionForWrite($nodeRef);

        // Make sure the node we're adding to exists
        $id = $this->getRecordIDFromNodeRef($nodeRef);

        // If the role being tagged to does not allow multiple tags, we need to get the node and ensure that
        // a tag isn't already tagged to it. If there is a tag already, the NodeValidator will throw a ValidationException.
        if(!StringUtils::strToBool($tagDef->Multiple)) {
            $existingNode = $this->NodeLookupDAO->getByNodeRef($nodeRef, new NodePartials('', '#'.$tagId));
            $existingNode->addOutTag($tag);

            $this->NodeValidator
                ->validateFor('edit', $existingNode)
                ->throwOnError();
        }

        $matchPartial = $tag->getMatchPartial();
        $matchPartial->TagSlug = $tag->getTagSlug();

        // save the tags
        $this->NodeTagsDAO->saveOutTags($db, $nodeRef, $id, $matchPartial->toString(), array($tag));
    }

    public function removeOutTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $tag);

        $id = $tag->getTagRole();
        $schema = $nodeRef->getElement()->getSchema();
        $tagDef = $schema->getTagDef($id);

        $tag = TagUtils::filterTagAgainstDef($tag, $tagDef);

        // retrieve the DB connection and table from DataSourceManager
        $db = $this->getConnectionForWrite($nodeRef);

        $id = $this->getRecordIDFromNodeRef($nodeRef);

        $matchPartial = $tag->getMatchPartial();
        $matchPartial->TagSlug = $tag->getTagSlug();

        // save the tags
        $this->NodeTagsDAO->saveOutTags($db, $nodeRef, $id, $matchPartial->toString(), array());
    }

    public function addInTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $tag);


        $tagId = $tag->getTagRole();
        $schema = $nodeRef->getElement()->getSchema();
        $tagDef = $schema->getTagDef($tagId);

        $tag = TagUtils::filterTagAgainstDef($tag, $tagDef);

        // retrieve the DB connection and table from DataSourceManager
        $db = $this->getConnectionForWrite($nodeRef);

        // Make sure the node we're adding to exists
        $id = $this->getRecordIDFromNodeRef($nodeRef);

        // If the role being tagged to does not allow multiple tags, we need to get the node and ensure that
        // a tag isn't already tagged to it. If there is a tag already, the NodeValidator will throw a ValidationException.
        if(!StringUtils::strToBool($tagDef->Multiple)) {
            $existingNode = $this->NodeLookupDAO->getByNodeRef($nodeRef, new NodePartials('', '', '#'.$tagId));
            $existingNode->addInTag($tag);

            $this->NodeValidator
                ->validateFor('edit', $existingNode)
                ->throwOnError();
        }

        $matchPartial = $tag->getMatchPartial();
        $matchPartial->TagSlug = $tag->getTagSlug();

        // save the tags
        $this->NodeTagsDAO->saveInTags($db, $nodeRef, $id, $matchPartial->toString(), array($tag));
    }

    public function removeInTag(NodeRef $nodeRef, Tag $tag)
    {
        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $tag);

        $id = $tag->getTagRole();
        $schema = $nodeRef->getElement()->getSchema();
        $tagDef = $schema->getTagDef($id);

        $tag = TagUtils::filterTagAgainstDef($tag, $tagDef);

        // retrieve the DB connection and table from DataSourceManager
        $db = $this->getConnectionForWrite($nodeRef);

        $id = $this->getRecordIDFromNodeRef($nodeRef);

        $matchPartial = $tag->getMatchPartial();
        $matchPartial->TagSlug = $tag->getTagSlug();

        // save the tags
        $this->NodeTagsDAO->saveInTags($db, $nodeRef, $id, $matchPartial->toString(), array());
    }

    public function copy(NodeRef $nodeRef, Element $newNodeElement)
    {
        $node = $this->NodeLookupDAO->getByNodeRef($nodeRef, new NodePartials('all', 'all', 'all'), true);

        $newNodeRef = new NodeRef($newNodeElement, $node->Slug);
        $newNode = $newNodeRef->generateNode();

        $cFields = array_intersect_key($node->toArray(),array_flip($this->NodeDBMeta->getPersistentFields()));

        $newNode->setFromArray($cFields);
        $newNode->setNodePartials($node->getNodePartials());

        $schema = $newNodeElement->getSchema();
        foreach($schema->getTagDefs() as $tagDef) {
//            echo "Tag [{$tagDef->Direction}]: {$tagDef->Id}\n";
            if($tagDef->Direction == 'in')
                $newNode->replaceInTags($tagDef->Id, $node->getInTags($tagDef->Id));
            else
                $newNode->replaceOutTags($tagDef->Id, $node->getOutTags($tagDef->Id));
        }

        foreach($schema->getMetaDefs() as $metaDef)
            $newNode->updateMeta($metaDef->Id, $node->getMetaValue($metaDef->Id));

        return $this->add($newNode);
    }



    /**
     * This only supports inserting nodes at the end of the tree or at the end of a list of children to a parent,
     * which inherently means the 2nd criteria to the tree beyond parent is creation date
     *
     * @param $node
     * @return unknown_type
     */
    protected function insertIntoTree(&$node, $existingNode = null)
    {

        $treeOriginTagDef = $node->getNodeRef()->getElement()->getSchema()->getTreeOriginTagDef();

        if(empty($treeOriginTagDef))
            throw new NodeException('Cannot insert a node in tree order mode without a tree origin tag definition');

        if($treeOriginTagDef->isMultiple())
            throw new NodeException('Cannot have more than 1 tree origin tag');

        if($treeOriginTagDef->Direction == 'out')
            $treeOrigin = $node->getOutTag($treeOriginTagDef->Id);
        else
            $treeOrigin = $node->getInTag($treeOriginTagDef->Id);

        if(empty($treeOrigin))
            throw new NodeException('Cannot insert a node into the tree without the tree origin tag');

        $treeOriginPartial = $treeOrigin->toPartial();

        $parentTreeID = '';
        if($node->getTreeParent() != false)
        {
            $parentNode = $this->NodeLookupDAO->getByNodeRef(new NodeRef($node->getNodeRef()->getElement(), $node->getTreeParent()));

            $parentTreeID = $parentNode->TreeID;
        }

        // fetch max tree id (if parentTreeID is blank, return last top-level tree id)
        $maxChildTreeID = $this->fetchMaxChildTreeID($node, $treeOriginTagDef, $treeOriginPartial, $parentTreeID);

        if(!is_null($existingNode) && $maxChildTreeID == $existingNode->TreeID)
        {
            $node->TreeID = $existingNode->TreeID;
            return;
        }

        if(empty($maxChildTreeID))
        {
            $newTreeID = $parentTreeID . '0001';

        // add this to the children
        } else {

            $last4 = substr($maxChildTreeID, -4);
            if(strtolower($last4) == 'ffff')
                throw new NodeException('This tree leaf has reached the limit of its children');

            $newTreeID = $parentTreeID . str_pad(dechex(hexdec($last4)+1), 4, '0', STR_PAD_LEFT);
        }

        while(!$this->Locks->getLock('treeid:'.$treeOriginPartial.':'.$newTreeID, 0)) {

            $last4 = substr($newTreeID, -4);
            if(strtolower($last4) == 'ffff')
                throw new NodeException('This tree leaf has reached the limit of its children');

            $newTreeID = $parentTreeID . str_pad(dechex(hexdec($last4)+1), 4, '0', STR_PAD_LEFT);

        }

        $node->TreeID = $newTreeID;
    }

    protected function fetchMaxChildTreeID(Node $myNode, TagDef $treeOriginTagDef, TagPartial $treeOriginPartial, $parentTreeID = '')
    {

        $nq = new NodeQuery();
        $nq->setParameter('Status.all', true);
        $nq->setParameter('TreeID.childOf', $parentTreeID);
        $nq->setParameter('Elements.in', $myNode->getNodeRef()->getElement()->getSlug());

        if($treeOriginTagDef->Direction == 'out')
            $nq->setParameter('OutTags.exist', $treeOriginPartial->toString());
        else
            $nq->setParameter('InTags.exist', $treeOriginPartial->toString());

        $nq->setOrderBy('TreeID', 'DESC');
        $nq->setLimit(1);

        $result = $this->NodeFindAllDAO->findAll($nq, true)->getResult();

        if(!empty($result))
        {
            $lastNodeTreeID = $result['TreeID'];

            // if on the same level, return blank, in order to append as first child
            if(strcmp($lastNodeTreeID, $parentTreeID) === 0) {
//                error_log('No children, same level');
                return '';
            } else {
                if(empty($parentTreeID))
                    return substr($lastNodeTreeID, 0, 4);
            }

//            error_log('Last TreeID = '.$lastNodeTreeID);
            return $lastNodeTreeID;
        }

        return '';
    }


    protected function addInternal(Node $node)
    {
        $this->Logger->debug('Adding node ['.$node->getNodeRef().'] with partials ['.$node->getNodePartials().']');

        // write the row to the database
        $primaryKey = $this->NodeDBMeta->getPrimaryKey($node->getNodeRef());
        unset($node->{$primaryKey});

        $db = $this->getConnectionForWrite($node->getNodeRef());
        $table = $db->quoteIdentifier($this->NodeDBMeta->getTableName($node->getNodeRef()));

        if(!$this->Locks->getLock('insert:'.$node->getNodeRef(), 0))
            throw new DuplicateNodeException('Node ['.$node->getNodeRef().'] is currently being created');

        // purge any previously deleted nodes with this ref
        $this->purgeInternal($node->getNodeRef());

        $id = $db->insertRecord($table, $this->NodeMapper->nodeToPersistentArray($node));

//        sleep(15);

        $node->{$primaryKey} = $id;
        $node->ID = $id;

        // save meta
        $changedMeta = $this->NodeMetaDAO->saveMeta($db, $node->getNodeRef(), $id, $node->getNodePartials()->getMetaPartials(), $node->getMetas(), $node->getNodePartials()->getRestrictedMetaPartials());

        // save the sections
        //$this->NodeSectionsDAO->saveSections($db, $node->getNodeRef(), $id, $node->getNodePartials()->getSectionPartials(), $node->getSections());

        // save the tags
        $changedOut = $this->NodeTagsDAO->saveOutTags($db, $node->getNodeRef(), $id, $node->getNodePartials()->getOutPartials(), $node->getOutTags(), $node->getNodePartials()->getRestrictedOutPartials());
        $changedIn = $this->NodeTagsDAO->saveInTags($db, $node->getNodeRef(), $id, $node->getNodePartials()->getInPartials(), $node->getInTags(), $node->getNodePartials()->getRestrictedInPartials());

    }

    protected function editInternal(Node $node)
    {
        $existingNode = $this->NodeLookupDAO->getByNodeRef($node->getNodeRef());

        $this->Logger->debug('Editing node ['.$node->getNodeRef().'] with partials ['.$node->getNodePartials().']');

        // update the record
        $primaryKey = $this->NodeDBMeta->getPrimaryKey($node->getNodeRef());
        $id = $existingNode->getID();

        $node->{$primaryKey} = $id;
        $node->ID = $id;

        $db = $this->getConnectionForWrite($node->getNodeRef());

        $oldArray = $this->NodeMapper->nodeToPersistentArray($existingNode);
        $newArray = $this->NodeMapper->nodeToPersistentArray($node);

        // save meta
        $changedMeta = $this->NodeMetaDAO->saveMeta($db, $node->getNodeRef(), $id, $node->getNodePartials()->getMetaPartials(), $node->getMetas(), $node->getNodePartials()->getRestrictedMetaPartials());

        // save the tags
        $changedOut = $this->NodeTagsDAO->saveOutTags($db, $node->getNodeRef(), $id, $node->getNodePartials()->getOutPartials(), $node->getOutTags(), $node->getNodePartials()->getRestrictedOutPartials());
        $changedIn = $this->NodeTagsDAO->saveInTags($db, $node->getNodeRef(), $id, $node->getNodePartials()->getInPartials(), $node->getInTags(), $node->getNodePartials()->getRestrictedInPartials());

        if(array_intersect_key($oldArray, $newArray) != $newArray)
        {
            $newArray['ModifiedDate'] = $this->DateFactory->newStorageDate();
            $node->ModifiedDate = $newArray['ModifiedDate'];

            $db->updateRecord($db->quoteIdentifier($this->NodeDBMeta->getTableName($node->getNodeRef())), $newArray,
                "{$primaryKey} = {$db->quote($id)}");
        }

    }

    protected function deleteInternal(NodeRef $nodeRef)
    {

        // flag the record as deleted
        $now = $this->DateFactory->newStorageDate();
        $db = $this->getConnectionForWrite($nodeRef);

        $this->NodeTagsDAO->fireRemoveOutTagEvents($nodeRef);
        $this->NodeTagsDAO->fireRemoveInTagEvents($nodeRef);
        $this->NodeMetaDAO->fireRemoveMetaEvents($nodeRef);

        $sql = "UPDATE {$db->quoteIdentifier($this->NodeDBMeta->getTableName($nodeRef))}
            SET Status = {$db->quote('deleted')},
                ModifiedDate = {$db->quote($now)}
            WHERE Slug = {$db->quote($nodeRef->getSlug())}";
        $db->write($sql);

    }

    protected function undeleteInternal(NodeRef $nodeRef)
    {

        // retrieve the DB connection and table from DataSourceManager
        $now = $this->DateFactory->newStorageDate();
        $db = $this->getConnectionForWrite($nodeRef);

        $this->NodeTagsDAO->fireAddOutTagEvents($nodeRef);
        $this->NodeTagsDAO->fireAddInTagEvents($nodeRef);
        $this->NodeMetaDAO->fireAddMetaEvents($nodeRef);

        // update its status to draft
        $sql = "UPDATE {$db->quoteIdentifier($this->NodeDBMeta->getTableName($nodeRef))}
            SET Status = {$db->quote('draft')},
                ModifiedDate = {$db->quote($now)}
            WHERE Slug = {$db->quote($nodeRef->getSlug())}";
        $db->write($sql);

    }

    protected function purgeInternal (NodeRef $nodeRef) {

        // retrieve the DB connection and table from DataSourceManager
        $db = $this->getConnectionForWrite($nodeRef);

        $primaryKey = $this->NodeDBMeta->getPrimaryKey($nodeRef);

        // determine if node exists at urls
        $sql = "SELECT {$primaryKey}
                FROM {$db->quoteIdentifier($this->NodeDBMeta->getTableName($nodeRef))}
                WHERE Slug = {$db->quote($nodeRef->getSlug())}
                    AND Status = 'deleted'";

        $deletedID = $db->readField($sql);

        if(!empty($deletedID)) {

            $currentlyEnabled = $this->NodeEvents->areEventsEnabled();

            $this->NodeEvents->disableEvents();

            // delete meta
            $this->NodeMetaDAO->saveMeta($db, $nodeRef, $deletedID, 'all', array());

            // delete tags
            $this->NodeTagsDAO->saveOutTags($db, $nodeRef, $deletedID, 'all', array());
            $this->NodeTagsDAO->saveInTags($db, $nodeRef, $deletedID, 'all', array());

            $db->deleteRecord($db->quoteIdentifier($this->NodeDBMeta->getTableName($nodeRef)), "{$primaryKey} = {$db->quote($deletedID)}");

            $this->NodeCache->deleteNode($nodeRef);

            if($currentlyEnabled)
                $this->NodeEvents->enableEvents();

        }
    }


}

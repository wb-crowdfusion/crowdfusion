<?php
/**
 * NodeLookupDAO
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
 * @version     $Id: NodeLookupDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeLookupDAO
 *
 * @package     CrowdFusion
 */
class NodeLookupDAO extends AbstractNodeDAO
{
    public function setNodeTagsDAO(NodeTagsDAO $NodeTagsDAO)
    {
        $this->NodeTagsDAO = $NodeTagsDAO;
    }

    public function setNodeMetaDAO(NodeMetaDAO $NodeMetaDAO)
    {
        $this->NodeMetaDAO = $NodeMetaDAO;
    }
    /**
     * @var NodeEvents
     */
    protected $NodeEvents;
    /**
    * [IoC]
    * @param NodeEvents $NodeEvents
    */
    public function setNodeEvents(NodeEvents $NodeEvents){
        $this->NodeEvents = $NodeEvents;
    }

//    public function setNodeSectionsDAO(NodeSectionsDAO $NodeSectionsDAO)
//    {
//        $this->NodeSectionsDAO = $NodeSectionsDAO;
//    }


    public function refExists(NodeRef $nodeRef)
    {
        if(!$nodeRef->isFullyQualified())
            throw new NodeException('Cannot check node exists without fully-qualified NodeRef');

        // retrieve the DB connection and table from DataSourceManager
        $db = $this->getConnectionForWrite($nodeRef);

        // determine if node exists at url, excluding deleted nodes
        $sql = "SELECT {$this->NodeDBMeta->getPrimaryKey($nodeRef)}
                FROM {$db->quoteIdentifier($this->NodeDBMeta->getTableName($nodeRef))}
                WHERE Slug = {$db->quote($nodeRef->getSlug())}
                    AND Status != 'deleted'";

        $unique = $db->readOne($sql);

        return !empty($unique);

    }

    public function getByNodeRef(NodeRef $nodeRef, NodePartials $nodePartials = null, $forceReadWrite = true, $checkJumpPermissions = false)
    {
        if(is_null($nodeRef))
            throw new NodeException('Cannot retrieve node without a NodeRef');

        if(!$nodeRef->isFullyQualified())
            throw new NodeException('Cannot retrieve node without fully-qualified NodeRef');

        if($checkJumpPermissions && !$this->NodePermissions->check('get', $nodeRef, $nodePartials, true))
            return null;

        $this->Logger->debug('Getting node ['.$nodeRef.'] with partials ['.$nodePartials.']');

        $this->NodeEvents->fireNodeEvents('get', '', $nodeRef, $nodePartials);

        $db = $forceReadWrite?$this->getConnectionForWrite($nodeRef):$this->getConnectionForRead($nodeRef);
        $tableid = $this->NodeDBMeta->getPrimaryKey($nodeRef);
        $table = $this->NodeDBMeta->getTableName($nodeRef);

        $rows = $this->multiGetFromDB($db, $tableid, $table, $nodeRef, $nodeRef->getSlug(), false, $forceReadWrite, true);

        if(empty($rows))
            return null; //throw new Exception('Node not found for NodeRef: '.$nodeRef);

        $row = current($rows);

        $idField = 'ID';
        $id = $row[$tableid];

        if(!empty($nodePartials))
        {
            $row->setNodePartials($nodePartials);

            $outtags = $this->NodeTagsDAO->findOutTags($db, $nodeRef, $id, $nodePartials->getOutPartials(), $forceReadWrite, $checkJumpPermissions, $nodePartials->getRestrictedOutPartials(), $nodePartials->isResolveLinks());
            $intags = $this->NodeTagsDAO->findInTags($db, $nodeRef, $id, $nodePartials->getInPartials(), $forceReadWrite, $checkJumpPermissions, $nodePartials->getRestrictedInPartials(), $nodePartials->isResolveLinks());
            $meta = $this->NodeMetaDAO->findMeta($db, $nodeRef, $id, $nodePartials->getMetaPartials(), $forceReadWrite, $nodePartials->getRestrictedMetaPartials());

            $row->setMetas( $meta );
            $row->setOutTags( $outtags );
            $row->setInTags( $intags );
        }

        return $row;
    }

    /**
     * @param NodeRef $nodeRef
     * @param null $title
     * @param bool $useTime
     * @return NodeRef
     * @throws NodeException
     * @events slugUniquing validation event with $slugTransporter as param2
     */
    public function generateUniqueNodeRef(NodeRef $nodeRef, $title = null, $useTime = false)
    {

        $slug = $nodeRef->getSlug();
        $slugTransporter = new Transport();
        $slugTransporter->Slug = $slug;

        $errors = new Errors();
        $this->NodeEvents->fireValidationEvents('slugUniquing', $errors, $nodeRef, $slugTransporter);

        $slug = $slugTransporter->Slug;
        $nodeRef = new NodeRef($nodeRef->getElement(),$slug);

        $errors->throwOnError();

        if (empty($slug)) {
            if(empty($title))
                throw new NodeException('Cannot generate unique NodeRef without title');
            if($useTime)
                $slug = SlugUtils::createSlug(substr($title, 0, 237).'-'.(floor(microtime(true)*100)),$nodeRef->getElement()->isAllowSlugSlashes());
            else
                $slug = SlugUtils::createSlug(substr($title, 0, 255),$nodeRef->getElement()->isAllowSlugSlashes());
            $nodeRef = new NodeRef($nodeRef->getElement(), $slug);
        } else {
            if($useTime)
                $title = substr($slug, 0, 237).'-'.(floor(microtime(true)*100));  // makes 12 digits (127213194347)
            else
                $title = substr($slug, 0, 255);

            $nodeRef = new NodeRef($nodeRef->getElement(), SlugUtils::createSlug($title,$nodeRef->getElement()->isAllowSlugSlashes()));
        }

        $i = 0;
        while ($this->refExists($nodeRef)) { // Generate a unique slug
            $slug = SlugUtils::createSlug(substr($title, 0, 250) . " " . ++$i,$nodeRef->getElement()->isAllowSlugSlashes());
            $nodeRef = new NodeRef($nodeRef->getElement(), $slug);
        }

        return $nodeRef;
    }

    public function resolveLinkedNodes(Node $node, NodePartials $nodePartials = null, $forceReadWrite = true, $checkJumpPermissions = false)
    {
        $node->OutTags = $this->NodeTagsDAO->findOutTags(null, $node->getNodeRef(), null, $nodePartials->getOutPartials(), $forceReadWrite, $checkJumpPermissions, $nodePartials->getRestrictedOutPartials(), true, $node->OutTags);
        $node->InTags = $this->NodeTagsDAO->findInTags(null, $node->getNodeRef(), null, $nodePartials->getInPartials(), $forceReadWrite, $checkJumpPermissions, $nodePartials->getRestrictedInPartials(), true, $node->InTags);

        return $node;
    }

}

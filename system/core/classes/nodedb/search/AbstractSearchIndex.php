<?php
/**
 * AbstractSearchIndex
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
 * @version     $Id: AbstractSearchIndex.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractSearchIndex
 *
 * @package     CrowdFusion
 */
abstract class AbstractSearchIndex
{
    protected $boundReindex = false;

    protected $Events;
    protected $DateFactory;
    protected $Logger;

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    public function setNodeService(NodeService $NodeService)
    {
        $this->NodeService = $NodeService;
    }


    protected $markedForReindex = array();
    protected $markedForDeletion = array();


    public abstract function reindexAll();

    public function getElements()
    {
        return $this->Elements;
    }

    // reindex

    public function reindex(NodeRef $nodeRef)
    {
        if(!$nodeRef->isFullyQualified())
            throw new Exception('Cannot reindex node without fully-qualified NodeRef');

        if(substr($this->Elements, 0, 1) == '@') {
            if(!$nodeRef->getElement()->hasAspect($this->Elements))
                return;
        } else if(!in_array($nodeRef->getElement()->getSlug(), StringUtils::smartExplode($this->Elements)))
                return;

        if(array_key_exists(''.$nodeRef, $this->markedForDeletion))
            unset($this->markedForDeletion[''.$nodeRef]);

        if(array_key_exists(''.$nodeRef, $this->markedForReindex))
            return;

        $this->markedForReindex[''.$nodeRef] = $nodeRef;
        $this->bindReindex();
    }

    public function reindexRename(NodeRef $oldNodeRef, NodeRef $nodeRef)
    {
        $this->delete($oldNodeRef);
        $this->reindex($nodeRef);
    }

    public function delete(NodeRef $nodeRef)
    {
        if(!$nodeRef->isFullyQualified())
            throw new Exception('Cannot delete node from index without fully-qualified NodeRef');

        if(substr($this->Elements, 0, 1) == '@') {
            if(!$nodeRef->getElement()->hasAspect($this->Elements))
                return;
        } else if(!in_array($nodeRef->getElement()->getSlug(), StringUtils::smartExplode($this->Elements)))
                return;

        if(array_key_exists(''.$nodeRef, $this->markedForReindex))
            unset($this->markedForReindex[''.$nodeRef]);

        if(array_key_exists(''.$nodeRef, $this->markedForDeletion))
            return;

        $this->markedForDeletion[''.$nodeRef] = $nodeRef;

        $this->bindReindex();
    }

    protected function bindReindex()
    {
        if(!$this->boundReindex) {
            $this->Events->bindEvent('TransactionManager.commit', $this, 'reindexAll', 1);
            $this->boundReindex = true;
        }
    }

}
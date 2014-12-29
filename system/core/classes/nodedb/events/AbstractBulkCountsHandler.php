<?php
/**
 * AbstractBulkCountsHandler
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
 * @version     $Id: AbstractCountsHandler.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractCountsHandler
 *
 * @package     CrowdFusion
 */
abstract class AbstractBulkCountsHandler extends AbstractCountsHandler
{
    protected $NodeCounts = array();

    /**
     * Update all meta on nodes queued
     *
     * @return void
     */
    public function commit() {
        if(empty($this->NodeCounts)) {
            $this->Logger->debug(__FUNCTION__ .'-> No nodes to change counts on.');
            return;
        }

        foreach($this->NodeCounts as $nodeRefUrl => $count) {
            $nodeRef = $this->NodeRefService->parseFromString($nodeRefUrl);

            // incrementMeta can handle negative values, so no need to call decrementMeta here
            if($count != 0) {
                $this->Logger->debug(__FUNCTION__ ."-> Updating ".$this->getMetaID()." by {$count} for: $nodeRefUrl");
                $this->NodeService->incrementMeta($nodeRef, $this->getMetaID(), $count);
            }
            else
                $this->Logger->debug(__FUNCTION__ ."-> No update of ".$this->getMetaID()." for: $nodeRefUrl");
        }

        $this->NodeCounts = array();
    }

    /**
     * Queue a node to have its meta incremented
     *
     * @param NodeRef $nodeRef
     * @param NodeRef $extNodeRef
     * @param $tag
     */
    public function increment(NodeRef $nodeRef, NodeRef $extNodeRef, $tag)
    {
        $nodeRefUrl = $nodeRef->getRefURL();
        if(!isset($this->NodeCounts[$nodeRefUrl]))
            $this->NodeCounts[$nodeRefUrl] = 0;

        $this->NodeCounts[$nodeRefUrl]++;

        $this->Logger->debug(__FUNCTION__ ."-> Increment ".$this->getMetaID()." for: $nodeRefUrl");
    }

    /**
     * Queue a node to have its meta decremented
     *
     * @param NodeRef $nodeRef
     * @param NodeRef $extNodeRef
     * @param $tag
     */
    public function decrement(NodeRef $nodeRef, NodeRef $extNodeRef, $tag)
    {
        $nodeRefUrl = $nodeRef->getRefURL();
        if(!isset($this->NodeCounts[$nodeRefUrl]))
            $this->NodeCounts[$nodeRefUrl] = 0;

        $this->NodeCounts[$nodeRefUrl]--;

        $this->Logger->debug(__FUNCTION__ ."-> Decrement ".$this->getMetaID()." for: $nodeRefUrl");
    }
}
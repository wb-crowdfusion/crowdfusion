<?php
/**
 * AbstractCountsHandler
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
abstract class AbstractCountsHandler
{
    protected $NodeService;
    public function setNodeService($NodeService)
    {
        $this->NodeService = $NodeService;
    }

    protected $NodeRefService;
    public function setNodeRefService($NodeRefService)
    {
        $this->NodeRefService = $NodeRefService;
    }

    protected $RequestContext;
    public function setRequestContext(RequestContext $RequestContext)
    {
        $this->RequestContext = $RequestContext;
    }

    protected $Logger;
    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

    protected abstract function getMetaID();

    public function increment(NodeRef $nodeRef, NodeRef $extNodeRef, $tag)
    {
        $this->NodeService->incrementMeta($nodeRef, $this->getMetaID());
    }

    public function decrement(NodeRef $nodeRef, NodeRef $extNodeRef, $tag)
    {
        $this->NodeService->decrementMeta($nodeRef, $this->getMetaID());
    }

    public function denyWrite(Transport &$permitted, NodeRef $nodeRef, &$node = null)
    {
        if(!$this->RequestContext->getControls()->isActionExecuting())
            return;

        $permitted->CachePermission = false;

        if(is_string($node)) {
            if(ltrim(strtolower($node), '#') == ltrim(strtolower($this->getMetaID()), '#'))
                $permitted->Permitted = false;
        } else if(!is_null($node) && $node instanceOf Node) {
            $node->getNodePartials()->increaseRestrictedMetaPartials($this->getMetaID());
        }
    }

}
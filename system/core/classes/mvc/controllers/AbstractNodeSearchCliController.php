<?php
/**
 * AbstractNodeSearchCliController.php
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
 * @version     $Id$
 */

/**
 * AbstractNodeSearchCliController.php
 *
 * @package     CrowdFusion
 */
class AbstractNodeSearchCliController extends AbstractCliController {
    

    public function setNodeService(NodeService $NodeService)
    {
        $this->NodeService = $NodeService;
    }

    protected function reindexAll()
    {
        $nq = new NodeQuery();
        $nq->setParameter('Elements.in', $this->Index->getElements());
        $nq->setParameter('NodeRefs.only', true);

        $all = $this->NodeService->findAll($nq)->getResults();

        foreach($all as $nodeRef)
            $this->Index->reindex($nodeRef);

    }

}

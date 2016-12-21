<?php
/**
 * BulkdeleteCmsController
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
 * @version     $Id: BulkdeleteCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * BulkdeleteCmsController
 *
 * @package     CrowdFusion
 */
class BulkdeleteCmsController extends AbstractBulkActionController
{
    public function execute() {

        $this->_beginBulkaction();

        $noderefs = $this->_getNodeRefs();

        foreach($noderefs as $noderef) {

            try {

                $this->RegulatedNodeService->delete($noderef);
                $this->_updateBulkaction($noderef->getElement()->Slug,$noderef->Slug);

            } catch(Exception $e) {
                $this->_failureBulkaction($e->getMessage(),$noderef->getElement()->Slug,$noderef->Slug);
            }
        }

        return $this->_endBulkaction();
    }

}
<?php
/**
 * BulkaddtagsCmsController
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
 * BulkaddtagsCmsController
 *
 * @package     CrowdFusion
 */
class BulkaddtagsCmsController extends AbstractBulkActionController
{
    public function execute() {

        $this->_beginBulkaction();

        $noderefs = $this->_getNodeRefs();

        $tags = $this->Request->getParameter('Tags');

        $tagStrings = array();

        if(!empty($tags))
            $tagStrings = StringUtils::smartSplit($tags);

        foreach($noderefs as $noderef) {

            try {

                foreach($tagStrings as $tagString) {
                    $this->RegulatedNodeService->addOutTag($noderef,new Tag($tagString));
                }

                $this->_updateBulkaction($noderef->getElement()->Slug,$noderef->Slug);
            } catch(Exception $e) {
                $this->_failureBulkaction($e->getMessage(),$noderef->getElement()->Slug,$noderef->Slug);
            }
        }

        return $this->_endBulkaction();
    }

}
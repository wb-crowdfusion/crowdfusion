<?php
/**
 * Abstract methods for CMS bulk action controllers including progress and failure handling.
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
 * @version     $Id: AbstractBulkActionController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Abstract methods for CMS bulk action controllers including progress and failure handling.
 *
 * @package     CrowdFusion
 */
abstract class AbstractBulkActionController extends AbstractCmsController
{
    protected $Response;
    protected $ElementService;

    public function setResponse(Response $Response)
    {
        $this->Response = $Response;
    }

    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    protected function _getNodeRefs() {

        $noderefs = $this->Request->getParameter('NodeRefs');

        if(!empty($noderefs) && strlen($noderefs) > 0) {
            $noderefs = StringUtils::smartSplit($noderefs,',');

            $noderefObjs = array();

            foreach($noderefs as $noderef) {
                $nr = $this->NodeRefService->parseFromString($noderef);
                if($nr != null)
                    $noderefObjs[] = $nr;
            }

            return $noderefObjs;
        } else
            return array();
    }

    protected function _beginBulkaction() {

        $this->Response->clean();
        echo '<html><head></head><body>';
    }

    protected function _endBulkaction() {

        echo '</body></html>';

        flush();

        //No View
        return null;
    }

    protected function _updateBulkaction($element,$slug) {

        $json = array(
            'Element' => $element,
            'Slug' => $slug
        );

        $json = JSONUtils::encode($json);

        $s = '<script type="text/javascript" language="JavaScript">parent.BulkActionToolbar.updateProgress(true,'.$json.');</script>';
        echo $s;
        flush();
        usleep(30000);
    }

    protected function _failureBulkaction($msg,$element,$slug) {

        $json = array(
            'Message' => $msg,
            'Element' => $element,
            'Slug' => $slug
        );

        $json = JSONUtils::encode($json);

        $s = "<script type=\"text/javascript\" language=\"JavaScript\">parent.BulkActionToolbar.updateProgress(false,".$json.");</script>\n";
        echo $s;
        flush();
        usleep(10000);
    }

}
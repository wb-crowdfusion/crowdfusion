<?php
/**
 * Single and Items interface for the CMS to query the Node Database.
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
 * @version     $Id: AspectsCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Single and Items interface for the CMS to query the Node Database.
 *
 * @package     CrowdFusion
 */
class AspectsCmsController extends AbstractCmsController
{

    protected $AspectService = null;
    protected $ElementService = null;
    protected $PluginService = null;

    public function setPluginService(PluginService $PluginService)
    {
        $this->PluginService = $PluginService;
    }

    public function setAspectService(AspectService $AspectService)
    {
        $this->AspectService = $AspectService;
    }

    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function single()
    {
        $this->checkPermission('aspects-list');

        $slug = $this->Request->getParameter('Slug');
        $id = $this->Request->getParameter('AspectID');
        if(empty($id))
            $id = $this->Request->getParameter('id');

        $aspect = null;

        if(!empty($slug) && $this->AspectService->slugExists($slug)) {
            $aspect = $this->AspectService->getBySlug($slug);
        } else if(!empty($id)) {
            $aspect = $this->AspectService->getByID($id);
        }

        if(!empty($aspect)) {

            if($this->getTemplateVariable('IncludeElements') == 'true') {
                $aspect['Elements'] = $this->ElementService->findAllWithAspect($aspect->Slug);
            }

            if(!empty($aspect->PluginID) && $this->getTemplateVariable('IncludePlugin') == 'true') {
                $aspect['Plugin'] = $this->PluginService->getByID($aspect->PluginID);
            }

            return array($aspect);
        }

        return array();
    }

    public function items()
    {
        //$this->checkPermission('aspects-list');

        $dto = new DTO();

        $this->buildSorts($dto);
        $this->buildLimitOffset($dto);
        $this->buildFilters($dto);

        $dto->setLimit(null);

        $data = $this->AspectService->findAll($dto)->getResults();

        if(count($data) > 0)
            $data[0]['TotalRecords'] = count($data);

        return $data;
    }

    public function edit()
    {
        return new View($this->formView());
    }
}
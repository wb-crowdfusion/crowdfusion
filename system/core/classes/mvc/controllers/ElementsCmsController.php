<?php
/**
 * ElementsCmsController
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
 * @version     $Id: ElementsCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ElementsCmsController
 *
 * @package     CrowdFusion
 */
class ElementsCmsController extends AbstractCmsController
{

    protected $ElementService = null;
    protected $SiteService = null;
    protected $AspectService = null;
    protected $NodeService = null;

    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function setSiteService(SiteService $SiteService)
    {
        $this->SiteService = $SiteService;
    }

    public function setAspectService(AspectService $AspectService)
    {
        $this->AspectService = $AspectService;
    }

    public function setNodeService(NodeService $NodeService)
    {
        $this->NodeService = $NodeService;
    }

    protected function bindAspectsToElement(Element &$obj)
    {
        $aspectIds = $this->Request->getParameter('Aspects');

        $aspects = array();
        foreach((array)$aspectIds as $aspectId) {
            $aspect = $this->AspectService->getByID($aspectId);
            $aspects[] = $aspect;
        }
        $obj->setAspects($aspects);

        $siteSlug = $this->Request->getParameter('AnchoredSiteSlug');

        $obj->setAnchoredSiteSlug($siteSlug);
        $obj->setAnchoredSiteSlugOverride($siteSlug);

        $obj->setAnchoredSite($this->SiteService->getBySlug($siteSlug));
    }

    public function add()
    {
        $this->checkPermission('elements-add');

        if(!$this->buttonPushed()) /* default flow */ {

            $obj = new Element();

            $this->ModelMapper->defaultsOnModel($obj);

            $this->bindToActionDatasource(array($obj));

            return new View($this->formView());

            //*** at this point the only subsequent actions will result from button presses
        }

        if($this->cancelButtonPushed()) {

            //redirect to list (action_cancel_view)
            return new View($this->cancelView());
        }

        if($this->saveButtonPushed() || $this->saveAndContinueButtonPushed()) {



            try {
                $this->checkNonce();

                $obj = new Element();

                $this->ModelMapper->defaultsOnModel($obj);

                //bind posted params to form backing object
                $this->ModelMapper->inputArrayToModel($this->Request->getParameters(),$this->Request->getRawParameters(),$obj,$this->errors);

                $this->bindAspectsToElement($obj);

                $this->errors->throwOnError();

                $this->ElementService->add($obj);

                $element = $this->ElementService->getBySlug($obj->Slug); //refresh schema
                $this->NodeService->createDBSchema($element);

            } catch(ValidationException $ve) {
                //bind form backing object to datasource
                $this->bindToActionDatasource(array($obj));

                //re-throw validationexception, which shows form view
                throw $ve;
            }

            $this->Session->setFlashAttribute('saved', true);

            if($this->saveButtonPushed())
                //redirect to list (action_success_view)
                return new View($this->successView(true));

            else if($this->saveAndContinueButtonPushed())
                //redirect to edit (action_continue_view)
                return new View($this->continueView(true), array('id'=> $obj->ElementID));
        }

        throw new Exception("unsupported button for this action");
    }

    public function delete()
    {
        $this->checkPermission('elements-delete');

        if($this->cancelButtonPushed()) {

            return new View($this->continueView(true));
        }

        if($this->saveButtonPushed()) {

            $obj = $this->ElementService->getByID($this->Request->getParameter('id'));

            $this->checkNonce();

            $this->ElementService->delete($obj->Slug);

            $this->NodeService->dropDBSchema($obj);

            return new View($this->successView(true));
        }

    }

    protected function elementHasRecords($slug)
    {
        $nq = new NodeQuery();

        $nq->setParameter('Elements.in', $slug);
//        $nq->setParameter('Status.all', true);
        $nq->setLimit(1);

        $count = $this->NodeService->findAll($nq)->getResult();

        return $count !== null;
    }

    public function duplicate()
    {
        $this->checkPermission('elements-add');

        if(!$this->buttonPushed()) /* default flow */ {

            $obj = $this->ElementService->getByID($this->Request->getParameter('id'));

            $obj = clone($obj);

            $this->bindToActionDatasource(array($obj));

            return new View($this->formView());

            //*** at this point the only subsequent actions will result from button presses
        }

        if($this->cancelButtonPushed()) {

            //redirect to list (action_cancel_view)
            return new View($this->cancelView());
        }

        if($this->saveButtonPushed() || $this->saveAndContinueButtonPushed()) {


            $obj = new Element();

            $this->ModelMapper->defaultsOnModel($obj);

            //bind posted params to form backing object
            $this->ModelMapper->inputArrayToModel($this->Request->getParameters(),$this->Request->getRawParameters(),$obj,$this->errors);

            $this->bindAspectsToElement($obj);

            try {
                $this->checkNonce();

                $obj->PluginID = 0;

                $this->ElementService->add($obj);

                $element = $this->ElementService->getBySlug($obj->Slug); //refresh schema
                $this->NodeService->createDBSchema($element);

            } catch(ValidationException $ve) {
                //bind form backing object to datasource
                $this->bindToActionDatasource(array($obj));

                //re-throw validationexception, which shows form view
                throw $ve;
            }

            $this->Session->setFlashAttribute('saved', true);

            if($this->saveButtonPushed())
                //redirect to list (action_success_view)
                return new View($this->successView(true));

            else if($this->saveAndContinueButtonPushed())
                //redirect to edit (action_continue_view)
                return new View($this->continueView(true), array('newid'=> $obj->ElementID));
        }

        throw new Exception("unsupported button for this action");
    }

    public function edit()
    {
        $this->checkPermission('elements-edit');

        if(!$this->buttonPushed()) /* default flow */ {

            return new View($this->formView());

            //*** at this point the only subsequent actions will result from button presses
        }

        if($this->cancelButtonPushed()) {

            return new View($this->cancelView());
        }

        if($this->deleteConfirmButtonPushed()) {

            $obj = $this->ElementService->getByID($this->Request->getParameter('id'));
            if($this->elementHasRecords($obj->Slug))
                $this->errors->addGlobalError('delete', "This element cannot be deleted. Please manually remove all data records for this element and try again.")->throwOnError();

            return new View($this->deleteConfirmView());
        }

        if($this->saveButtonPushed() || $this->saveAndContinueButtonPushed()) {

            $obj = $this->ElementService->getByID($this->Request->getParameter('id'));

            $slug = $obj->Slug;

            $obj = clone($obj);

            //bind posted params to form backing object
            $this->ModelMapper->inputArrayToModel($this->Request->getParameters(),$this->Request->getRawParameters(),$obj,$this->errors);

            //$obj->ModifiedDate = $this->DateFactory->newStorageDate();

            $this->bindAspectsToElement($obj);

            try {
                $this->checkNonce();

                $obj->PluginID = 0;

                $this->ElementService->edit($obj);

                $element = $this->ElementService->getBySlug($obj->Slug); //refresh schema
                $this->NodeService->createDBSchema($element);

            } catch(ValidationException $ve) {
                //bind form backing object to datasource
                if($this->elementHasRecords($slug))
                    $obj['HasRecords'] = true;
                $this->bindToActionDatasource(array($obj));

                //re-throw validationexception, which shows form view
                throw $ve;
            }

            $this->Session->setFlashAttribute('saved', true);

            if($this->saveButtonPushed())
                return new View($this->successView(true));

            else if($this->saveAndContinueButtonPushed())
                return new View($this->continueView(true));
        }

        throw new Exception("unsupported button for this action");
    }

    public function single()
    {
        $this->checkPermission('elements-list');

        $slug = $this->Request->getParameter('Slug');
        $id = $this->Request->getParameter('ElementID');
        if(empty($id))
            $id = $this->Request->getParameter('id');

        $element = null;

        if(!empty($slug) && $this->ElementService->slugExists($slug)) {
            $element = $this->ElementService->getBySlug($slug);
        } else if(!empty($id)) {
            $element = $this->ElementService->getByID($id);
        }

        if($element != null) {

            if($this->elementHasRecords($element->Slug))
                $element['HasRecords'] = true;

//            error_log(print_r($element, true));

            return array($element);
        }

        return array();
    }

    public function items()
    {
        //$this->checkPermission('elements-list');

        $dto = new DTO();

        $this->buildSorts($dto);
        $this->buildLimitOffset($dto);
        $this->buildFilters($dto);

        $this->passthruTemplateVariable($dto, 'IncludesAspect');

        $dto->setLimit(false);
        $dto->isRetrieveTotalRecords(false);

        $data = $this->ElementService->findAll($dto)->getResults();

        if(count($data) > 0)
            $data[0]['TotalRecords'] = count($data);

        return $data;
    }

}
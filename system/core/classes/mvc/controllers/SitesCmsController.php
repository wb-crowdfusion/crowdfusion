<?php
/**
 * SitesCmsController
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
 * @version     $Id: SitesCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SitesCmsController
 *
 * @package     CrowdFusion
 */
class SitesCmsController extends AbstractCmsController
{

    protected $SiteService = null;
    protected $ElementService = null;
    protected $NodeService = null;
    protected $Events = null;

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    public function setSiteService(SiteService $SiteService)
    {
        $this->SiteService = $SiteService;
    }

    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function setNodeService(NodeService $NodeService)
    {
        $this->NodeService = $NodeService;
    }

//    public function add()
//    {
//        $this->checkPermission('sites-add');
//
//        if(!$this->buttonPushed()) /* default flow */ {
//
//            return new View($this->formView());
//
//            //*** at this point the only subsequent actions will result from button presses
//        }
//
//        if($this->cancelButtonPushed()) {
//
//            //redirect to list (action_cancel_view)
//            return new View($this->cancelView());
//        }
//
//        if($this->saveButtonPushed() || $this->saveAndContinueButtonPushed()) {
//
//
//            $obj = new Site();
//
//            $this->ModelMapper->defaultsOnModel($obj);
//
//            //bind posted params to form backing object
//            $this->ModelMapper->inputArrayToModel($this->Request->getParameters(),$this->Request->getRawParameters(),$obj,$this->errors);
//
//            try {
//                $this->checkNonce();
//                $obj = $this->SiteService->add($obj);
//
//                $location = 'post';
//                $this->Events->trigger('SitesCmsController.'.__FUNCTION__.'.'.$location,
//                                            $this->errors, $this->templateVars, $obj);
//
//            } catch(ValidationException $ve) {
//                //bind form backing object to datasource
//                $data = $this->ModelMapper->modelToPersistentArray($obj);
//                $this->bindToActionDatasource(array($data));
//
//                //re-throw validationexception, which shows form view
//                throw $ve;
//            }
//
//            $this->Session->setFlashAttribute('saved', true);
//
//            if($this->saveButtonPushed())
//                //redirect to list (action_success_view)
//                return new View($this->successView(true));
//
//            else if($this->saveAndContinueButtonPushed())
//                //redirect to edit (action_continue_view)
//                return new View($this->continueView(true), array('id'=> $obj->SiteID));
//        }
//
//        throw new Exception("unsupported button for this action");
//    }

//    public function delete()
//    {
//        $this->checkPermission('sites-delete');
//
//        if($this->cancelButtonPushed()) {
//
//            return new View($this->continueView(true));
//        }
//
//        if($this->saveButtonPushed()) {
//
//            $obj = $this->SiteService->getByID($this->Request->getParameter('id'));
//
//            $this->checkNonce();
//            $this->SiteService->delete($obj->Slug);
//
//            return new View($this->successView(true));
//        }
//
//    }

//    public function edit()
//    {
//        $this->checkPermission('sites-edit');
//
//        if(!$this->buttonPushed()) /* default flow */ {
//
//            return new View($this->formView());
//
//            //*** at this point the only subsequent actions will result from button presses
//        }
//
//        if($this->cancelButtonPushed()) {
//
//            return new View($this->cancelView());
//        }
//
//        if($this->deleteConfirmButtonPushed()) {
//
////            $obj = $this->SiteService->getByID($this->Request->getParameter('id'));
////            if($this->NodeService->siteHasDBTables($obj))
////                $this->errors->addGlobalError('delete', "This site cannot be deleted. Please remove all floating elements and elements anchored to this site or manually remove all DB tables for this site and try again.")->throwOnError();
//
//            return new View($this->deleteConfirmView());
//        }
//
//        if($this->saveButtonPushed() || $this->saveAndContinueButtonPushed()) {
//
//            $obj = $this->SiteService->getByID($this->Request->getParameter('id'));
//
//            $obj = clone($obj);
//
//            //bind posted params to form backing object
//            $this->ModelMapper->inputArrayToModel($this->Request->getParameters(),$this->Request->getRawParameters(),$obj,$this->errors);
//
//            $obj->ModifiedDate = $this->DateFactory->newStorageDate();
//
//
//            try {
//                $this->checkNonce();
//                $this->SiteService->edit($obj);
//
//            } catch(ValidationException $ve) {
//                //bind form backing object to datasource
//                $data = $this->ModelMapper->modelToPersistentArray($obj);
//
////                if($this->NodeService->siteHasDBTables($obj))
////                    $data['HasTables'] = true;
//
//                $this->bindToActionDatasource(array($data));
//
//                //re-throw validationexception, which shows form view
//                throw $ve;
//            }
//
//            $this->Session->setFlashAttribute('saved', true);
//
//            if($this->saveButtonPushed())
//                return new View($this->successView(true));
//
//            else if($this->saveAndContinueButtonPushed())
//                return new View($this->continueView(true));
//        }
//
//        throw new Exception("unsupported button for this action");
//    }

    public function single()
    {
        $this->checkPermission('sites-list');

        $slug = $this->Request->getParameter('Slug');
        //$id = $this->Request->getParameter('SiteID');
        //if(empty($id))
        //   $id = $this->Request->getParameter('id');

        $site = null;

        if(!empty($slug) && $this->SiteService->slugExists($slug)) {
            $site = $this->SiteService->getBySlug($slug);
        //} else if(!empty($id)) {
        //    $site = $this->SiteService->getByID($id);
        }

        if(empty($site))
            return array();

        return array($site);
    }

    public function items()
    {
        //$this->checkPermission('sites-list');

        $dto = new DTO();

        $this->buildSorts($dto);
        $this->buildLimitOffset($dto);
        $this->buildFilters($dto);

        $dto->setLimit(false);
        $dto->isRetrieveTotalRecords(false);

        $data = $this->SiteService->findAll($dto)->getResults();

        if(count($data) > 0)
            $data[0]['TotalRecords'] = count($data);

        return $data;
    }

}
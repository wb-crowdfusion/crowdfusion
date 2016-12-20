<?php
/**
 * NodeCmsController
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
 * @version     $Id: NodeCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeCmsController
 *
 * @package     CrowdFusion
 */
class NodeCmsController extends AbstractCmsController
{
    protected $NodeService;
    protected $SiteService;
    protected $ApplicationContext;
    protected $nodeRef;


    public function setNodeImportExportService(NodeImportExportService $NodeImportExportService)
    {
        $this->NodeImportExportService = $NodeImportExportService;
    }

    /**
     * [IoC] Inject Events
     *
     * @param Events $Events injected
     *
     * @return void
     */
    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    /**
     * [IoC] Inject SiteService
     *
     * @param SiteService $SiteService SiteService
     *
     * @return void
     */
    public function setSiteService(SiteService $SiteService)
    {
        $this->SiteService = $SiteService;
    }

    protected $ErrorHandler;

    public function setErrorHandler(ErrorHandler $ErrorHandler)
    {
        $this->ErrorHandler = $ErrorHandler;
    }

    /////////////
    // ACTIONS //
    /////////////

    protected function export()
    {
        $this->checkPermission('nodes-export');

	      if(!$this->buttonPushed()) /* default flow */ {

            return new View($this->formView());

            //*** at this point the only subsequent actions will result from button presses
        }

        if($this->cancelButtonPushed()) {

            return new View($this->cancelView());
        }

        if($this->saveButtonPushed() || $this->saveAndContinueButtonPushed()) {

		      FileSystemUtils::recursiveMkdir(PATH_BUILD.DIRECTORY_SEPARATOR.'exports', 0755);

          $file = $this->Request->getParameter('file');
          if(empty($file))
			      $file = 'export.'.date('dmY').'.cf';

		      $result = $this->NodeImportExportService->export(PATH_BUILD.DIRECTORY_SEPARATOR.'exports'.DIRECTORY_SEPARATOR.$file);

          return new View($this->continueView(true));
        }

        throw new Exception("unsupported button for this action");
    }

    /**
     * Provides functionality for adding new nodes
     *
     * @return View
     */
    protected function add()
    {
        $nodeRef = $this->buildCmsNodeRef($slugKey = null);

        if (!$this->buttonPushed()) {

            $node = $nodeRef->generateNode();
            $node->setNodePartials(new NodePartials('all', 'fields', 'fields'));

            $this->NodeMapper->defaultsOnNode($node);

            $this->NodeBinder->bindPersistentFields($node, $this->errors, $this->params, $this->rawParams);
            $this->NodeBinder->fireAddBindEvents($node, $this->errors, $this->params, $this->rawParams);

            $location = 'load';
            $this->Events->trigger('NodeCmsController.add'.'.'.$location, $this->errors, $this->templateVars, $node);
            foreach ((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'add'.'.'.$location, $this->errors, $this->templateVars, $node);

            if($node->hasTitle() && !$node->hasSlug())
                $node->Slug = SlugUtils::createSlug($node->Title, $node->getNodeRef()->getElement()->AllowSlugSlashes);

            $this->bindNodeToActionDatasource($node);

            //show form view (action_form_view)
            return new View($this->formView());

            //*** at this point the only subsequent actions will result from button presses
        }

        if ($this->cancelButtonPushed() || $this->deleteConfirmButtonPushed()) {

            // $this->service->delete($id);

            $view = new View($this->cancelView());

            $location = 'cancel';
            $this->Events->trigger('NodeCmsController.add'.'.'.$location,
                                    $this->errors, $this->templateVars, $view);
            foreach ((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'add'.'.'.$location,
                                        $this->errors, $this->templateVars, $view);

            //redirect to pages list (action_cancel_view)
            return $view;
        }

        if ($this->saveButtonPushed() || $this->saveAndContinueButtonPushed() || $this->saveAndNewButtonPushed()) {

            //get default form backing object
            // $page = $this->service->getByID($id);
            try {
                $nodeRef = $this->buildCmsNodeRef($slugKey = 'Slug');

                $node = $nodeRef->generateNode();

                //bind posted params to form backing object
                $this->NodeBinder->bindPersistentFields($node, $this->errors, $this->params, $this->rawParams);
                $this->NodeBinder->fireAddBindEvents($node, $this->errors, $this->params, $this->rawParams);

                //FIRE EVENTS FOR PRE-ADD
                $location = 'pre';
                $this->Events->trigger('NodeCmsController.add'.'.'.$location,
                                        $this->errors, $this->templateVars, $node);
                foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                    $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'add'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);

                // check permissions
                // $this->checkAddPermission($node);
                $this->checkNonce();

                $this->getErrors()->throwOnError();

                $this->RegulatedNodeService->add($node);

                $location = 'post';
                $this->Events->trigger('NodeCmsController.add'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);
                foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                    $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'add'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);


            } catch(ValidationException $ve) {
                $location = 'validationException';
                $this->Events->trigger('NodeCmsController.add'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);
                foreach ((array)$nodeRef->getElement()->getAspects() as $aspect)
                    $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'add'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);

                $this->RegulatedNodeService->resolveLinkedNodes($node, $node->getNodePartials(), true);

                //bind form backing object to datasource
                $this->bindNodeToActionDatasource($node);

                //re-throw validationexception, which shows form view
                throw $ve;
            } catch (Exception $e) {

                $this->ErrorHandler->sendErrorEmail($e);

                $this->errors->addGlobalError($e->getCode(), $e->getMessage());

                if(!empty($node))
                {
                    $location = 'validationException';
                    $this->Events->trigger('NodeCmsController.add'.'.'.$location,
                                                $this->errors, $this->templateVars, $node);

                    foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                        $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'add'.'.'.$location,
                                                $this->errors, $this->templateVars, $node);

                    $this->RegulatedNodeService->resolveLinkedNodes($node, $node->getNodePartials(), true);

                    //bind form backing object to datasource
                    $this->bindNodeToActionDatasource($node);
                }

                //re-throw validationexception, which shows form view
                throw new ValidationException($this->errors);
            }

            $this->Session->setFlashAttribute('saved', true);

            if ($this->saveButtonPushed())
                //redirect to list (action_success_view)
                return new View($this->successView());

            elseif($this->saveAndContinueButtonPushed())
                //redirect to edit (action_continue_view)
                return new View($this->continueView(), array('slug'=> $node->getSlug()));

            elseif($this->saveAndNewButtonPushed())
                // redirect to new edit (action_new_view)
                return new View($this->newView(true), array('newslug'=> $node->getSlug()));

        }

        throw new Exception("unsupported button for this action");
    }



    /**
     * Provides functionality for editing nodes
     *
     * @return View
     */
    protected function edit()
    {
        $nodeRef = $this->buildCmsNodeRef();
        if(!$nodeRef->isFullyQualified())
            throw new NotFoundException('Cannot edit record without fully-qualified NodeRef');

        $node = $nodeRef->generateNode();

        if (!$this->buttonPushed()) {  /* default flow */

            $node->setNodePartials(new NodePartials('all', 'fields', 'fields'));

            $location = 'load';
            $this->Events->trigger('NodeCmsController.edit'.'.'.$location,
                                    $this->errors, $this->templateVars, $node);
            foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'edit'.'.'.$location,
                                        $this->errors, $this->templateVars, $node);

            if (!$node->hasTitle())
                //get default form backing object
                $node = $this->RegulatedNodeService->getByNodeRef($node->getNodeRef(), $node->getNodePartials());

            if(empty($node) || $node->Status == 'deleted')
            {
                $this->Session->setFlashAttribute('refreshed', true);
                return new View($this->successView());
            }

//            if (empty($node))
//                throw new NotFoundException('Record not found for NodeRef: '.$nodeRef);

            // check permissions
            // $this->checkEditPermission($node);

            //bind form backing object ($page) to data source
            //$this->bindObjectToActionDatasource($page);
            $this->bindNodeToActionDatasource($node);

            //show form view (action_form_view)
            return new View($this->formView());

            //*** at this point the only subsequent actions will result from button presses
        }

        if ($this->cancelButtonPushed()) {
            $view = new View($this->cancelView());

            $location = 'cancel';
            $this->Events->trigger('NodeCmsController.edit'.'.'.$location,
                                    $this->errors, $this->templateVars, $node, $view);
            foreach ((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'edit'.'.'.$location,
                                        $this->errors, $this->templateVars, $node, $view);

            //redirect to pages list (action_cancel_view)
            return $view;
        }

        if ($this->deleteConfirmButtonPushed()) {
            $view = new View($this->deleteConfirmView());

            $location = 'delete';
            $this->Events->trigger('NodeCmsController.edit'.'.'.$location,
                                    $this->errors, $this->templateVars, $node, $view);
            foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'edit'.'.'.$location,
                                        $this->errors, $this->templateVars, $node, $view);

            $this->Session->setFlashAttribute('delete_referer', $this->Request->getReferrer());

            //redirect to pages list (action_cancel_view)
            return $view;
        }

        if ($this->saveButtonPushed() || $this->saveAndContinueButtonPushed() || $this->saveAndNewButtonPushed()) {
            try {
                $node->setNodePartials(new NodePartials());

                $node = $this->RegulatedNodeService->getByNodeRef($node->getNodeRef(), $node->getNodePartials());
                if (empty($node))
                    throw new Exception('Record not found for NodeRef: '.$nodeRef);


                //bind posted params to form backing object ($page)
                $this->NodeBinder->bindPersistentFields($node, $this->errors, $this->params, $this->rawParams);
                $this->NodeBinder->fireEditBindEvents($node, $this->errors, $this->params, $this->rawParams);

                $location = 'pre';
                $this->Events->trigger('NodeCmsController.edit'.'.'.$location,
                                        $this->errors, $this->templateVars, $node);
                foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                    $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'edit'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);

                // check permissions
                // $this->checkEditPermission($node);
                $this->checkNonce();

                $this->getErrors()->throwOnError();

                $this->RegulatedNodeService->edit($node);

                $location = 'post';
                $this->Events->trigger('NodeCmsController.edit'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);
                foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                    $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'edit'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);

            } catch(ValidationException $ve) {

                $location = 'validationException';
                $this->Events->trigger('NodeCmsController.edit'.'.'.$location,
                                        $this->errors, $this->templateVars, $node);
                foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                    $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'edit'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);

                $this->RegulatedNodeService->resolveLinkedNodes($node, $node->getNodePartials(), true);

                //bind form backing object to datasource
                if (!empty($node))
                    $this->bindNodeToActionDatasource($node);

                //re-throw validationexception, which shows form view
                throw $ve;
            } catch (Exception $e) {

                $this->ErrorHandler->sendErrorEmail($e);

                $this->errors->addGlobalError($e->getCode(), $e->getMessage());

                if(!empty($node))
                {
                    $location = 'validationException';
                    $this->Events->trigger('NodeCmsController.edit'.'.'.$location,
                                                $this->errors, $this->templateVars, $node);

                    foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                        $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'edit'.'.'.$location,
                                                $this->errors, $this->templateVars, $node);

                    $this->RegulatedNodeService->resolveLinkedNodes($node, $node->getNodePartials(), true);

                    //bind form backing object to datasource
                    $this->bindNodeToActionDatasource($node);
                }

                //re-throw validationexception, which shows form view
                throw new ValidationException($this->errors);
            }


            $this->Session->setFlashAttribute('saved', true);


            if ($this->saveButtonPushed())
                //redirect to list (action_success_view)
                return new View($this->successView());

            elseif ($this->saveAndContinueButtonPushed())
                //redirect to edit (action_continue_view)
                return new View($this->continueView(true), array('slug'=> $node->getSlug()));

            elseif ($this->saveAndNewButtonPushed())
                // redirect to new edit (action_new_view)
                return new View($this->newView(true), array('newslug'=> $node->getSlug()));

        }

        throw new Exception("unsupported button for this action");
    }


    /**
     * Provides functionality for duplicating nodes
     *
     * @return View
     */
    protected function duplicate()
    {
        $nodeRef = $this->buildCmsNodeRef('OriginalSlug');
        $node = $nodeRef->generateNode();

        $node->setNodePartials(new NodePartials('all', 'fields', 'fields'));

        $location = 'load';
        $this->Events->trigger('NodeCmsController.duplicate'.'.'.$location,
                                $this->errors, $this->templateVars, $node);

        foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
            $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'duplicate'.'.'.$location,
                                    $this->errors, $this->templateVars, $node);

        if (!$node->hasTitle())
            //get default form backing object
            $node = $this->RegulatedNodeService->getByNodeRef($node->getNodeRef(), $node->getNodePartials());

        if (empty($node))
            throw new NotFoundException('Record not found for NodeRef: '.$nodeRef);

        if (!$this->buttonPushed()) {  /* default flow */

            // check permissions
            // $this->checkDuplicatePermission($node);

            $this->bindNodeToActionDatasource($node);

            //show form view (action_form_view)
            return new View($this->formView());

            //*** at this point the only subsequent actions will result from button presses
        }

        if ($this->cancelButtonPushed()) {

            $view = new View($this->cancelView());

            $location = 'cancel';
            $this->Events->trigger('NodeCmsController.duplicate'.'.'.$location,
                                    $this->errors, $this->templateVars, $node, $view);

            foreach ((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'duplicate'.'.'.$location,
                                            $this->errors, $this->templateVars, $node, $view);

            //redirect to pages list (action_cancel_view)
            return $view;
        }

        if ($this->deleteConfirmButtonPushed()) {
            $view = new View($this->deleteConfirmView());

            $location = 'delete';
            $this->Events->trigger('NodeCmsController.duplicate'.'.'.$location,
                                    $this->errors, $this->templateVars, $node, $view);

            foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'duplicate'.'.'.$location,
                                            $this->errors, $this->templateVars, $node, $view);

            $this->Session->setFlashAttribute('delete_referer', $this->Request->getReferrer());

            //redirect to pages list (action_cancel_view)
            return $view;

        }

        if ($this->saveButtonPushed() || $this->saveAndContinueButtonPushed() || $this->saveAndNewButtonPushed()) {
            try {
                $nodeRef = $this->buildCmsNodeRef($slugKey = 'Slug');

                $node = $nodeRef->generateNode();

                //bind posted params to form backing object
                $this->NodeBinder->bindPersistentFields($node, $this->errors, $this->params, $this->rawParams);
                $this->NodeBinder->fireAddBindEvents($node, $this->errors, $this->params, $this->rawParams);

                $location = 'pre';
                $this->Events->trigger('NodeCmsController.duplicate'.'.'.$location,
                                        $this->errors, $this->templateVars, $node);
                foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                    $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'duplicate'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);

                // check permissions
                // $this->checkDuplicatePermission($node);
                $this->checkNonce();

                $this->getErrors()->throwOnError();

                $this->RegulatedNodeService->add($node);

                $location = 'post';
                $this->Events->trigger('NodeCmsController.duplicate'.'.'.$location,
                                        $this->errors, $this->templateVars, $node);

                foreach ((array)$nodeRef->getElement()->getAspects() as $aspect)
                    $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'duplicate'.'.'.$location,
                                                $this->errors, $this->templateVars, $node);

            } catch (ValidationException $ve) {

                $location = 'validationException';
                $this->Events->trigger('NodeCmsController.duplicate'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);

                foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                    $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'duplicate'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);

                $this->RegulatedNodeService->resolveLinkedNodes($node, $node->getNodePartials(), true);

                //bind form backing object to datasource
                $this->bindNodeToActionDatasource($node);

                //re-throw validationexception, which shows form view
                throw $ve;
            } catch (Exception $e) {

                $this->ErrorHandler->sendErrorEmail($e);

                $this->errors->addGlobalError($e->getCode(), $e->getMessage());

                if(!empty($node))
                {
                    $location = 'validationException';
                    $this->Events->trigger('NodeCmsController.duplicate'.'.'.$location,
                                                $this->errors, $this->templateVars, $node);

                    foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                        $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'duplicate'.'.'.$location,
                                                $this->errors, $this->templateVars, $node);

                    $this->RegulatedNodeService->resolveLinkedNodes($node, $node->getNodePartials(), true);

                    //bind form backing object to datasource
                    $this->bindNodeToActionDatasource($node);
                }

                //re-throw validationexception, which shows form view
                throw new ValidationException($this->errors);
            }

            $this->Session->setFlashAttribute('saved', true);

            if ($this->saveButtonPushed())
                //redirect to list (action_success_view)
                return new View($this->successView());

            elseif ($this->saveAndContinueButtonPushed())
                //redirect to edit (action_continue_view)
                return new View($this->continueView(true), array('slug'=> $node->getSlug()));

            elseif ($this->saveAndNewButtonPushed())
                // redirect to new edit (action_new_view)
                return new View($this->newView(true), array('newslug'=> $node->getSlug()));
        }

        throw new Exception("unsupported button for this action");
    }

    /**
     * Provides delete functionality for Nodes
     *
     * @return View
     */
    protected function delete()
    {
        $nodeRef = $this->buildCmsNodeRef('Slug');

        $node = $nodeRef->generateNode();

        $location = 'load';
        $this->Events->trigger('NodeCmsController.delete'.'.'.$location,
                                    $this->errors, $this->templateVars, $node);
        foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
            $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'delete'.'.'.$location,
                                    $this->errors, $this->templateVars, $node);

        if (!$node->hasTitle())
            //get default form backing object
            $node = $this->RegulatedNodeService->getByNodeRef($node->getNodeRef());

        if (empty($node))
            throw new NotFoundException('Record not found for NodeRef: '.$nodeRef);

        // check permissions
        // $this->checkDeletePermission($node);

        if (!$this->buttonPushed()) {
            $this->Session->setFlashAttribute('delete_referer', $this->Request->getReferrer());
            //show delete confirmation view (action_form_view)
            return new View($this->formView());
        }

        if ($this->saveButtonPushed()) {
            $this->checkNonce();
            $mergeSlug = null;
            $tags      = isset($this->params['OutTags']['#merge'])  ?
                           $this->params['OutTags']['#merge']
                         : array();

            if (!empty($tags)) {
                $newtag = new Tag(current($tags));

                if ($newtag->TagElement != $nodeRef->getElement()->getSlug())
                    throw new Exception('Unable to merge with different element ['.$newtag->TagElement.']');

                $mergeSlug = $newtag->TagSlug;
            }

            $location = 'pre';
            $this->Events->trigger('NodeCmsController.delete'.'.'.$location,
                                    $this->errors, $this->templateVars, $node, $mergeSlug);
            foreach ((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'delete'.'.'.$location,
                                        $this->errors, $this->templateVars, $node, $mergeSlug);

            $this->getErrors()->throwOnError();


            $this->RegulatedNodeService->delete($nodeRef, $mergeSlug);

            $location = 'post';
            $this->Events->trigger('NodeCmsController.delete'.'.'.$location, $this->errors, $this->templateVars, $node);
            foreach ((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'delete'.'.'.$location, $this->errors, $this->templateVars, $node);

            //redirect to news list (action_success_view)
            return new View($this->successView());
        }

        if ($this->cancelButtonPushed())
            //redirect to referer
            return new View('redirect:'.$this->Session->getFlashAttribute('delete_referer'));  //$this->cancelView());

        throw new Exception("unsupported button for this action");
    }


    /**
     * Provides 'undelete' functionality for Nodes
     *
     * @return View
     */
    protected function undelete()
    {
        if($this->saveButtonPushed())
        {
            $nodeRef = $this->buildCmsNodeRef();
            $node = $nodeRef->generateNode();
            $location = 'load';

            $this->Events->trigger('NodeCmsController.undelete'.'.'.$location,
                                    $this->errors, $this->templateVars, $node);

            foreach((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'undelete'.'.'.$location,
                                        $this->errors, $this->templateVars, $node);

            if (!$node->hasTitle())
                //get default form backing object
                $node = $this->RegulatedNodeService->getByNodeRef($node->getNodeRef());

            if (empty($node))
                throw new NotFoundException('Record not found for NodeRef: '.$nodeRef);

            // check permissions
            // $this->checkUndeletePermission($node);

            $location = 'pre';
            $this->Events->trigger('NodeCmsController.undelete'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);

            foreach ((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'undelete'.'.'.$location,
                                            $this->errors, $this->templateVars, $node);

            $this->getErrors()->throwOnError();

            $this->RegulatedNodeService->undelete($nodeRef);

            $location = 'post';
            $this->Events->trigger('NodeCmsController.undelete'.'.'.$location, $this->errors, $this->templateVars, $node);
            foreach ((array)$nodeRef->getElement()->getAspects() as $aspect)
                $this->Events->trigger('NodeCmsController.@'.$aspect->Slug.'.'.'undelete'.'.'.$location, $this->errors, $this->templateVars, $node);


            //redirect to edit news (action_success_view)
            return new View($this->successView(), array('slug'=> $nodeRef->getSlug()));
        }
    }


    /////////////////
    // DATASOURCES //
    /////////////////


    /**
     * Datasource for returning a single Node
     *
     * @return DTO Contains the matching node (as an array) or is empty
     */
    protected function single()
    {

        // check permissions
        // $this->checkSinglePermission();

        $nq = new NodeQuery();
        $nq->setLimit(1);
//        $this->buildFilters($nq);

        $this->passthruTemplateVariable($nq, 'Elements.in');
        $this->passthruTemplateVariable($nq, 'Sites.in');
        //$this->passthruTemplateVariable($nq, 'SiteIDs.in');

        $this->passthruTemplateVariable($nq, 'Meta.select');
        $this->passthruTemplateVariable($nq, 'OutTags.select');
        $this->passthruTemplateVariable($nq, 'InTags.select');
//        $this->passthruTemplateVariable($nq, 'Sections.select');

//        $this->passthruTemplateVariable($nq, 'Title.like');
//        $this->passthruTemplateVariable($nq, 'Title.ieq');
//        $this->passthruTemplateVariable($nq, 'Title.eq');
//        $this->passthruTemplateVariable($nq, 'Title.firstChar');
//        $this->passthruTemplateVariable($nq, 'Status.isActive');
//        $this->passthruTemplateVariable($nq, 'Status.all');
//        $this->passthruTemplateVariable($nq, 'Status.eq');
//        $this->passthruTemplateVariable($nq, 'TreeID.childOf');
//        $this->passthruTemplateVariable($nq, 'TreeID.eq');
//
//        $this->passthruTemplateVariable($nq, 'ActiveDate.before');
//        $this->passthruTemplateVariable($nq, 'ActiveDate.after');
//        $this->passthruTemplateVariable($nq, 'ActiveDate.start');
//        $this->passthruTemplateVariable($nq, 'ActiveDate.end');
//
//        $this->passthruTemplateVariable($nq, 'CreationDate.before');
//        $this->passthruTemplateVariable($nq, 'CreationDate.after');
//        $this->passthruTemplateVariable($nq, 'CreationDate.start');
//        $this->passthruTemplateVariable($nq, 'CreationDate.end');

//        $this->passthruTemplateVariable($nq, 'OutTags.exist');
//        $this->passthruTemplateVariable($nq, 'InTags.exist');
//        $this->passthruTemplateVariable($nq, 'Meta.exist');

//        foreach ($this->templateVars as $name => $value) {
//            if (strpos($name, '#') === 0)
//                $nq->setParameter($name, $value);
//        }

        $slug = $this->getTemplateVariable('Slugs.in');
        if ($slug != null) {
            $nq->setParameter('Slugs.in', $slug);

            $nq = $this->NodeRefService->normalizeNodeQuery($nq);

            $nodeRefs = $nq->getParameter('NodeRefs.normalized');
            $nodePartials = $nq->getParameter('NodePartials.eq');
            $allFullyQualified = $nq->getParameter('NodeRefs.fullyQualified');

            if(!$allFullyQualified || count($nodeRefs) > 1)
                throw new Exception('No Slugs.in set for DataSource');

            $row = $this->RegulatedNodeService->getByNodeRef(current($nodeRefs), $nodePartials);

            if(!empty($row))
                $nq->setResults(array($row));

            return $this->readNodeQuery($nq);
        }

        throw new Exception('No Slugs.in set for DataSource');
    }

    /**
     * Datasource for returning all matching Nodes
     *
     * @return DTO contains all matching nodes (As array)
     */
    protected function items()
    {
        // check permissions
        // $siteIDs = $this->checkItemsPermission();

        $dto = new NodeQuery();
        $this->buildLimitOffset($dto);

        $this->passthruTemplateVariable($dto, 'Elements.in');
        $this->passthruTemplateVariable($dto, 'Sites.in');
        //$this->passthruTemplateVariable($dto, 'SiteIDs.in');
        $this->passthruTemplateVariable($dto, 'Slugs.in');

        $this->passthruTemplateVariable($dto, 'Meta.select');
        $this->passthruTemplateVariable($dto, 'OutTags.select');
        $this->passthruTemplateVariable($dto, 'InTags.select');
//        $this->passthruTemplateVariable($dto, 'Sections.select');
        $this->passthruTemplateVariable($dto, 'OrderByInTag');
        $this->passthruTemplateVariable($dto, 'OrderByOutTag');

        $this->passthruTemplateVariable($dto, 'Title.like');
        $this->passthruTemplateVariable($dto, 'Title.ieq');
        $this->passthruTemplateVariable($dto, 'Title.eq');
        $this->passthruTemplateVariable($dto, 'Title.firstChar');
        $this->passthruTemplateVariable($dto, 'Status.isActive');
        $this->passthruTemplateVariable($dto, 'Status.all');
        $this->passthruTemplateVariable($dto, 'Status.eq');
        $this->passthruTemplateVariable($dto, 'TreeID.childOf');
        $this->passthruTemplateVariable($dto, 'TreeID.eq');

        $this->passthruTemplateVariable($dto, 'ActiveDate.before');
        $this->passthruTemplateVariable($dto, 'ActiveDate.after');
        $this->passthruTemplateVariable($dto, 'ActiveDate.start');
        $this->passthruTemplateVariable($dto, 'ActiveDate.end');

        $this->passthruTemplateVariable($dto, 'CreationDate.before');
        $this->passthruTemplateVariable($dto, 'CreationDate.after');
        $this->passthruTemplateVariable($dto, 'CreationDate.start');
        $this->passthruTemplateVariable($dto, 'CreationDate.end');

        $this->passthruTemplateVariable($dto, 'OutTags.exist');
        $this->passthruTemplateVariable($dto, 'InTags.exist');
        $this->passthruTemplateVariable($dto, 'Meta.exist');
//        $this->passthruTemplateVariable($dto, 'Sections.exist');

        foreach ($this->templateVars as $name => $value) {
            if (strpos($name, '#') === 0)
                $dto->setParameter($name, $value);
        }

        $this->buildFilters($dto);

        $dto->isRetrieveTotalRecords(true);

        $dto->setOrderBy($this->getTemplateVariable('OrderBy'));
        $this->buildSorts($dto);

        $this->Events->trigger('NodeCmsController.items', $dto);

        try {
            $dto = $this->RegulatedNodeService->findAll($dto,
                    (($frw = $this->getTemplateVariable('ForceReadWrite'))!=null?StringUtils::strToBool($frw):false));
            return $this->readNodeQuery($dto);
        } catch (NoElementsException $e) {
            throw new NotFoundException('No elements were found for this request.');
        }

    }





    /////////////////
    // HELPERS     //
    /////////////////

    /**
     * Given a slug, builds a nodeRef
     *
     * @param string $slugKey The slug to use
     *
     * @return NodeRef
     */
    protected function buildCmsNodeRef($slugKey = 'OriginalSlug')
    {
        if (empty($this->params['Element']))
            throw new Exception('Element parameter is required');

        $nodeElement = $this->ElementService->getBySlug($this->params['Element']);

        if (!is_null($slugKey) && $this->Request->getParameter($slugKey) != '') {
            $slug = $this->Request->getParameter($slugKey);

            $this->nodeRef = new NodeRef($nodeElement, $slug);
        } else {
            $this->nodeRef = new NodeRef($nodeElement);
        }


        return $this->nodeRef;
    }

}

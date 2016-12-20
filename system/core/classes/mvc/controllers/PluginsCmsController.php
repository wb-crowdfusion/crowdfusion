<?php
/**
 * PluginsCmsController
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
 * @version     $Id: PluginsCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PluginsCmsController
 *
 * @package     CrowdFusion
 */
class PluginsCmsController extends AbstractCmsController
{

    protected $PluginService = null;
    protected $AspectService = null;
    protected $ApplicationContext = null;
    protected $PluginInstallationService = null;
    protected $rootPath;

    public function setAspectService(AspectService $AspectService)
    {
        $this->AspectService = $AspectService;
    }

    public function setRootPath($rootPath)
    {
        $this->rootPath = rtrim($rootPath,'/').'/';
    }

    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    public function setPluginService(PluginService $PluginService)
    {
        $this->PluginService = $PluginService;
    }


    public function setPluginInstallationService(PluginInstallationService $PluginInstallationService)
    {
        $this->PluginInstallationService = $PluginInstallationService;
    }

    protected function manage()
    {
        return new View($this->formView());
    }

    protected function install()
    {
        $this->checkPermission('plugins-install');

        if($this->saveButtonPushed())
        {
            $obj = $this->PluginService->getByID($this->Request->getParameter('id'));

            list($log, $status) = $this->PluginInstallationService->installPlugin($obj->Path, $this->errors);

            return new View('plugins/install.cft',array('action'=>__FUNCTION__, 'log'=>$log,'status'=>$status));
        }


        if($this->cancelButtonPushed())
            return new View($this->cancelView());
    }

    protected function upgrade()
    {
        $this->checkPermission('plugins-install');

        if($this->saveButtonPushed())
        {
            $obj = $this->PluginService->getByID($this->Request->getParameter('id'));

            list($log,$status) = $this->PluginInstallationService->upgradePlugin($obj->Slug,$obj->Path, $this->errors);

            return new View('plugins/install.cft',array('action' => __FUNCTION__, 'log'=>$log,'status'=>$status));
        }


        if($this->cancelButtonPushed())
            return new View($this->cancelView());
    }

    protected function uninstall()
    {
        $this->checkPermission('plugins-uninstall');

        if($this->saveButtonPushed())
        {
            $obj = $this->PluginService->getByID($this->Request->getParameter('id'));

            list($log,$status) = $this->PluginInstallationService->uninstallPlugin($obj->Slug, $this->errors);

            return new View('plugins/install.cft',array('action' => __FUNCTION__, 'log'=>$log,'status'=>$status));

        }

        if($this->cancelButtonPushed())
            return new View($this->cancelView());

    }


    protected function uninstallPurge()
    {
        $this->checkPermission('plugins-uninstall');

        if ($this->saveButtonPushed())
        {
            $obj = $this->PluginService->getByID($this->Request->getParameter('id'));

            list($log,$status) = $this->PluginInstallationService->uninstallPlugin($obj->Slug, $this->errors, true);

            return new View('plugins/install.cft',array('action' => 'uninstall', 'log'=>$log,'status'=>$status));

        }

        if($this->cancelButtonPushed())
            return new View($this->cancelView());

    }

    public function edit()
    {
        $this->checkPermission('plugins-edit');

        if(!$this->buttonPushed()) /* default flow */ {

            return new View($this->formView());

            //*** at this point the only subsequent actions will result from button presses
        }

        if($this->cancelButtonPushed()) {

            return new View($this->cancelView());
        }

        if($this->saveButtonPushed() || $this->saveAndContinueButtonPushed()) {



            try {
                $this->checkNonce();

                $obj = $this->PluginService->getByID($this->Request->getParameter('id'));

                //bind posted params to form backing object
                $this->ModelMapper->inputArrayToModel($this->Request->getParameters(),$this->Request->getRawParameters(),$obj,$this->errors);

                $obj->ModifiedDate = $this->DateFactory->newStorageDate();

                $this->errors->throwOnError();

                $this->PluginService->edit($obj);

                $obj = $this->PluginService->getByID($obj->PluginID);

                $this->ApplicationContext->clearContextFiles();

                //$this->ApplicationContext->setPluginStatus($obj->Path, $obj->isEnabled(), $obj->Priority);

            } catch(ValidationException $ve) {
                //bind form backing object to datasource
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

    protected function single()
    {
        $this->checkPermission('plugins-list');

        $slug = $this->Request->getParameter('Slug');
        $id = $this->Request->getParameter('PluginID');
        if(empty($id))
            $id = $this->Request->getParameter('id');

        $plugin = null;

        if(!empty($slug) && $this->PluginService->slugExists($slug)) {
            $plugin = $this->PluginService->getBySlug($slug);
        } else if(!empty($id)) {
            $plugin = $this->PluginService->getByID($id);
        }

        if(!empty($plugin)) {
            if($this->getTemplateVariable('IncludeAspects') == 'true') {
                $dto = new DTO();
                $dto->setParameter('PluginID',$plugin->PluginID);
                $plugin['Aspects'] = $this->AspectService->findAll($dto)->getResults();
            }

            return array($plugin);
        }

        return array();
    }

    public function items()
    {
        $this->checkPermission('plugins-list');

        $this->PluginInstallationService->scanInstall();

        $dto = new DTO();

        //$this->buildSorts($dto);
        //$this->buildLimitOffset($dto);
        //$this->buildFilters($dto);

        $dto->setLimit(null);

        $data = $this->PluginService->findAll($dto)->getResults();

        if(count($data) > 0)
            $data[0]['TotalRecords'] = count($data);

        return $data;
    }

}

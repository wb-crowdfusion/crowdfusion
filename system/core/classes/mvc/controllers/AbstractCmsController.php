<?php
/**
 * Abstract methods for CMS controllers including view, action, and nonce handling.
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
 * @version     $Id: AbstractCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Abstract methods for CMS controllers including view, action, and nonce handling.
 *
 * @package     CrowdFusion
 */
abstract class AbstractCmsController extends AbstractWebController
{

    // default action buttons, can be overridden
    protected $cancelKey          = 'cancel';
    protected $saveAndNewKey      = 'saveandnew';
    protected $saveAndContinueKey = 'saveandcontinue';
    protected $deleteConfirmKey   = 'confirmdelete';

    protected function handleActionInternal($method)
    {
        try {

            parent::handleActionInternal($method);

        } catch (NoncesException $e) {
            //if (LOG_ENABLE) System::log(self::$logType, 'Nonce Exception: ['.print_r($e, true).']');

            $data = array();
            //$data                              = $this->rawParams; // Fill all params so we can access them on the nonce-mismatch screen
            //$data['control_action']            = $this->RequestContext->getControls()->getControl('action');
            //$data['control_action_form_view']  = $this->RequestContext->getControls()->getControl('action_form_view');
            $data['NonceMismatch-Params']      = array_merge($this->rawParams, array(
                'action_nonce' => $this->Nonces->create($this->rawParams['action'])
            ));
            //$data['NonceMismatch-OriginalURL'] = $this->Request->getRequestURI();

            $view = 'nonce-mismatch.cft';

            $this->view = new View($view, $data);
        }
    }

    protected function checkNonce()
    {
        $nonce = $this->RequestContext->getControls()->getControl('action_nonce');

        // action was submitted, so verify the nonce
        // NOTE: this verifies the nonce against the original method
        if(!$this->Nonces->verify($nonce, $this->originalAction)) {
            throw new NoncesException('Nonce mismatch for action ['.$this->originalAction.'].');
        }

        //if(LOG_ENABLE) System::log(self::$logType, 'Passed nonce check.');
    }

    protected function saveAndNewButtonPushed()
    {
        return $this->buttonPushed($this->saveAndNewKey);
    }

    protected function saveAndContinueButtonPushed()
    {
        return $this->buttonPushed($this->saveAndContinueKey);
    }

    protected function cancelButtonPushed()
    {
        return $this->buttonPushed($this->cancelKey);
    }

    protected function deleteConfirmButtonPushed()
    {
        return $this->buttonPushed($this->deleteConfirmKey);
    }

    protected function cancelView()
    {
        $cancelView = $this->RequestContext->getControls()->getControl('action_cancel_view');
        if($cancelView == null || empty($cancelView))
            $cancelView = 'redirect:'.$this->Request->getReferrer();

        return $cancelView;
    }

    protected function deleteConfirmView()
    {
        $deleteConfirmView = $this->RequestContext->getControls()->getControl('action_confirm_delete_view');

        return $deleteConfirmView;
    }

    protected function newView($forceRedirect = false)
    {
        $saveNewView = $this->RequestContext->getControls()->getControl('action_new_view');

        if(substr(trim($saveNewView),0,9) != 'redirect:' && $forceRedirect)
            $saveNewView = 'redirect:/'.$saveNewView;

        return $saveNewView;
    }

    protected function continueView($forceRedirect = false)
    {
            // get continue view
        $continueView = $this->RequestContext->getControls()->getControl('action_continue_view');

        if($continueView == null || empty($continueView))
            $continueView = 'redirect:'.$this->Request->getReferrer();

        if(substr(trim($continueView),0,9) != 'redirect:' && $forceRedirect)
            $continueView = 'redirect:/'.$continueView;

        return $continueView;
    }


    protected function buildSorts(&$dto) {

        if($this->bypassSorts()) return;

        if(!empty($this->params['sort']))
            $dto->setOrderBys($this->params['sort']);

    }

    protected function buildFilters(&$dto) {

        if($this->bypassFilters()) return;

        if(isset($this->rawParams['filter']))
            foreach((array)$this->rawParams['filter'] as $name => $value) {
                $dto->setParameter($name, $value);
            }

    }

    protected function bypassFilters() {
        return(isset($this->templateVars['NoFilters']));
    }

    protected function bypassSorts() {
        return(isset($this->templateVars['NoSorts']));
    }


}
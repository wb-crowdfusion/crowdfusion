<?php
/**
 * Controls
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
 * @version     $Id: Controls.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * This class encapsulates the control variables which are derived from the {@link Request} object.  The control
 * variables command the flow of the web request through the dispatcher, controllers, interceptors, renderer and
 * template engines.  This object lives in the {@link WebContext} object, which is available to nearly every web
 * component.
 *
 * @package     CrowdFusion
 */
class Controls
{
    private $controls = null;

    public function __construct(array $routeParameters, array $inputParameters)
    {
        $this->clearControls();
        $this->setControls(array_merge($routeParameters, $inputParameters));
    }

    public function getControl($name)
    {
		if(!isset($this->controls[$name])) return null;

        return $this->controls[$name];
    }

    public function getControls()
    {
        return $this->controls;
    }


	public function setControl($name, $value) {
//		if(!in_array(trim(strtolower($name)), array('view','action_success_view', /*'id', 'view_nodebug', 'action', 'action_form_view'*/))) {
//			return;
//		}
		if($value == null)
			unset($this->controls[$name]);
		else
			$this->controls[$name] = $value;
	}

	public function isActionExecuting() {
		return $this->getControl('action_executing') != null;
	}

	public function clearControls()
	{
        // SET DEFAULTS
        $this->controls = array(
            'view_handler' => 'html',
            'view_page' => 1
        );

        return $this;
	}

	public function clearAction() {
		foreach((array)$this->controls as $name => $value)
			if($name == 'action' || strpos($name, 'action_') === 0) unset($this->controls[$name]);
	}

    public function setControls($params)
    {

		$cvars = array(
			'view',
			'view_page',
			'view_handler',
			'view_paging',
			'view_nodebug',
			'view_nocache',
            'view_pushcache',
			'view_rss_link',
			'action',
			'action_nonce',
			//'action_button',
			'action_success_view',
			'action_form_view',
			'action_continue_view',
			'action_new_view',
			'action_cancel_view',
			'action_confirm_delete_view',
			'action_datasource',
            'action_buffer');

		foreach($params as $name => $value) {

//			if(in_array($name, $cvars)) {
				$this->controls[$name] = $value;
//			}

			if(substr($name, 0, 2) == 'b_') {
				if(substr($name, -2) == '_x' || substr($name, -2) == '_y')
					$name = substr($name, 0, -2);
				$this->controls['action_button'] = substr($name, 2);
			}

			if(substr($name, 0, 2) == 'a_') {
				if(substr($name, -2) == '_x' || substr($name, -2) == '_y')
					$name = substr($name, 0, -2);
				$this->controls['action_button'] = 'save';
				$this->controls['action_override_method'] = substr($name, 2);
			}

		}

        if(isset($this->controls['view_pushcache']))
        {
            $_SERVER['REQUEST_URI'] = preg_replace('/\?view_pushcache=([^\&]+)\&/', '?', $_SERVER['REQUEST_URI']);
            $_SERVER['REQUEST_URI'] = preg_replace('/\?view_pushcache=([^\&]+)/', '', $_SERVER['REQUEST_URI']);
            unset($_GET['view_pushcache']);

        }

		if(isset($this->controls['action_button'])) {
			$this->controls['action_executing'] = true;
            $this->controls['view_nocache'] = true;
        }

		if(isset($this->controls['action'])) {
            try {

                list($elem,$method) = ActionUtils::parseActionDatasource($this->controls['action']);

                $this->controls['action'] = ActionUtils::createActionDatasource($elem,$method);
                $this->controls['action_element'] = $elem;
                $this->controls['action_method'] = $method;

            }catch(ActionDatasourceException $ae){
                unset($this->controls['action']);
                unset($this->controls['action_element']);
                unset($this->controls['action_method']);
            }
		}

		return $this;
    }


}
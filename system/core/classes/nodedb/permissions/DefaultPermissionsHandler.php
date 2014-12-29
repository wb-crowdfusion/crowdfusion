<?php
/**
 * DefaultPermissionsHandler
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
 * @version     $Id: DefaultPermissionsHandler.php 2025 2010-02-18 14:43:46Z ryans $
 */

/**
 * DefaultPermissionsHandler
 *
 * @package     CrowdFusion
 */
class DefaultPermissionsHandler
{
    protected $aspect;

    public function setPermissions(PermissionsInterface $Permissions)
    {
        $this->Permissions = $Permissions;
    }

    public function setNodeService(NodeServiceInterface $NodeService)
    {
        $this->NodeService = $NodeService;
    }

    protected function getAspect($eventName)
    {
        $at = strpos($eventName, '@');
        $dot = strpos(substr($eventName, $at+1), '.');

        return substr($eventName, $at+1, $dot);
    }

    protected function buildPerm($context, $action)
    {
        return $this->getAspect($context['name']).'-'.strtolower($action);
    }

    protected function check($perm, Transport &$permitted, NodeRef $nodeRef)
    {
        $permitted->Permitted = $this->Permissions->checkPermission($perm, $nodeRef->getSite()->getSlug());
    }

    public function __call($name, $arguments)
    {
        if(count($arguments) < 3)
            throw new Exception('Calls to DefaultPermissionsHandler require 3 parameters: array $eventContext, boolean $permitted, NodeRef $nodeRef');

        $context = $arguments[0];
        $permitted = $arguments[1];
        $nodeRef = $arguments[2];

        $this->check($this->buildPerm($context, $name) , $permitted, $nodeRef);

    }

}
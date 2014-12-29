<?php
/**
 * PermissionFilterer
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
 * @version     $Id: PermissionFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PermissionFilterer
 *
 * @package     CrowdFusion
 */
class PermissionFilterer extends AbstractFilterer
{

    protected $Permissions;

    public function setPermissions(PermissionsInterface $Permissions)
    {
        $this->Permissions = $Permissions;
    }

    public function setNodePermissions(NodePermissions $NodePermissions)
    {
        $this->NodePermissions = $NodePermissions;
    }

    protected function getDefaultMethod()
    {
        return "pass";
    }

    protected function passNode()
    {
        $action      = $this->getParameter('action');

        return $this->NodePermissions->check($action, $this->getLocal('NodeRef'));
    }

    /**
     * Checks if the specified action is granted to the current user
     *
     * Expected Params:
     *  action string The action string to check
     *
     * @return boolean True if the user is allowed access
     */
    public function pass()
    {
        $action      = $this->getParameter('action');

        return $this->Permissions->checkPermission($action, $this->getLocal('SiteSlug'));
    }

    /**
     * Checks if the specified action is denied to the current user
     *
     * Expected Params:
     *  action string The action string to check
     *
     * @return boolean True if the user is denied access
     */
    public function noPass()
    {
        return !$this->pass();
    }

}
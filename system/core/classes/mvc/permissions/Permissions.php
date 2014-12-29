<?php
/**
 * Permissions
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
 * @version     $Id: Permissions.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Permissions
 *
 * @package     CrowdFusion
 */
class Permissions implements PermissionsInterface
{
    public function checkPermission($permissionSlug, $siteSlug = null)
    {
        return true;
    }

    public function addPermission(Plugin $plugin, $permissionSlug, $title = '')
    {
        return true;
    }

    public function permissionExists($permissionSlug)
    {
        return false;
    }

    public function getPermittedSites($actionString)
    {
        return array();
    }

    public function checkUserPermission(NodeRef $memberRef, $permissionSlug, $siteSlug = null)
    {
        return true;
    }

    public function getUserPermittedSites(NodeRef $memberRef, $actionString)
    {
        return array();
    }

} // END class Permissions

<?php
/**
 * PermissionsInterface
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
 * @version     $Id: PermissionsInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PermissionsInterface
 *
 * @package     CrowdFusion
 */
interface PermissionsInterface
{
    /**
     * Determines if the current user has permission to the {@link $actionString} specified
     *
     * @param string $permissionSlug             The action string to test
     *
     * @return boolean
     */
    public function checkPermission($permissionSlug, $siteSlug = null);

    public function getPermittedSites($actionString);

    public function addPermission(Plugin $plugin, $permissionSlug, $title = '');

    public function permissionExists($permissionSlug);


    public function checkUserPermission(NodeRef $memberRef, $permissionSlug, $siteSlug = null);

    public function getUserPermittedSites(NodeRef $memberRef, $actionString);

}
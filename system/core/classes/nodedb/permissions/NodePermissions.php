<?php
/**
 * NodePermissions
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
 * @version     $Id: NodePermissions.php 2029 2010-02-18 15:41:13Z ryans $
 */

/**
 * NodePermissions
 *
 * @package     CrowdFusion
 */
class NodePermissions
{
    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    protected $byElementPermissions = array();


    protected function checkInternal($action, NodeRef &$nodeRef, &$newData = null, $throw = true, $isRead = false)
    {
        $obj = new Transport();
        $obj->Permitted = true;
        $obj->CachePermission = true;


        $checkedCache = false;
        $elementSlug = $nodeRef->getElement()->getSlug();

        if(array_key_exists($elementSlug, $this->byElementPermissions)
            && array_key_exists($action, $this->byElementPermissions[$elementSlug]))
        {
            $obj->Permitted = $this->byElementPermissions[$nodeRef->getElement()->getSlug()][$action];
            $checkedCache = true;
        }

        if(!$checkedCache)
        {

//        if(!$isRead)
//            $this->Events->trigger('Node.permit.write', $obj, $nodeRef, $newData);
//
//        if ($obj->isPermitted()) {
//            $this->Events->trigger('Node.permit.'.$action, $obj, $nodeRef, $newData);
//        }

//        if ($obj->isPermitted())
            foreach ((array)$nodeRef->getElement()->getAspects() as $aspect) {
                if(!$isRead)
                    $this->Events->trigger('Node.@'.$aspect->Slug.'.permit.write', $obj, $nodeRef, $newData);

                if (!$obj->isPermitted())
                    break;

                $this->Events->trigger('Node.@'.$aspect->Slug.'.permit.'.$action, $obj, $nodeRef, $newData);

                if (!$obj->isPermitted())
                    break;

            }

            if($obj->isCachePermission())
            {
                if(!array_key_exists($elementSlug, $this->byElementPermissions))
                    $this->byElementPermissions[$elementSlug] = array();

                $this->byElementPermissions[$elementSlug][$action] = $obj->Permitted;
            }

        }

        if (!$obj->isPermitted() && $throw)
            throw new PermissionsException('Permission denied to '.$action.' '.$nodeRef->getElement()->getName());

        return $obj->isPermitted();
    }

    /**
     * Returns true if the user is permitted to access the node
     *
     * @param $action
     * @param $nodeRef
     * @param $newData
     * @param $isRead
     * @return unknown_type
     */
    public function check($action, NodeRef &$nodeRef, &$newData = null, $isRead = false)
    {
        return $this->checkInternal($action, $nodeRef, $newData, false, $isRead);
    }

    /**
     * Throws a PermissionsException if the user is unable to access the node, otherwise returns true
     *
     * @param $action
     * @param $nodeRef
     * @param $newData
     * @param $isRead
     * @return unknown_type
     */
    public function checkThrow($action, NodeRef &$nodeRef, &$newData = null, $isRead = false)
    {
        return $this->checkInternal($action, $nodeRef, $newData, true, $isRead);
    }

}
<?php
/**
 * NodePartials
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
 * @version     $Id: NodePartials.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodePartials
 *
 * @package     CrowdFusion
 */
class NodePartials extends Object
{

    public function __construct($MetaPartials = '', $OutPartials = '', $InPartials = '')
    {
        $this->fields['MetaPartials']    = $MetaPartials;
        $this->fields['OutPartials']     = $OutPartials;
        $this->fields['InPartials']      = $InPartials;

        $this->fields['RestrictedMetaPartials'] = '';
        $this->fields['RestrictedOutPartials'] = '';
        $this->fields['RestrictedInPartials'] = '';

        $this->fields['ResolveLinks'] = true;
    }


    public function setResolveLinks($resolve)
    {
        $this->fields['ResolveLinks'] = StringUtils::strToBool($resolve);
    }

    public function isResolveLinks()
    {
        return $this->fields['ResolveLinks'];
    }

    public function increaseMetaPartials($MetaName) {
        if($MetaName == 'fields' || $MetaName == 'all')
            $this->fields['MetaPartials'] = 'all';
        else if(!empty($MetaName))
            $this->fields['MetaPartials'] = PartialUtils::increasePartials($this->fields['MetaPartials'], '#'.ltrim($MetaName, '#'));
    }

    public function increaseOutPartials($role)
    {
        if($role == 'all')
            $this->fields['OutPartials'] = 'all';
        else if($role == 'fields')
            $this->fields['OutPartials'] = PartialUtils::increasePartials($this->fields['OutPartials'], 'fields');
        else if(!empty($role))
            $this->fields['OutPartials'] = PartialUtils::increasePartials($this->fields['OutPartials'], (strpos($role, '#') !== FALSE?$role:'#'.ltrim($role, '#')));
    }

    public function increaseInPartials($role)
    {
        if($role == 'all')
            $this->fields['InPartials'] = 'all';
        else if($role == 'fields')
            $this->fields['InPartials'] = PartialUtils::increasePartials($this->fields['InPartials'], 'fields');
        else if(!empty($role))
            $this->fields['InPartials'] = PartialUtils::increasePartials($this->fields['InPartials'], (strpos($role, '#') !== FALSE?$role:'#'.ltrim($role, '#')));

//        $this->fields['InPartials'] = PartialUtils::increasePartials($this->fields['InPartials'], '#'.ltrim($role, '#'));
    }

    public function decreaseMetaPartials($MetaName) {
        $this->fields['MetaPartials'] = PartialUtils::decreasePartials($this->fields['MetaPartials'], '#'.ltrim($MetaName, '#'));
    }

    public function decreaseOutPartials($role)
    {
        $this->fields['OutPartials'] = PartialUtils::decreasePartials($this->fields['OutPartials'], '#'.ltrim($role, '#'));
    }

    public function decreaseInPartials($role)
    {
        $this->fields['InPartials'] = PartialUtils::decreasePartials($this->fields['InPartials'], '#'.ltrim($role, '#'));
    }


    public function hasMetaPartials() {
        return !empty($this->fields['MetaPartials']);
    }

    public function hasOutPartials() {
        return !empty($this->fields['OutPartials']);
    }

    public function hasInPartials() {
        return !empty($this->fields['InPartials']);
    }


    public function getMetaPartials() {
        return $this->fields['MetaPartials'];
    }

    public function getOutPartials() {
        return $this->fields['OutPartials'];
    }

    public function getInPartials() {
        return $this->fields['InPartials'];
    }

    public function setMetaPartials($partials) {
        $this->fields['MetaPartials'] = $partials;
    }

    public function setOutPartials($partials) {
        $this->fields['OutPartials'] = $partials;
    }

    public function setInPartials($partials) {
        $this->fields['InPartials'] = $partials;
    }

    /* Restricted partials */
    public function increaseRestrictedMetaPartials($MetaName) {
        $this->fields['RestrictedMetaPartials'] = PartialUtils::increasePartials($this->fields['RestrictedMetaPartials'], '#'.ltrim($MetaName, '#'));
    }

    public function increaseRestrictedOutPartials($role)
    {
        $this->fields['RestrictedOutPartials'] = PartialUtils::increasePartials($this->fields['RestrictedOutPartials'], '#'.ltrim($role, '#'));
    }

    public function increaseRestrictedInPartials($role)
    {
        $this->fields['RestrictedInPartials'] = PartialUtils::increasePartials($this->fields['RestrictedInPartials'], '#'.ltrim($role, '#'));
    }

    public function decreaseRestrictedMetaPartials($MetaName) {
        $this->fields['RestrictedMetaPartials'] = PartialUtils::decreasePartials($this->fields['RestrictedMetaPartials'], '#'.ltrim($MetaName, '#'));
    }

    public function decreaseRestrictedOutPartials($role)
    {
        $this->fields['RestrictedOutPartials'] = PartialUtils::decreasePartials($this->fields['RestrictedOutPartials'], '#'.ltrim($role, '#'));
    }

    public function decreaseRestrictedInPartials($role)
    {
        $this->fields['RestrictedInPartials'] = PartialUtils::decreasePartials($this->fields['RestrictedInPartials'], '#'.ltrim($role, '#'));
    }


    public function setRestrictedMetaPartials($partials) {
        $this->fields['RestrictedMetaPartials'] = $partials;
    }

    public function setRestrictedOutPartials($partials) {
        $this->fields['RestrictedOutPartials'] = $partials;
    }

    public function setRestrictedInPartials($partials) {
        $this->fields['RestrictedInPartials'] = $partials;
    }

    public function hasRestrictedMetaPartials() {
        return !empty($this->fields['RestrictedMetaPartials']);
    }

    public function hasRestrictedOutPartials() {
        return !empty($this->fields['RestrictedOutPartials']);
    }

    public function hasRestrictedInPartials() {
        return !empty($this->fields['RestrictedInPartials']);
    }

    public function getRestrictedMetaPartials() {
        return $this->fields['RestrictedMetaPartials'];
    }

    public function getRestrictedOutPartials() {
        return $this->fields['RestrictedOutPartials'];
    }

    public function getRestrictedInPartials() {
        return $this->fields['RestrictedInPartials'];
    }


    public function __toString()
    {
        return "Meta.select: [{$this->fields['MetaPartials']}], OutTags.select: [{$this->fields['OutPartials']}], InTags.select: [{$this->fields['InPartials']}], "./*Sections.select: [{$this->fields['sectionPartials}], */"Meta.restrict [{$this->fields['RestrictedMetaPartials']}], OutTags.restrict [{$this->fields['RestrictedOutPartials']}], InTags.restrict [{$this->fields['RestrictedInPartials']}]";
    }
}

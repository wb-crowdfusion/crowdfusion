<?php
/**
 * DefaultBindHandler
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
 * @version     $Id: DefaultBindHandler.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * DefaultBindHandler
 *
 * @package     CrowdFusion
 */
class DefaultBindHandler
{
    public function setNodeBinder($NodeBinder)
    {
        $this->NodeBinder = $NodeBinder;
    }

    protected function getAspect($eventName)
    {
        $at = strpos($eventName, '@');
        $dot = strpos(substr($eventName, $at+1), '.');

        return substr($eventName, $at+1, $dot);
    }

    public function bind(array $context, $action, Node &$node, Errors &$errors, array $fields, array $rawFields)
    {
        $this->NodeBinder->bindAllForAspect($this->getAspect($context['name']), $node, $errors, $fields, $rawFields);
    }

    public function bindNothing($action, Node &$node, Errors &$errors, array $fields, array $rawFields)
    {
        // intentionally do nothing to prevent binding form variables
    }

}
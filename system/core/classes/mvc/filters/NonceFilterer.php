<?php
/**
 * NonceFilterer
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
 * @version     $Id: NonceFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NonceFilterer
 *
 * @package     CrowdFusion
 */
class NonceFilterer extends AbstractFilterer
{

    protected $Nonces = null;

    public function setNonces(Nonces $Nonces)
    {
        $this->Nonces = $Nonces;
    }

    protected function getDefaultMethod()
    {
        return "nonce";
    }


    /**
     * Creates a nonce for the current or specified action
     *
     * Expected Param:
     *  action string (optional) The action that the nonce will be valid for.
     *                If not specified, the current action will be used
     *
     * @return string
     */
    protected function nonce()
    {
        $action = ($this->getParameter('action') == null) ?
                    $this->RequestContext->getControls()->getControl('action')
                  : $this->getParameter('action');

        return $this->Nonces->create($action);
    }

}
<?php
/**
 * DesignInterceptor
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
 * @version     $Id: DesignInterceptor.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * DesignInterceptor
 *
 * @package     CrowdFusion
 */
class DesignInterceptor
{
    public function __construct(Request $Request, Response $Response, $siteDomain, $context, $viewDirectory, $deviceView, $design)
    {
        $this->Request = $Request;
        $this->Response = $Response;

        $this->siteDomain = $siteDomain;
        $this->context = $context;
        $this->viewDirectory = $viewDirectory;
        $this->deviceView = $deviceView;
        $this->design = $design;
    }

    public function preDeploy()
    {
        $redirect = false;

        if (($deviceView = $this->Request->getParameter('device_view')) !== null) {
            $redirect = true;
            $deviceView = preg_replace('/[^a-zA-Z0-9_\-]+/', '', $deviceView);

            if (empty($deviceView)) {
                $this->Response->clearCookie('DEVICE_VIEW');
            } elseif (!is_dir("{$this->viewDirectory}/{$this->context}/{$this->siteDomain}/{$deviceView}")) {
                $this->deviceView = 'main';
                $this->Response->clearCookie('DEVICE_VIEW');
            } else {
                $this->deviceView = $deviceView;
                $this->Response->sendCookie('DEVICE_VIEW', $deviceView);
            }
        }

        if (($design = $this->Request->getParameter('design_switch')) !== null) {
            $redirect = true;
            $design = preg_replace('/[^a-zA-Z0-9_\-]+/', '', $design);

            if (empty($design)) {
                $this->Response->clearCookie('DESIGN');
            } elseif (!is_dir("{$this->viewDirectory}/{$this->context}/{$this->siteDomain}/{$this->deviceView}/{$design}")) {
                $this->Response->clearCookie('DESIGN');
            } else {
                $this->Response->sendCookie('DESIGN', $design);
            }
        }

        if ($redirect) {
            $this->Response->sendRedirect($this->Request->getAdjustedRequestURI());
        }
    }
}
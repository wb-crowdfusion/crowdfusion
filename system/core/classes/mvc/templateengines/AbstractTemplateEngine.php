<?php   
/**
 * AbstractTemplateEngine
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
 * @version     $Id: AbstractTemplateEngine.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractTemplateEngine
 *
 * @package     CrowdFusion
 */
abstract class AbstractTemplateEngine implements TemplateEngineInterface {

    protected $TemplateService;
    protected $Request;
    protected $Response;
    protected $RequestContext;
    protected $ControllerManager;
    protected $FilterManager;

    public function __construct(TemplateService $TemplateService, Request $Request, Response $Response, RequestContext $RequestContext, FilterManagerInterface $FilterManager, ControllerManagerInterface $ControllerManager)
    {
        $this->TemplateService = $TemplateService;
        $this->Request = $Request;
        $this->Response = $Response;
        $this->RequestContext = $RequestContext;
        $this->ControllerManager = $ControllerManager;
        $this->FilterManager = $FilterManager;

    }

}
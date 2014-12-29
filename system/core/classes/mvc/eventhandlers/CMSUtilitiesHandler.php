<?php
/**
 * CMSUtilitiesHandler
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
 * @version     $Id: CMSUtilitiesHandler.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * CMSUtilitiesHandler
 *
 * @package     CrowdFusion
 */
class CMSUtilitiesHandler
{

    public function setSiteService(SiteService $SiteService)
    {
        $this->SiteService = $SiteService;
    }

    public function setRequestContext(RequestContext $RequestContext)
    {
        $this->RequestContext = $RequestContext;
    }

    protected $theme = null;
    public function setCmsTheme($cmsTheme)
    {
        $this->theme = $cmsTheme;
    }


    public function addSiteLink(Transport &$output)
    {
        $site = $this->SiteService->getAnchoredSite();

        $output->String .=
        '<strong class="site"><a href="'.$site->getBaseURL().'" target="_blank">'.$site->getDomain().'</a></strong>
        ';
    }

    public function addHelpLink(Transport &$output)
    {
        $output->String .= '
         <a href="help/" class="help">Help</a>
        ';
    }

    public function addTheme(Transport $output)
    {
        if($this->theme == null)
            return;

        $output->String .= <<<EOD
            {% asset css?src=css/themes/{$this->theme}.css&min=true %}
EOD;
    }
}
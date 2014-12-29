<?php
/**
 * AbstractAspectsCmsBuilder
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
 * @version     $Id: EditAspectsCmsBuilder.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractAspectsCmsBuilder
 *
 * @package     CrowdFusion
 */
abstract class AbstractAspectsCmsBuilder extends AbstractCmsBuilder {

    protected $ElementService;
    protected $TemplateService;
    protected $PluginService;

    public function setElementService($ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function setPluginService($PluginService)
    {
        $this->PluginService = $PluginService;
    }

    public function setTemplateService($TemplateService)
    {
        $this->TemplateService = $TemplateService;
    }

    protected function getTemplate($aspect)
    {
        return "{$aspect->Slug}-edit.xmod";
    }

    protected function _xmodule() {

        $element = $this->ElementService->getBySlug($this->globals['INPUT_ELEMENT']);

        $ignoreAspects = array();
        $ignore = $this->getParameter('ignore');
        if(!empty($ignore))
            $ignoreAspects = StringUtils::smartExplode($ignore);

        $aspects =$element->getAspects();

        $ordered = array();
        foreach((array)$aspects as $aspect){
            if(in_array($aspect['Slug'], $ignoreAspects))
                continue;

            $plugin  = $this->PluginService->getByID($aspect['PluginID']);
            $ordered[$plugin->Priority][] = $aspect;
        }
        ksort($ordered);

        foreach($ordered as $priority => $aspects)
            foreach($aspects as $aspect){
            $template = $this->getTemplate($aspect);
            if($this->TemplateService->fileExists($template))
                $this->xhtml[] = StringUtils::l("{% template {$template}?inherit=true %}");
            }

        $str = StringUtils::l("{% begin contents %}");

        if(!empty($this->js)) {
            $str .= StringUtils::l('<script type="text/javascript">');
            $str .= StringUtils::l();
            $str .= StringUtils::l('    $(document).ready(function() {');
            $str .= StringUtils::l();
            foreach((array)$this->js as $line)
                $str .= StringUtils::l($line);
            $str .= StringUtils::l();
            $str .= StringUtils::l('    });');
            $str .= StringUtils::l();
            $str .= StringUtils::l('</script>');
        }

        if(!empty($this->xhtml)) {
            foreach((array)$this->xhtml as $line)
                $str .= StringUtils::l($line);
        }

        $str .= StringUtils::l("{% end %}");

        return $str;
    }
}
<?php
/**
 * PHPTemplateEngine
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
 * @version     $Id: PHPTemplateEngine.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PHPTemplateEngine
 *
 * @package     CrowdFusion
 */
class PHPTemplateEngine extends AbstractTemplateEngine
{


    public function firstPass($viewName, $contentType, &$globals, RendererInterface $renderer)
    {
        $result = $this->parseTemplateIncludes("{% template {$viewName} %}", $contentType, null, $globals, $renderer);

        return $result;
    }


    public function parseTemplateIncludes($unparsedContent, $contentType, $parentTemplate, &$globals, RendererInterface $renderer, $isFinalPass = false, $isDependentPass = false)
    {
        // does nothing
    }


    public function finalPass($unparsedContent, $contentType, &$globals, RendererInterface $renderer)
    {


    }

    public function loadFromCache(Template $template)
    {

    }

    public function prepareForCache(Template $template)
    {

    }


    public function loadTemplateExtended(Template $template, &$globals)
    {


    }

    public function processTemplateContents(Template $template,
                                            &$globals,
                                            RendererInterface $renderer,
                                            $isFinalPass = false,
                                            $isDependentPass = false)
    {


    }


    public function processTemplateSetGlobals(Template $template, &$globals, $isFinalPass = false)
    {


    }


}
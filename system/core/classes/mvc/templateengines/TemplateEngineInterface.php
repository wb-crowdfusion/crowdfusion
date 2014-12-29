<?php
/**
 * TemplateEngineInterface
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
 * @version     $Id: TemplateEngineInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * A template engine understands how to read template files and parse them into actual output.
 *
 * @package     CrowdFusion
 */
interface TemplateEngineInterface
{
    /**
     * A template can include other templates inside of it, similar to a require() or include() in php.
     * This function takes the {@link $unparsedContent} and expands it to include all of the other templates,
     * depending on the expected format for this template engine.
     * It passes control to the Renderer for processing the included templates.
     *
     * It returns the fully expanded version of {@link $unparsedContent}
     *
     * @param string            $unparsedContent The content that needs to be "expanded"
     * @param string            $contentType     The content type
     * @param Template          $parentTemplate  The parent template or null
     * @param array             &$globals        An array of globals for this template
     * @param RendererInterface $renderer        The renderer that manages this template engine
     * @param boolean           $isFinalPass     If set to true, this is the final pass of this content
     * @param boolean           $isDependentPass If set to true, this is the dependent pass
     *
     * @return string The "fully-expanded" content
     */
    public function parseTemplateIncludes($unparsedContent, $contentType, $parentTemplate, &$globals,
                                          RendererInterface $renderer, $isFinalPass = false, $isDependentPass = false);

    /**
     * Returns the processed template specified by {@link $viewName}.
     * This is the first pass of the template and is used to expand it fully before any interpolation occurs.
     *
     * @param string            $viewName    The name of the template to render
     * @param string            $contentType The content type
     * @param array             &$globals    An array of globals to replace in the templates
     * @param RendererInterface $renderer    The renderer that manages this template engine
     *
     * @return string the fully expanded and content
     */
    public function firstPass($viewName, $contentType, &$globals, RendererInterface $renderer);

    /**
     * Performs the final parse of the content, intended to handle the globals.
     *
     * @param string            $unparsedContent The unparsed content to analyze
     * @param string            $contentType     The content type
     * @param array             &$globals        An array of globals
     * @param RendererInterface $renderer        The renderer that manages this template engine
     *
     * @return string the final content to pass to the renderer
     */
    public function finalPass($unparsedContent, $contentType, &$globals, RendererInterface $renderer);

    /**
     * Loads the contents of the Template before caching and processing
     *
     * @param Template $template
     * @param array    $globals
     * @return Template
     */
    public function loadTemplateExtended(Template $template, &$globals);

    /**
     * Loads the items from the datasource and any other variables and performs
     * the interpolation from the {@link $globals}
     *
     * @param Template          $template        The template to process
     * @param array             &$globals        The globals to use
     * @param RendererInterface $renderer        The renderer that manages this template engine
     * @param boolean           $isFinalPass     If set to true, this is the final pass of this content
     * @param boolean           $isDependentPass If set to true, this is the dependent pass
     *
     * @return string The parsed content
     */
    public function processTemplateContents(Template $template, &$globals, RendererInterface $renderer,
                                            $isFinalPass = false, $isDependentPass = false);

    /**
     * Extracts all the template globals from the {@link $template}
     *
     * @param Template $template    The template to process
     * @param array    &$globals    The globals array
     * @param boolean  $isFinalPass If true, this is the final pass of the template
     *
     * @return array The globals that were set
     */
    public function processTemplateSetGlobals(Template $template, &$globals, $isFinalPass = false);

    /**
     * Loads the specified template from cache
     *
     * @param Template $template The template to load
     *
     * @return Template the template, processed after loading from cache.
     */
    public function loadFromCache(Template $template);

    /**
     * Prepares the template to be stored in cache
     *
     * @param Template $template The template to store
     *
     * @return Template the template, prepared for caching.
     */
    public function prepareForCache(Template $template);

}
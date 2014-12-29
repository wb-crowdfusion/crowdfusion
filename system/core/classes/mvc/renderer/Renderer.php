<?php
/**
 * Renderer
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
 * @version     $Id: Renderer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * The Renderer uses one or more TemplateEngineInterface instances to parse and render a template file. Each template
 * engine
 * can render one or more types of templates.  Therefore a renderer can render a multitude of template formats and
 * a template engine can [optionally] support format-differing included templates.
 *
 * The Renderer depends on a list of view resolvers and template engines:
 *   viewResolvers - An ordered array or Iterator of ViewResolverInterface instances.  These are use to resolve
 *                   the logical view name into a View object which is passed to the renderer for rendering.
 *
 * @package     CrowdFusion
 */
class Renderer implements RendererInterface {

    protected $Logger;
    protected $Benchmark;
    protected $TemplateCache;

    protected $templateEngines = null;
    protected $defaultViewType;

    protected $invokedTemplateEngines = array();

    protected $view;
    protected $contentType;

    protected $topTemplate;
    protected $noTemplatesCalled = true;

    protected $cacheEnabled;
    protected $pushCache;

    protected $throwRenderExceptions = true;
    protected $benchmarkRendering = false;

   /**
    * Default constructor.
    *
    * @param array $templateEngines An keyed array of template engine instances. Keys should be the lower case type
    *                               representing the template engine (i.e. cft, json, php).
    *
    * @throws Exception If the template engine parameter is empty.
    */
    public function __construct(array $templateEngines, $defaultViewType)
    {
        if (empty($templateEngines))
            throw new Exception("Renderer must have at least one TemplateEngine.");

        $this->templateEngines = $templateEngines;
        $this->defaultViewType = $defaultViewType;
    }

    public function setBenchmark(BenchmarkInterface $benchmark)
    {
        $this->Benchmark = $benchmark;
    }

    public function setLogger($Logger)
    {
        $this->Logger = $Logger;
    }

    public function setTemplateCache($TemplateCache)
    {
        $this->TemplateCache = $TemplateCache;
    }

    public function setThrowRenderExceptions($throwRenderExceptions)
    {
        $this->throwRenderExceptions = $throwRenderExceptions;
    }

    public function setBenchmarkRendering($benchmarkRendering)
    {
        $this->benchmarkRendering = $benchmarkRendering;
    }

   /**
     * @param string $contentType The name of the handler that the template engine should use to render the output.
     *                            Types: XHTML, RSS, XML, JSON, JSONp, JavaScript, SerializedPHP, PlainText, HTTPGET
     *                            A template engine may or may not support all of these handlers.
     *
     * @throws Exception If template engine doesn't support requested content type (thrown from TemplateEngine)
     * @throws Exception If no template engine is capable of rendering the view template supplied
     * @throws Exception If the template engine doesn't support the handler type.
     */
    public function renderView(View $view, $contentType, $cacheEnabled = true, $pushCache = false)
    {

        $this->view              = $view;
        $this->contentType       = $contentType;
        $this->cacheEnabled      = $cacheEnabled;
        $this->pushCache         = $pushCache;
        $this->noTemplatesCalled = true;


        //VIEW DATA (MODEL) IS USED AS GLOBAL VARIABLES DURING TEMPLATE RENDER
        $globals = $view->getData() == null ? array() : $view->getData();

        $templateEngine = $this->getTemplateEngine($view->getName());

        $this->Logger->debug("Starting render [{$view->getName()}] - ".($cacheEnabled==true?'WITH':'WITHOUT')." CACHING");
        if($this->benchmarkRendering) $this->Benchmark->start('render-'. $view->getName());

        $content = $templateEngine->firstPass($view->getName(), $contentType, $globals, $this);

        foreach ($this->invokedTemplateEngines as $te) {
            $this->Logger->debug("Starting finalPass for [".get_class($te)."]");
            $content = $te->finalPass($content, $contentType, $globals, $this);
        }

        $this->Logger->debug("Completed rendering view [{$view->getName()}]");
        if($this->benchmarkRendering) $this->Benchmark->end('render-'. $view->getName());

        $this->noTemplatesCalled = true;

        return array($content, $globals);
    }

    public function processTemplate(Template $template, $contentType, &$globals, $isFinalPass = false, $isDependentPass = false)
    {
        if ((!$isFinalPass && $template->isDeferred())
             || ($isDependentPass && !$template->isDependent())) {
            $this->Logger->debug("Deferring template [{$template->getName()}]");

            // defer my execution
            return $template->getMatchString();
        }

        $this->Logger->debug("Processing template [{$template->getName()}]");
        if($this->benchmarkRendering) $this->Benchmark->start('process-template-'. $template->getName());

        if ($this->noTemplatesCalled == true) {
            $this->topTemplate       = $template;
            $this->noTemplatesCalled = false;

            $template->setTopTemplate(true);
        }

        $templateEngine = $this->getTemplateEngine($template->getName());

        try {
            $template = $templateEngine->loadTemplateExtended($template, $globals);

        } catch(Exception $e)
        {
            if($this->throwRenderExceptions)
                throw $e;
            else {
                $this->Logger->error($e->getMessage() . "\nURL: " . URLUtils::fullUrl());
                return '';
            }

        }

        $cacheThisModule = false;
        $usedCache       = false;

        if ($this->cacheEnabled && $template->isCacheable())
            $cacheThisModule = true;


        if ($cacheThisModule) {

            $cacheKey = $this->TemplateCache->getTemplateCacheKey($template, $globals);

            if (!in_array($template->getName(), (array)$this->pushCache) && $cachedTemplate = $this->TemplateCache->get($cacheKey)) {

                // check the timestamp
                //if ($cachedTemplate->getTimestamp() == $template->getTimestamp()) {
                    $this->Logger->debug("Loaded from cache [".$template->getName()."]");

                    $locals = $template->Locals;

                    // load from cache
                    $template  = $templateEngine->loadFromCache($cachedTemplate);
                    $template->Locals = $locals;

                    $content   = $template->getProcessedContent();
                    $usedCache = true;

                //}

            }


        }


        if (!$usedCache) {

            $this->Logger->debug("Processing [{$template->getName()}]");

            // process the template and all dependent includes
            $content = $templateEngine->processTemplateContents($template, $globals, $this, $isFinalPass, $isDependentPass);

            $template->setProcessedContent($content);

            if ($cacheThisModule) {
                // snapshot of the cache contents at this moment
                $toBeCachedTemplate = clone $templateEngine->prepareForCache($template);

            }

        }

        // parse all independent includes, outside of caching (recurse!)
        $content = $templateEngine->parseTemplateIncludes($content, $contentType, $template, $globals, $this, $isFinalPass, false);

        if (!$usedCache) {
            $this->Logger->debug("Processing template set globals [{$template->getName()}]...");

            // parse template set globals
            $templateSetGlobals = $templateEngine->processTemplateSetGlobals($template, $globals, $isFinalPass);

        } else {
            $templateSetGlobals = $cachedTemplate->getTemplateSetGlobals();
        }

        $globals = array_merge($globals, $templateSetGlobals);

        // store in cache
        if (!$usedCache && $cacheThisModule) {
            $toBeCachedTemplate->setIndependentTemplates($template->isIndependentTemplates());
            $toBeCachedTemplate->setParsedIndependent(true);

            if($template->isTopTemplate())
                $toBeCachedTemplate->setTemplateSetGlobals($globals);
            else
                $toBeCachedTemplate->setTemplateSetGlobals($templateSetGlobals);

            $this->Logger->debug("Storing in cache [".$toBeCachedTemplate->getName()."]");
            unset($toBeCachedTemplate->Locals);
            unset($toBeCachedTemplate->Data);
            unset($toBeCachedTemplate->Contents);
            unset($toBeCachedTemplate->SetMatches);
            unset($toBeCachedTemplate->File);
            unset($toBeCachedTemplate->TemplateBlocks);

            $this->TemplateCache->putTemplate($cacheKey, $toBeCachedTemplate);
        }

        if($this->benchmarkRendering) $this->Benchmark->end('process-template-'. $template->getName());

        return $content;
    }

    protected function getTemplateEngine($templateName)
    {

        $type = false;
        if (($lastPeriod = strrpos($templateName, '.')) !== false) {
            $type = substr($templateName, $lastPeriod+1);
        }

        if(empty($type))
            $type = $this->defaultViewType;

        if (array_key_exists($type,$this->templateEngines)) {
            $te = $this->templateEngines[$type];
            if (!in_array($te, $this->invokedTemplateEngines))
                $this->invokedTemplateEngines[] = $te;
            return $te;
        }

        throw new Exception("No template engine capable of rendering views of type: '{$type}'");
    }
}

<?php
/**
 * XModTemplateEngine
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
 * @version     $Id: XModTemplateEngine.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * XModTemplateEngine
 *
 * @package     CrowdFusion
 */
class XModTemplateEngine extends CFTemplateEngine
{
    protected $ApplicationContext;

    public function setApplicationContext($ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

//    protected function loadTemplateExtended(Template $template, &$globals)
//    {
////        $this->Benchmark->start('load-template-extended-'. $template->getName());
//
//        $file = $this->TemplateService->getFile($template->getName());
//
//        if (empty($file))
//            throw new TemplateEngineException("Template file not found for template name: ".$template->getName());
//
//        $template->setFile($this->TemplateService->getFile($template->getName()));
//        $template->setTimestamp(filemtime($template->getFile()));
//
//        $this->Logger->debug("Loading template file: ".$template->getFile());
//
//
//        $this->loadTemplateContents($template, $globals);
//
//        $template->setLocals($params = array());
//        $template->setTemplateSetGlobals($templateSetGlobals = array());
//
//        return $template;
//
//    }

    public function loadTemplateExtended(Template $template, &$globals)
    {
        if($this->benchmarkRendering) $this->Benchmark->start('load-template-extended-'. $template->getName());

//        if(!($templateFileCached = $this->TemplateCache->get('t:'.$template->getName())))
//        {

            if (!$this->TemplateService->fileExists($template->getName()))
                throw new TemplateEngineException("Template file not found for template name: ".$template->getName());

            $file = $this->TemplateService->resolveFile($template->getName());

            $template->setFile($file->getLocalPath());

            $this->Logger->debug("Loading template file: ".$template->getFile());

            $this->loadTemplateContents($template, $globals);

            $this->Logger->debug('Parsing set blocks: '.$template->getName());

            if (preg_match_all("/\{\%\s+(set|setGlobal|appendGlobal)\s+([^\%]+?)\s+\%\}[\n\r\t]*(.*?)[\n\r\t]*\{\%\s+end\s+\%\}/s",
                               $template->getContents() ,$setMatches, PREG_SET_ORDER)) {
                $template->setSetMatches($setMatches);
            } else {
                $template->setSetMatches(array());
            }

//            $this->TemplateCache->put('t:'.$template->getName(), $template, 0);

//        } else {
//
//            $template->setFile($templateFileCached->getFile());
//            $template->setSetMatches($templateFileCached->getSetMatches());
//            $template->setContents($templateFileCached->getContents());
//
//        }

        $params = $template->getLocals();

        $templateSetGlobals = array();

        foreach ($template->getSetMatches() as $m) {
            $m[3] = $this->parseFormatVariables($m[3], $this->getConstants());

            switch ($m[1]) {
            case 'set':
                if (array_key_exists($m[2], $params))
                    continue;

                $val           = $this->parseFormatVariablesAndFilters($m[3], $params);
                $params[$m[2]] = $val;
                break;
            case 'setGlobal':
                $templateSetGlobals[$m[2]] = $m[3];
                break;
            case 'appendGlobal':
                $val = $this->parseFormatVariablesAndFilters($m[3], $params);
                if (!array_key_exists($m[2], $globals))
                    $templateSetGlobals[$m[2]] = $val;
                else {
                    $templateSetGlobals[$m[2]]  = $globals[$m[2]];
                    $templateSetGlobals[$m[2]] .= $val;
                }
                break;
            }

        }

//        $this->Logger->debug(__CLASS__,$params);

        $template->setLocals($params);
        $template->setTemplateSetGlobals($templateSetGlobals);

        if (!empty($params['CacheTime']))
            $template->setCacheTime($params['CacheTime']);

        if ( /*(!$template->isDependent()) &&*/   // if we're independent (or the top module)
          $template->getCacheTime() > 0 &&   // if CacheTime is greater than 0
          (empty($params['NoCache']) || StringUtils::strToBool($params['NoCache']) == false)) {       // if this module is not marked as NoCache
            $template->setCacheable(true);
        }

        if($this->benchmarkRendering) $this->Benchmark->end('load-template-extended-'. $template->getName());

        return $template;
    }

    protected function loadTemplateContents(Template $template, $globals)
    {
        $file = $template->getFile();

        $xml = file_get_contents($file);

        if(preg_match("/xbuilder\=\"(c:)?([^\"]+)\"/si", $xml, $m)) {
            $parserClass = $m[2];
        } else {
            throw new Exception('XModule ['.$template->getName().'] failed to specify XBuilder class');
        }

        $builder = $this->ApplicationContext->object($parserClass);

        if(!$builder instanceof XBuilderInterface)
            throw new TemplateEngineException('XBuilder class does not implement XBuilderInterface: '.get_class($builder));

        // TODO: cache module contents into a file and parse as usual
        $contents = $builder->handleBuild($xml, $file, $template, array_merge($globals, $this->getConstants()));

        // remove comments
        $contents = preg_replace("/{%\s+\/\*.*\*\/\s+%}/sU",'', $contents);

        if(empty($contents)) {
            throw new TemplateEngineException("Template empty: " .$template->getName());
        }

        $template->setContents($contents);

        return $template;

    }


}
<?php
/**
 * CFTemplateEngine
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
 * @version     $Id: CFTemplateEngine.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * CFTemplateEngine
 *
 * @package     CrowdFusion
 */
class CFTemplateEngine extends AbstractTemplateEngine
{

    protected $Benchmark;
    protected $Logger;
    protected $DateFactory;

    protected $Session;
    protected $FileCache;
    protected $AssetAggregator;
    protected $NodeMapper;


    protected $environment;
    protected $deviceView;
    protected $systemEmailAddress;
    protected $systemVersion;
    protected $isDevelopmentMode;

    protected $constants = false;

    private $deferredContents = array();
    private $deferredCount    = 1;

    private $deferredOrDependentParamCount = 1;
    private $deferredOrDependentParams     = array();

    protected $excludeCounter = 1;

    protected $lastType = false;
    protected $lastKey = '';
    protected $lastAssetBlock = '';

    protected $assetPaths = array();
    protected $previousParamArray = array();

    protected $assetBlocksToReplace = array();
    protected $tempAssetsFV;
    protected $tempAssetsFinalParse;

    protected $throwRenderExceptions = true;
    protected $benchmarkRendering = false;

    protected $TemplateCache;

    public function setThrowRenderExceptions($throwRenderExceptions)
    {
        $this->throwRenderExceptions = $throwRenderExceptions;
    }

    public function setBenchmarkRendering($benchmarkRendering)
    {
        $this->benchmarkRendering = $benchmarkRendering;
    }

    public function setBenchmark($Benchmark)
    {
        $this->Benchmark = $Benchmark;
    }

    public function setLogger($Logger)
    {
        $this->Logger = $Logger;
    }

    public function setSession($Session)
    {
        $this->Session = $Session;
    }

    public function setDateFactory($DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    public function setNodeMapper($NodeMapper)
    {
        $this->NodeMapper = $NodeMapper;
    }

    public function setFileCache($FileCache)
    {
        $this->FileCache = $FileCache;
    }

    public function setTemplateCache(TemplateCache $TemplateCache)
    {
        $this->TemplateCache = $TemplateCache;
    }

    public function setAssetAggregator($AssetAggregator)
    {
        $this->AssetAggregator = $AssetAggregator;
    }

    public function setDevelopmentMode($isDevelopmentMode)
    {
        $this->isDevelopmentMode = $isDevelopmentMode;
    }

    public function setSystemEmailAddress($systemEmailAddress)
    {
        $this->systemEmailAddress = $systemEmailAddress;
    }

    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param string $deviceView
     */
    public function setDeviceView($deviceView)
    {
        $this->deviceView = $deviceView;
    }

    /**
     * @param string $systemVersion
     */
    public function setSystemVersion($systemVersion)
    {
        $this->systemVersion = $systemVersion;
    }

    public function __construct(TemplateService $TemplateService, Request $Request, Response $Response, RequestContext $RequestContext, FilterManagerInterface $FilterManager, ControllerManagerInterface $ControllerManager)
    {
        parent::__construct($TemplateService, $Request, $Response, $RequestContext, $FilterManager, $ControllerManager);
    }

    public function parseTemplateIncludes($unparsedContent, $contentType, $parentTemplate, &$globals, RendererInterface $renderer, $isFinalPass = false, $isDependentPass = false)
    {
        if(!is_null($parentTemplate) && !$isDependentPass && $parentTemplate->isParsedIndependent() && !$parentTemplate->isIndependentTemplates())
            return $unparsedContent;

        if(!is_null($parentTemplate) && !$isDependentPass)
            $this->Logger->debug("Parsing independent includes for [{$parentTemplate->getName()}]");

        $parsedContent = '';
//        $parentTemplate->setIndependentTemplates(false);

        while (($pos = strpos($unparsedContent,'{% template')) !== false) {

            if(!is_null($parentTemplate) && !$isDependentPass) {
                $parentTemplate->setIndependentTemplates(true);
            }
//            $parentTemplate->setParsedIndependent(true);

            $parsedContent .=  substr($unparsedContent,0,$pos);
            $unparsedContent = substr($unparsedContent,$pos);

            if (!preg_match("/{% template (.+?)(\?(.+?))?\s+%}/s",$unparsedContent,$m))
                if($this->throwRenderExceptions)
                    throw new Exception("BAD TEMPLATE CALL: $unparsedContent");
                else {
                    $this->Logger->error("BAD TEMPLATE CALL: $unparsedContent" . "\nURL: " . URLUtils::fullUrl());
                    continue;
                }
            $unparsedContent = substr($unparsedContent, strlen($m[0]));

            $this->Logger->debug("Found template call: ".$m[0]);

            try {
                $template = $this->loadTemplate($m, $contentType, $parentTemplate, $globals, $isFinalPass, $isDependentPass);
            } catch(Exception $e)
            {
                if($this->throwRenderExceptions)
                    throw $e;
                else {
                    $this->Logger->error($e->getMessage() . "\nURL: " . URLUtils::fullUrl());
                    continue;
                }
            }

            $this->Logger->debug("Loaded template [".$template->getName()."], now processing");

            $newParsedContent = $renderer->processTemplate($template, $contentType, $globals, $isFinalPass, $isDependentPass);

            $parsedContent .= $newParsedContent;
        }

        return $parsedContent . $unparsedContent;
    }

    public function firstPass($viewName, $contentType, &$globals, RendererInterface $renderer)
    {
        if($this->benchmarkRendering) $this->Benchmark->start('first-pass-'. $viewName);

        foreach($globals as $name => $value)
            if(is_string($value))
                $globals[$name] = preg_replace("/\%([A-Za-z#][A-Za-z0-9\-\_\:\.\[\]\=\#\~]+)\%/", '&#37;$1&#37;', str_replace(array('{%', '%}'), array('{&#37;', '&#37;}'), $value));

        $result = $this->parseTemplateIncludes("{% template {$viewName} %}", $contentType, null, $globals, $renderer);
        if($this->benchmarkRendering) $this->Benchmark->end('first-pass-'. $viewName);

        return $result;
    }

    public function finalPass($unparsedContent, $contentType, &$globals, RendererInterface $renderer)
    {

        foreach ($globals as $name => $value) {
            if (!is_array($value) && !is_object($value))
            {
                $value = $this->parseFormatVariablesAndFilters($value, $globals, true);

                if (substr($value, 0, 1) == '%' && substr($value, -1) == '%' && strpos(substr($value, 1, -1), '%') === false)
                    $value = '';
                $globals[$name] = $value;
            }
        }

        $this->globals = $globals;
        // final parse
        if($this->benchmarkRendering) $this->Benchmark->start('final-pass');

        // if(LOG_ENABLE) {
        //     System::log(self::$logType, 'Final Parse');
//             $this->Logger->error('Globals ['.print_r($this->globals, true).']');
        // }

        $this->Logger->debug("Parsing deferrals");

//        $this->Logger->error('parse deferrals');

        $renderout = $this->parseDeferrals($unparsedContent);

//        $this->Logger->error(print_r($this->deferredContents, true));

//        $this->Logger->error($renderout);

        $this->Logger->debug("Parsing conditions");

        $renderout = $this->parseConditions($renderout, $this->globals, true);

        $this->Logger->debug("Parsing other templates");

        $renderout = $this->parseTemplateIncludes($renderout, $contentType, null, $this->globals, $renderer, true);

        $this->Logger->debug("Parsing format variables and filters");

        $renderout = $this->parseFormatVariables($renderout, $this->globals, true);
        $renderout = $this->parseFilters($renderout, $this->globals, true);
        $renderout = $this->parseAssets($renderout, $this->globals, true);

//        $this->Logger->error('last deferrals');
        $this->Logger->debug("Parsing last deferrals");
//        $this->Benchmark->start('parse-deferrals');

        //$this->Logger->error($renderout);
        ///$this->Logger->error(print_r($this->globals, true));
//        $this->Logger->error(print_r($this->deferredContents, true));

        if(!empty($this->deferredContents))
            while (strpos($renderout, "{% defer ") !== FALSE) {
                $renderout = $this->parseDeferrals($renderout);
                $renderout = $this->parseFormatVariables($renderout, $this->globals, true);
                $renderout = $this->parseFilters($renderout, $this->globals, true);
                $renderout = $this->parseAssets($renderout, $this->globals, true);
            }

        $renderout = $this->parseFormatVariables($renderout, $this->getConstants(), true);

//        $this->Benchmark->end('parse-deferrals');

        $this->Logger->debug("Parsing assets");

        $renderout = $this->parseFinalAssets($renderout);

        if($this->benchmarkRendering) $this->Benchmark->end('final-pass');

        return $renderout;

    }

    protected function loadTemplate($moduleParts, $contentType, $parentTemplate, &$globals, $isFinalPass, $isDependentPass)
    {
        $this->globals = $globals;

        $template = new Template();

        $template->setContentType($contentType);

        $moduleString = $moduleParts[0];
        $moduleName   = $moduleParts[1];
        $moduleParams = isset($moduleParts[3]) ? $moduleParts[3] : null;
        $parentLocals = !empty($parentTemplate) ? $parentTemplate->getLocals() : array();

        $template->setName( $this->parseFormatVariables($moduleName, $parentLocals) );

        if($this->benchmarkRendering) $this->Benchmark->start('load-template-'. $template->getName());
        $matchString = '{% template '.$template->getName();

//        $this->Logger->error($template->getName());
        $usesGlobals = false;
        $deferable = false;

        $params = array();
        if (!empty($moduleParams)) {
            $matchString .= '?';

            $nvps = explode('&',html_entity_decode($moduleParams, ENT_QUOTES));
            foreach ($nvps as $param) {
                $split = explode('=',$param, 2);
                if ($split === FALSE || count($split) == 1)
                    throw new Exception('Bad NVP in template call: '.$moduleString);

                list($name,$value) = $split;

                $dataparam = null;
                $previouslyFound = array();

                if (substr($value, 0, 1) == '%' && substr($value, -1) == '%' && strpos(substr($value, 1, -1), '%') === FALSE) {
                    $paramval = 'Data:'.substr($value, 1, -1);
                    $dataparam = substr($value, 1, -1);
//                    $template->setDependent(true);
//                    $this->Logger->debug('Marking template ['.$template->getName().'] as dependent');
                } else {
                    $paramval = $value;

                    if (strpos($paramval, 'Data:') === 0) {
                        $dataparam = substr($paramval, 5);
//                        $template->setDependent(true);
//                        $this->Logger->debug('Marking template ['.$template->getName().'] as dependent');
//                        $this->Logger->error('Marking template ['.$template->getName().'] as dependent');
                    } elseif (strpos($paramval, 'Global:') === 0) {
                        $dataparam = substr($paramval, 7);
                        $deferable = true;
                        $usesGlobals = true;
                    }
                }

                if (strlen($dataparam) >= 3 && strpos($dataparam, '%') !== false) {
                    $dataparam = $this->parseFormatVariables($dataparam, $parentLocals);
                }

                switch($name) {
                    // manual datasource parameter
                    case 'data':
                        if (!empty($params))
                            throw new Exception("Error in template call $moduleString :: data parameter must be first parameter");

                        $template->setDependent(true);

                        if ($dataparam == 'DataSource') {
                            if (empty($parentTemplate))
                                throw new Exception('Impossible to use DataSource from non-existent parent template');

                            $template->setData($parentTemplate->getData());
                        } elseif (array_key_exists($dataparam, $parentLocals)) {
                            $template->setData($parentLocals[$dataparam]);
                        } elseif (array_key_exists($dataparam, $this->getConstants())) {
                            $template->setData($this->getConstant($dataparam));
                        } elseif (array_key_exists($dataparam, $globals)) {
                            $template->setData($globals[$dataparam]);
                        } elseif(strpos($dataparam, '.') !== false) {
                            $parts = explode('.', $dataparam);
                            if(!empty($parts))
                            {
                                $broken = true;
                                $array = $parentLocals;
                                foreach($parts as $pname)
                                {
                                    if(isset($array[$pname])) {
                                        $array = $array[$pname];
                                        $broken = false;
                                    } else {
                                        $broken = true;
                                        break;
                                    }
                                }
                                if(!$broken)
                                    $value = $array;
                            }
                        }

                        if ($template->hasData() && !is_array($template->getData()))
                            $template->setData(array($template->getData()));
//                            throw new Exception("Invalid data parameter in template call ".$moduleString.", must be an array :: $dataparam\n");

                        break;

                    // inherit all locals from parent
                    case 'inherit':
                        if(StringUtils::strToBool($value) == true)
                        {
                            $template->setDependent(true);

                            $newParents = $parentLocals;
                            unset($newParents['CacheTime']);
                            unset($newParents['DataSource']);
                            $params = array_merge($newParents, $params);
                        }
                        break;

                    case 'dependent':
                        if(StringUtils::strToBool($value) == true)
                            $template->setDependent(true);

                        break;
                    default:

                        if ($dataparam != null) {
                            if($usesGlobals)
                            {
                                if (!$isFinalPass) {
                                    if(!empty($previouslyFound))
                                    {
                                        $this->globals = array_merge($this->globals, $previouslyFound);
                                        $previouslyFound = array();
                                    }

                                    if (array_key_exists($dataparam, $parentLocals)) {
                                        $this->globals[$dataparam] =  $parentLocals[$dataparam];
                                        $value = 'Global:'.$dataparam;
                                    }

                                } else {


                                    if (array_key_exists($dataparam, $this->getConstants())) {
                                        $value =  $this->getConstant($dataparam);
                                    } elseif (array_key_exists($dataparam, $globals)) {
                                        $value =  $globals[$dataparam];
                                    }

                                }


                                $template->setDeferred(true);

                            } else {

                                if (array_key_exists($dataparam, $this->getConstants())) {
                                    $value =  $this->getConstant($dataparam);
//                                } elseif (array_key_exists($dataparam, $this->deferredOrDependentParams)) {
//                                    $value =  $this->deferredOrDependentParams[$dataparam];
//                                } elseif ((!$deferable || $isFinalPass) && array_key_exists($dataparam, $globals)) {
//                                    $value =  $globals[$dataparam];
//                                } elseif (!$deferable && array_key_exists($dataparam, $parentLocals)) {
                                } elseif (array_key_exists($dataparam, $parentLocals)) {
                                    $value =  $parentLocals[$dataparam];
                                    $previouslyFound[$dataparam] = $value;

                                    // if we're deferred or only parsing data dependent modules and this module is independent,
                                    //  save the passed parameters for later
//                                    $this->Logger->error('Deferred? '.$template->isDeferred());
//                                    $this->Logger->error('Independent? '.($isDependentPass && !$template->isDependent()));
//                                    if ($template->isDeferred() || ($isDependentPass && !$template->isDependent())) {
//                                        $c = $this->deferredOrDependentParamCount++;
//                                        $this->deferredOrDependentParams[$dataparam.$c] = $parentLocals[$dataparam];
//                                        $paramval = 'Data:'.$dataparam.$c;
//                                        $this->Logger->error('Defer ['.$name.'] param: '.$paramval);
//                                    } else {
//                                        $value =  $parentLocals[$dataparam];
//                                    }
                                } elseif(strpos($dataparam, '.') !== false)
                                {
                                    $parts = explode('.', $dataparam);
                                    if(!empty($parts))
                                    {
                                        $broken = true;
                                        $array = $parentLocals;
                                        foreach($parts as $pname)
                                        {
                                            if(isset($array[$pname])) {
                                                $array = $array[$pname];
                                                $broken = false;
                                            } else {
                                                $broken = true;
                                                break;
                                            }
                                        }
                                        if(!$broken)
                                            $value = $array;
                                        else
                                            $value = '';
//                                        elseif (!$isFinalPass && $deferable)
//                                            $template->setDeferred(true);
                                    }
//                                } elseif (!$isFinalPass && $deferable) {
//                                    // defer setting a value until the final pass
//                                    $template->setDeferred(true);
                                } else {
                                    $value = '';
                                }
                            }
                        } elseif (strlen($value) >= 4 && strpos($value, '%') !== false) {
                            $value    = $this->parseFormatVariables($value, $parentLocals);
                            $paramval = $value;
                        }


                        $params[$name] = $value;
                        break;

                }

                $matchString = $matchString.$name.'='.$paramval.'&';

            }

            $matchString = substr($matchString, 0, -1);
        }

        $matchString .= ' %}';

        $template->setMatchString($matchString);
        $template->setLocals($params);


        $this->Logger->debug("Loaded template call: ".$matchString);

        if($this->benchmarkRendering) $this->Benchmark->end('load-template-'. $template->getName());

//        $template = $this->loadTemplateExtended($template, $globals);

        return $template;
    }

    public function loadTemplateExtended(Template $template, &$globals)
    {
        if($this->benchmarkRendering) $this->Benchmark->start('load-template-extended-'. $template->getName());

        if(!$template->hasFile() || !$template->hasSetMatches() || !$template->hasContents())
        {

            if(!($templateFileCached = $this->TemplateCache->get('t:'.$template->getName())))
            {

                if (!$this->TemplateService->fileExists($template->getName()))
                    throw new TemplateEngineException("Template file not found for template name: ".$template->getName());

                $file = $this->TemplateService->resolveFile($template->getName());

                $template->setFile($file->getLocalPath());

                $this->Logger->debug("Loading template file: ".$template->getFile());

                $this->loadTemplateContents($template, $globals);

                $this->Logger->debug('Parsing set blocks: '.$template->getName());

                $head = $template->getContents();
                if(($blockpos = strpos($head, '{% begin')) !== FALSE)
                    $head = substr($head, 0, $blockpos);

                if (preg_match_all("/\{\%\s+(set|setGlobal|appendGlobal)\s+([^\%]+?)\s+\%\}[\n\r\t]*(.*?)[\n\r\t]*\{\%\s+end\s+\%\}/s",
                                   $head ,$setMatches, PREG_SET_ORDER)) {
                    $template->setSetMatches($setMatches);
                } else {
                    $template->setSetMatches(array());
                }

                $this->TemplateCache->put('t:'.$template->getName(), $template, 0);

            } else {

                $template->setFile($templateFileCached->getFile());
                $template->setSetMatches($templateFileCached->getSetMatches());
                $template->setContents($templateFileCached->getContents());

            }

        }

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

    protected function getConstant($name)
    {
        $arr = $this->getConstants();
        if (array_key_exists($name, $arr))
            return $arr[$name];

        return null;
    }

    protected function getConstants()
    {
        if ($this->constants === false) {
            // TODO: use an event to populate constants

            $constants = array();

            $constants['FULL_URL']          = $this->Request->getFullURL();
            $constants['BASE_URL']          = $this->Request->getBaseURL();
            $constants['REQUEST_URI']       = $this->Request->getAdjustedRequestURI();
            $constants['TIME']              = $this->DateFactory->newLocalDate();
            $constants['USER_LOGGED_IN']    = ''.$this->RequestContext->isAuthenticatedUser();
            $constants['ENVIRONMENT'] = $this->environment;
            $constants['SERVER_DEVELOPMENT_MODE'] = $this->isDevelopmentMode;
            $constants['SYSTEM_EMAIL_ADDRESS'] = $this->systemEmailAddress;
            $constants['DEVICE_VIEW'] = $this->deviceView;
            $constants['SYSTEM_VERSION'] = $this->systemVersion;


            foreach ((array)$this->RequestContext->getControls()->getControls() as $name => $val) {
                $constants['CONTROL_'.strtoupper(str_replace(' ','_',$name))] = $val;
            }

            if ($this->RequestContext->getSite() != null) {
                $site = $this->RequestContext->getSite();
                if ($sf = $site->getStorageFacilityInfo()) {
                    if (isset($sf['assets'])) {
                        $constants['ASSETS_BASEURL'] = $sf['assets']->getBaseURL();
                    }
                    if (isset($sf['media'])) {
                        $constants['MEDIA_BASEURL'] = $sf['media']->getBaseURL();
                    }
                }

                foreach ((array)$site->toArray() as $name => $val) {
                    $constants['SITE_'.strtoupper(str_replace(' ','_',$name))] = $val;
                }
            }

            if ($this->RequestContext->getUser() != null) {
                $constants['USER'] = $this->RequestContext->getUser();
                foreach ($this->RequestContext->getUser()->toArray() as $name => $val)
                {
                    $constants['USER_'.strtoupper(str_replace(' ','_',$name))] = $val;
                }
            }

            foreach ((array)$this->Request->getParameters() as $name => $val) {
                if(!is_array($val))
                    $constants['INPUT_'.strtoupper(str_replace(' ','_',$name))] = $val;
                else {
                    foreach($val as $n2 => $v2)
                        $constants['INPUT_'.strtoupper(str_replace(' ','_',$name.'_'.$n2))] = $v2;
                }
            }

            foreach ((array)$this->Session->getSessionAttribute() as $name => $val) {
                if (!is_array($val))
                    $constants['SESSION_'.strtoupper(str_replace(' ','_',$name))] = $val;
                else {
                    foreach($val as $n2 => $v2)
                        $constants['SESSION_'.strtoupper(str_replace(' ','_',$name.'_'.$n2))] = $v2;
                }
            }

            foreach ((array)$this->Session->getFlashAttributes() as $name => $val) {
                if (!is_array($val))
                    $constants['FLASH_'.strtoupper(str_replace(' ','_',$name))] = $val;
                else {
                    foreach($val as $n2 => $v2)
                        $constants['FLASH_'.strtoupper(str_replace(' ','_',$name.'_'.$n2))] = $v2;
                }
            }

            foreach ($this->Request->getCookies() as $name => $val) {
                if (!is_array($val))
                    $constants['COOKIE_'.strtoupper(str_replace(' ','_',$name))] = $val;
            }

            foreach ($this->Request->getServerAttributes() as $name => $val) {
                if (!is_array($val))
                    $constants['SERVER_'.strtoupper(str_replace(' ','_',$name))] = $val;
            }

            $this->constants = $constants;
        }

        return $this->constants;
    }





    public function processTemplateContents(Template $template,
                                            &$globals,
                                            RendererInterface $renderer,
                                            $isFinalPass = false,
                                            $isDependentPass = false)
    {
        $this->globals = $globals;
        $newParsedContent = '';

//        $template = $this->loadTemplatePreProcess($template, $globals);

        if($this->benchmarkRendering) $this->Benchmark->start('process-template-contents-'. $template->getName());

        try {

            // load the template blocks
            $this->loadTemplateBlocks($template, $globals);

            // load my data
            $this->loadTemplateDataSource($template, $globals);

            // parse the template blocks
            $newParsedContent = $this->processTemplateBlocks($template, $globals, $renderer);

            $newParsedContent = $this->filterTemplateContents($newParsedContent, $template, $globals);

        } catch(NotFoundException $nfe) {
            throw $nfe;
        } catch(Exception $e)
        {
            if($this->throwRenderExceptions)
                throw $e;
            else {
                $this->Logger->error($e->getMessage() . "\n" . $e->getTraceAsString() . "\nURL: " . URLUtils::fullUrl() . "\nCONTENT:\n$newParsedContent");
                return '';
            }
        }

        if($this->benchmarkRendering) $this->Benchmark->end('process-template-contents-'. $template->getName());

        return $newParsedContent;
    }

    protected function filterTemplateContents($parsedContent, Template $template, &$globals)
    {
        return $parsedContent;
    }


    public function processTemplateSetGlobals(Template $template, &$globals, $isFinalPass = false)
    {
        if($this->benchmarkRendering) $this->Benchmark->start('process-template-set-globals-'. $template->getName());
        $templateSetGlobals = $template->getTemplateSetGlobals();
        $locals = $template->getLocals();
        foreach ($templateSetGlobals as $name => $value) {
            if (!is_array($value) && !is_object($value))
            {

                $value = $this->parseFormatVariablesAndFilters($value, array_merge($locals, $globals), $isFinalPass);

//                if (substr($value, 0, 1) == '%' && substr($value, -1) == '%' && strpos(substr($value, 1, -1), '%') === false)
//                    $value = '';

            }
            // $this->globals[$name] = $value;
            $templateSetGlobals[$name] = $value;
        }

        if($this->benchmarkRendering) $this->Benchmark->end('process-template-set-globals-'. $template->getName());

        return $templateSetGlobals;
    }

    public function loadFromCache(Template $template)
    {
//        $this->Logger->error(print_r($template->getDeferredContents(), true));
//        $this->Logger->error(print_r($template->getDeferredParams(), true));

        if($this->benchmarkRendering) $this->Benchmark->start('load-template-from-cache-'. $template->getName());
        $newParsedContent = $template->getProcessedContent();

        // TODO: THIS IS A PROBLEM!

        foreach ((array)$template->getDeferredContents() as $num => $conts) {
//            $this->Logger->error($num.': '.$conts);
//            $newcount = $this->deferredCount++;
            $newParsedContent = str_replace('{% defer '.$num.' %}',$conts, $newParsedContent);
//            if (!isset($this->deferredContents[$num]))
//                $this->deferredContents[$num] = $conts;

//            $this->deferredContents[$num.'.'.$newcount] = $conts;
        }
//        foreach ((array)$template->getDeferredParams() as $name => $val) {
//            $newcount = $this->deferredOrDependentParamCount++;
//            $newParsedContent = str_replace('Data:'.$name,'Data:'.$name.'_'.$newcount, $newParsedContent);
//            if (!isset($this->deferredOrDependentParams[$name]))
//                $this->deferredOrDependentParams[$name] = $val;
//            $this->deferredOrDependentParams[$name.'_'.$newcount] = $val;
//        }
        $template->setProcessedContent($newParsedContent);
        if($this->benchmarkRendering) $this->Benchmark->end('load-template-from-cache-'. $template->getName());
        return $template;
    }

    public function prepareForCache(Template $template)
    {
        // TODO: THIS IS A PROBLEM!
        if($this->benchmarkRendering) $this->Benchmark->start('prepare-template-for-cache-'. $template->getName());
        $template->setDeferredContents($this->deferredContents);
//        $deferredParams = array();
//        foreach($this->deferredOrDependentParams as $name => $param)
//            $deferredParams[$name] = "".$param;

//        $template->setDeferredParams($deferredParams);
        if($this->benchmarkRendering) $this->Benchmark->end('prepare-template-for-cache-'. $template->getName());

        return $template;
    }


    protected function loadTemplateContents(Template $template, $globals)
    {

        if($this->benchmarkRendering) $this->Benchmark->start('load-template-contents-'. $template->getName());
        $file = $template->getFile();

        if (!($contents = $this->FileCache->getFileContents($file)))
            $contents = file_get_contents($file);

        // remove comments
        $contents = preg_replace("/{%\s+\/\*.*\*\/\s+%}/sU",'', $contents);

//        if (empty($contents))
//            throw new TemplateEngineException("Template empty: " .$template->getName());

//         $this->Logger->debug("Found contents:\n".$contents);

        $template->setContents($contents);
        if($this->benchmarkRendering) $this->Benchmark->end('load-template-contents-'. $template->getName());

        return $contents;
    }

    protected function loadTemplateBlocks(Template &$template)
    {
        if($this->benchmarkRendering) $this->Benchmark->start('load-template-blocks-'. $template->getName());
        $unparsedContent = $template->getContents();

        $templateBlocks = array();
        $parsedContent  = '';
        $foundOne       = false;

        while (($pos = strpos($unparsedContent,'{% begin ')) !== false) {
            $foundOne       = true;
            $parsedContent .= substr($unparsedContent,0,$pos);
            $content        = substr($unparsedContent,$pos);

            $eol = strpos($content, "\n");

            $line = $content;
            if ($eol !== FALSE)
                $line = substr($content, 0, ($eol !== FALSE?$eol:0));

            if (preg_match("/\{\%\s+begin\s+([^\%]+?)\s+\%\}[\n\r\t]*/s",$line,$m)) {
                $blockName = $m[1];

                $endpos = strpos($content,'{% end %}');
                if($endpos === FALSE)
                    throw new Exception('No end block found for :'.$m[0]);

                $blockContents = trim(substr($content, strlen($m[0]), $endpos-strlen($m[0])), "\n\r\t");
                $blockContents = $this->parseFormatVariables($blockContents, $this->getConstants());

                $templateBlocks[$blockName] = $blockContents;
            } else
                throw new Exception('Invalid begin block found: '.$line);

            $unparsedContent = substr($content, $endpos + strlen('{% end %}'));
        }

        if (!$foundOne) {
            $blockContents              = $this->parseFormatVariables($unparsedContent, $this->getConstants());
            $templateBlocks['contents'] = $blockContents;
        }

        $template->setTemplateBlocks($templateBlocks);
        if($this->benchmarkRendering) $this->Benchmark->end('load-template-blocks-'. $template->getName());

        return $template;

    }

    protected function loadTemplateDataSource(Template &$template)
    {
        if($this->benchmarkRendering) $this->Benchmark->start('load-template-datasource-'. $template->getName());

        $locals = $template->getLocals();

        $datasource = null;
        if (isset($locals['DataSource']))
            $datasource = $locals['DataSource'];

        $this->Logger->debug('DataSource ['.$datasource.']');

        $data = null;

        if ($template->getData() !== null)
            $data = $template->getData();

        if ($datasource != null && !empty($datasource)) {
            $preloadedData = $data !== null ? $data : array();

            $data = $this->ControllerManager->invokeDatasource($datasource, $preloadedData, $locals);

            // datasources can change locals manually
            $template->setLocals($locals);
            //
            // if($module['name'] == $this->view && count($data) == 1) {
            //     if(isset($data[0]['id'])) {
            //         $this->globals['SingleID'] = $data[0]['id'];
            //         $templateSetGlobals['SingleID'] = $data[0]['id'];
            //     }
            //     if(isset($data[0]['Slug'])) {
            //         $module['SingleSlug'] = $data[0]['Slug'];
            //         $this->globals['SingleSlug'] = $module['SingleSlug'];
            //         $templateSetGlobals['SingleSlug'] = $data[0]['Slug'];
            //     }
            //     if(isset($data[0]['Type'])) {
            //         $module['TypeSlug'] = $data[0]['Type'];
            //         $this->globals['SingleType'] = $module['TypeSlug'];
            //         $templateSetGlobals['SingleType'] = $data[0]['Type'];
            //     }
            //     if(isset($data[0]['Status'])) {
            //         $this->globals['SingleStatus'] = $data[0]['Status'];
            //         $templateSetGlobals['SingleStatus'] = $data[0]['Status'];
            //     }
            //     if(isset($data[0]['ActiveDate'])) {
            //         $this->globals['SingleActiveDate'] = $data[0]['ActiveDate'];
            //         $templateSetGlobals['SingleActiveDate'] = $data[0]['ActiveDate'];
            //     }
            //     $module['ElementName'] = $element;
            //     $this->globals['SingleElement'] = $element;
            //     $templateSetGlobals['SingleElement'] = $element;
            // }
        }

        if($this->benchmarkRendering) $this->Benchmark->end('load-template-datasource-'. $template->getName());

        $template->setData($data);

        return $template;
    }

    protected function processTemplateBlocks(Template $template, &$globals, RendererInterface $renderer)
    {
        if($this->benchmarkRendering) $this->Benchmark->start('process-template-blocks-'. $template->getName());

        $templateBlocks = $template->getTemplateBlocks();

        $data    = $template->getData();
        $locals  = $template->getLocals();
        $handler = ($template->getContentType()!='html' && $template->getContentType()!='') ?
                     $template->getContentType() . '-'
                   : '';

        $renderout = '';
        $contents = '';
        $parsedContents = '';

        $locals['DisplayRecords'] = sizeof($data);
        $locals['Count']  = 1;

        if (!empty($data)) {
            //if(LOG_ENABLE) System::log(self::$logType, 'Parsing contents block');

            if (!isset($templateBlocks[$handler.'contents']) && !isset($templateBlocks[$handler.'header']) && !isset($templateBlocks[$handler.'exec']))
                throw new Exception('Template ['.$template->getName().'] is missing a template block for ['.
                                        $handler.'contents] or ['.$handler.'header] or ['.$handler.'exec]');

            $eBlock  = isset($templateBlocks[$handler.'exec'])           ? $templateBlocks[$handler.'exec']           : '';
            $cBlock  = isset($templateBlocks[$handler.'contents'])           ? $templateBlocks[$handler.'contents']           : '';
            $ciBlock = isset($templateBlocks[$handler.'contents-inbetween']) ? $templateBlocks[$handler.'contents-inbetween'] : '';

            if(!empty($eBlock))
            {
                $contents = $eBlock;

                $contents = $this->parseSetters($contents, $locals);
                $contents = $this->parseConditions($contents, $locals);
                $contents = $this->parseFilters($contents, $locals);
                $contents = $this->parseAssets($contents, $locals);

                // parse dependent sub modules
                $template->setLocals($locals);

                // $this->Logger->debug("Parsing dependent includes for [{$template->getName()}]...");
                $contents = $this->parseTemplateIncludes($contents, $template->getContentType(), $template, $globals, $renderer, false, true);
                $contents = $this->parseFormatVariables($contents, $locals);

                $renderout .= $contents;

            } else {

                // List Processing of the Data
                foreach ((array)$data as $row) {
                    $contents = $cBlock;

                    // if(strpos($contents, '{% set ') !== FALSE) {
                    //     while(preg_match("/(.*?)\{\%\s+set\s+([^\%]+?)\s+\%\}\s*(.*?)\s?\{\%\s+endset\s+\%\}\s*(.*)/s",$contents,$m)) {
                    //         if(!array_key_exists($m[2], $row)) {
                    //             $val = $this->parseFormatVariablesAndFilters($m[3], $row);
                    //             $row[$m[2]] = $val;
                    //         }
                    //         $contents = $m[1]. $m[4];
                    //      }
                    // }

                    if ($locals['DisplayRecords'] != $locals['Count'])
                        $contents .= $ciBlock;

                    if (!is_array($row)) {
                        if ($row instanceof Node) {
                            /*
                             * Populating Node itself into the row so it can be used in templates,
                             * passed to events, filters, etc.  see ticket #30
                             * todo: investigate populating 'Node' in populateNodeCheaters directly
                             */
                            $node = $row;
                            $row = $this->NodeMapper->populateNodeCheaters($row)->toArray();
                            $row['Node'] = $node;
                        } else if ($row instanceof Object) {
                            $row = $row->toArray();
                        } else {
                            throw new Exception("data is not an array\n".print_r($row, true));
                        }
                    }

                    $row_locals = array_merge($locals,$row);
                    $row_locals['SerializedData'] = $row;
                    //if(LOG_ENABLE) System::log(self::$logType, 'Locals ['.print_r($locals, true).']');

                    $contents = $this->parseSetters($contents, $row_locals);
                    $contents = $this->parseConditions($contents, $row_locals);
                    $contents = $this->parseFilters($contents, $row_locals);
                    $contents = $this->parseAssets($contents, $row_locals);

                    // parse dependent sub modules
                    $template->setLocals($row_locals);

                    // $this->Logger->debug("Parsing dependent includes for [{$template->getName()}]...");
                    $contents = $this->parseTemplateIncludes($contents, $template->getContentType(), $template, $globals, $renderer, false, true);
                    $contents = $this->parseFormatVariables($contents, $row_locals);

                    $locals['Count']++;

                    $parsedContents .= $contents;

                }

                $locals = $row_locals;
                $template->setLocals($locals);

                // headers and footers can use format variables from the final row
                $renderout = '';

                if (!empty($templateBlocks[$handler.'header'])) {
                    $header = $templateBlocks[$handler.'header'];

                    $header = $this->parseSetters($header, $locals);
                    $header = $this->parseConditions($header, $locals);
                    $header = $this->parseFilters($header, $locals);
                    $header = $this->parseAssets($header, $locals);

                    $template->setLocals($locals);

                    // parse dependent sub modules
                    $header = $this->parseTemplateIncludes($header, $template->getContentType(), $template, $globals, $renderer, false, true);

                    $header = $this->parseFormatVariables($header, $locals);
                    $renderout .= $header;
                }

                $renderout .= $parsedContents;

                if (!empty($templateBlocks[$handler.'footer'])) {
                    $footer = $templateBlocks[$handler.'footer'];

                    $footer = $this->parseSetters($footer, $locals);
                    $footer = $this->parseConditions($footer, $locals);
                    $footer = $this->parseFilters($footer, $locals);
                    $footer = $this->parseAssets($footer, $locals);

                    $template->setLocals($locals);

                    // parse dependent sub modules
                    $footer = $this->parseTemplateIncludes($footer, $template->getContentType(), $template, $globals, $renderer, false, true);

                    $footer = $this->parseFormatVariables($footer, $locals);
                    $renderout .= $footer;
                }
            }

        } else {

            if (!$template->isTopTemplate()
               && !array_key_exists($handler.'contents', $templateBlocks)
               && !array_key_exists($handler.'noresults', $templateBlocks))
                throw new Exception('Template ['.$template->getName().'] is missing a template block for ['.
                                        $handler.'noresults] or ['.$handler.'contents] or ['.$handler.'exec]');

            if (array_key_exists($handler.'noresults', $templateBlocks))
                $renderout = $templateBlocks[$handler.'noresults'];
            elseif ($template->getData() === null && empty($locals['DataSource']) && array_key_exists($handler.'exec', $templateBlocks))
                $renderout = $templateBlocks[$handler.'exec'];
            elseif ($template->getData() === null && empty($locals['DataSource']) && array_key_exists($handler.'contents', $templateBlocks))
                $renderout = $templateBlocks[$handler.'contents'];
            elseif($template->isTopTemplate())
                throw new NotFoundException($template->getName());

            $renderout = $this->parseSetters($renderout, $locals);
            $renderout = $this->parseConditions($renderout, $locals);
            $renderout = $this->parseFilters($renderout, $locals);
            $renderout = $this->parseConditions($renderout, $locals);

            $template->setLocals($locals);

            // parse dependent sub modules
            //$template->setLocals($locals);
            $renderout = $this->parseTemplateIncludes($renderout, $template->getContentType(), $template, $globals, $renderer, false, true);

            $renderout = $this->parseFormatVariables($renderout, $locals);

        }


        //if(LOG_ENABLE) System::log(self::$logType, 'Render out ['.print_r($renderout, true).']');

        if($this->benchmarkRendering) $this->Benchmark->end('process-template-blocks-'. $template->getName());

        return $renderout;
    }

    protected function parseSetters($content, &$locals, $isFinalPass = false)
    {
        $parsedContent = '';

        while (($pos = strpos($content,'{% set ')) !== false) {
            $parsedContent .= substr($content,0,$pos);
            $content = substr($content,$pos);

            try {
                if (preg_match("/\{\%\s+(set)\s+([^\%]+?)\s+\%\}[\n\r\t]*(.*?)[\n\r\t]*\{\%\s+endset\s+\%\}/s",$content,$m)) {
                    $content = substr($content, strlen($m[0]));

                    $m[3] = $this->parseFormatVariables($m[3], $this->getConstants());
                    $val           = $this->parseFormatVariablesAndFilters($m[3], $locals);
                    $locals[$m[2]] = $val;

                    $parsedContent .= '';
                } else {
                    throw new Exception("setter error :". print_r($content, true));
                }
            }catch(Exception $e)
            {
                if($this->throwRenderExceptions)
                    throw $e;
                else {
                    $this->Logger->error($e->getMessage() . "\nURL: " . URLUtils::fullUrl());
                    continue;
                }
            }
        }

        return $parsedContent . $content;

    }


    protected function parseFilters($content, $locals, $isFinalPass = false)
    {
        $parsedContent = '';

        // $this->Benchmark->start('parse-filters');
        //
        // $counter = @++$this->counters['filters'];
        // System::timer('filters-'.$counter);

        while (($pos = strpos($content,'{% filter ')) !== false) {
            $parsedContent .= substr($content,0,$pos);
            $content = substr($content,$pos);

            try {
                if (preg_match("/^\{% filter (.+?)(\?(.+?))? %\}/s",$content,$m)) {
                    $content = substr($content, strlen($m[0]));

                    $params = array();
                    if (isset($m[3])) {
                        $nvps = explode('&',html_entity_decode($m[3], ENT_QUOTES));
                        foreach ((array)$nvps as $param) {
                            if (count($split = explode('=',$param, 2)) == 1)
                                throw new Exception('Bad NVP in filter call: '.$m[0]);

                            list($name, $value) = $split;


                            $dataparam = null;
    //                        $deferable = false;
                            if (substr($value, 0, 1) == '%' && substr($value, -1) == '%' && strpos(substr($value, 1, -1), '%') === false){
                                $paramval  = 'Data:'.substr($value, 1, -1);
                                $dataparam = substr($value, 1, -1);
                            } else {
                                $paramval = $value;

                                if (strpos($paramval, 'Data:') === 0)
                                    $dataparam = substr($paramval, 5);
                                elseif (strpos($paramval, 'Global:') === 0) {
                                    $dataparam = substr($paramval, 7);
    //                                $deferable = true;
                                    if(!$isFinalPass) {
                                        $parsedContent .= $m[0];
                                        continue 2;
                                    }
                                }
                            }

                            if (strlen($dataparam) >= 3 && strpos($dataparam, '%') !== false)
                                $dataparam = $this->parseFormatVariables($dataparam, $locals, $isFinalPass);

                            if ($dataparam != null) {
                                // $value = ''; // initialize value to empty until we find a way to fill it!
                                // TODO: above line is proposed fix for PF #1332. Review/regression testing needed
                                if (array_key_exists($dataparam, $locals))
                                    $value =  $locals[$dataparam];
                                elseif (array_key_exists($dataparam, $this->getConstants()))
                                    $value =  $this->getConstant($dataparam);
    //                            elseif (!$deferable && array_key_exists($dataparam, $this->globals))
    //                                $value =  $this->globals[$dataparam];
    //                            elseif (!$deferable && array_key_exists($dataparam, $this->globals))
    //                                $value =  $this->globals[$dataparam];
                                elseif(strpos($dataparam, '.') !== false)
                                {
                                    $parts = explode('.', $dataparam);
                                    if(!empty($parts))
                                    {
                                        $broken = true;
                                        $array = $locals;
                                        foreach($parts as $pname)
                                        {
                                            if(isset($array[$pname])) {
                                                $array = $array[$pname];
                                                $broken = false;
                                            } else {
                                                $broken = true;
                                                break;
                                            }
                                        }
                                        if(!$broken)
                                            $value = $array;
    //                                    else if(!$isFinalPass && $deferable) {
    //                                        $parsedContent .= $m[0];
    //                                        continue 2;
    //                                    }

                                    }
    //                            }
    //                            elseif(!$isFinalPass && $deferable) {
    //                                $parsedContent .= $m[0];
    //                                continue 2;
                                } else
                                    $value = '';

                            } elseif (strlen($value) >= 4 && strpos($value, '%') !== false) {
                                $value = $this->parseFormatVariables($value, $locals, $isFinalPass);
                            }
                            $params[$name] = $value;

                        }
                    }

                    $parsedContent .= $this->handleFilter($m[1], $params, $locals);
                } else {
                    throw new Exception("filter error :". print_r($content, true));
                }
            }catch(Exception $e)
            {
                if($this->throwRenderExceptions)
                    throw $e;
                else {
                    $this->Logger->error($e->getMessage() . "\nURL: " . URLUtils::fullUrl());
                    continue;
                }
            }
        }
        // System::storeTime('filters-'.$counter);
        // $this->Benchmark->end('parse-filters');
        return $parsedContent . $content;
    }

    protected function handleFilter($filterCall, $params, $locals)
    {
        list($filtererName, $method) = ActionUtils::parseActionDatasource($filterCall, true);

        $filterer = $this->FilterManager->getFiltererByName($filtererName);

        $filterResult = $filterer->handleFilter($filtererName, $method, $params, $locals, array_merge($this->globals, $this->getConstants()));

        if(!$filterer->isAllowTemplateCode())
            $filterResult = preg_replace("/\%([A-Za-z#][A-Za-z0-9\-\_\:\.\[\]\=\#\~]+)\%/", '&#37;$1&#37;', str_replace(array('{%', '%}'), array('{&#37;', '&#37;}'), $filterResult));

        return $filterResult;
    }

    protected function parseDeferrals($content)
    {
        if(empty($this->deferredContents))
            return $content;

        $ret = preg_replace_callback("/\{% defer (.+?) %\}/s",array($this, 'callbackDeferrals'), $content);
        return $ret;
    }

    protected function callbackDeferrals($m)
    {
        $defCount = $m[1];
        if (!isset($this->deferredContents[$defCount]))
            throw new Exception('Deferral not found: '.$defCount);

        $condition = $this->deferredContents[$defCount];

        //$this->Logger->error($condition);

        return $this->parseDeferrals( $this->parseConditions($condition, $this->globals, true) );
    }


    protected function parseConditions($content, $locals, $isFinalPass = false)
    {
        $parsedContent = '';

        // $this->Benchmark->start('parse-conditions');

        while (($pos = strpos($content, '{% endif %}')) !== false) {
            $m[1] = substr($content, 0, $pos);
            $m[2] = substr($content, $pos+11);

            try {
                if (preg_match("/(.*)\{\% if (.+?) \%\}(.*)/s", $m[1], $n)) {
                    $condition = $n[2];

                    list($trueCondition, $falseCondition) = $this->checkElse($n[3]);

                    try {

                        $content = ($this->evaluateCondition($condition, $locals, $isFinalPass) === true ? $trueCondition : $falseCondition) . $m[2];

                        if (strpos($n[1], "{% if ") !== false)
                            $content = $n[1].$content;
                        else
                            $parsedContent .= $n[1];

                    } catch(DeferralException $e) {
                        $count = $this->deferredCount++;
                        //$this->Logger->error('Deferring condition ['.$condition.'] ('.$count.'): '.$e->getMessage());
                        //defer til the end
                        $content = $n[1] . '{% defer ' . $count . ' %}' . $m[2];
                        //$parsedContent .= $n[1];
                        //$this->deferredConditions[$count] = $condition;
                        $t = $trueCondition . ($falseCondition != '' ? '{% else %}' . $falseCondition : '');
                        $this->deferredContents[$count] = $this->parseFormatVariables($this->parseFilters($t, $locals), $locals);
                        $this->deferredContents[$count] = '{% if ' . $condition . ' %}' . $this->deferredContents[$count] . '{% endif %}';
                        // parse filters now in deferred contents for later

                        //continue;
                    }


                } else {
                    throw new Exception("ERROR: No Opening Condition for End Block " . $m[1]);
                }

            }catch(Exception $e)
            {
                if($this->throwRenderExceptions)
                    throw $e;
                else {
                    $content = $m[1] . $m[2];
                    $this->Logger->error($e->getMessage() . "\n" . $e->getTraceAsString() . "\nURL: " . URLUtils::fullUrl() . "\nCONTENT:\n$content");
                    continue;
                }
            }
        }
        // $this->Benchmark->end('parse-conditions');

        return $parsedContent . $content;
    }

    protected function evaluateCondition($condition, $locals, $isFinalPass = false)
    {
        if (count($conds = explode(' || ', $condition)) > 1) {
            foreach ($conds as $condition)
                if ($this->evalOneCondition($condition, $locals, $isFinalPass) == true)
                    return true;
            return false;
        } elseif (count($conds = explode(' && ', $condition)) > 1) {
            foreach ($conds as $condition)
                if ($this->evalOneCondition($condition, $locals, $isFinalPass) !== true)
                    return false;
            return true;
        }

        return $this->evalOneCondition($condition, $locals, $isFinalPass);
    }

    protected function closedCondition($data)
    {
        if (strpos($data,'{% endif %}') === true) {
            if (($pos1 = strpos($data, '{% endif %}')) !== false) {
                $m[1] = substr($data, 0, $pos1);
                $m[2] = substr($data, $pos1+11);
                if (($pos2 = strpos($m[1], '{% if ')) !== false) {
                    $n[1] = substr($m[1], $pos2);
                    $n[2] = substr($m[1], strpos($m[1], ' %}') + 3);

                    if (strpos($n[2], '{% endif %}') !== false)
                        return false;

                    return $this->closedCondition($n[1].$m[2]);
                }

                return false;
            }
        } else {
            if (strpos($data,'{% if ') === true)
                return false;

            return true;
        }
    }

    protected function checkElse($data)
    {
        // This function is splitting the Condition Data into a True and an Else Value.
        // We have to find the right else by making sure we don't take elses from other
        // conditions.
        if (strpos($data,'{% else %}') === false)
            return array($data,'');

        $elses = explode('{% else %}',$data);

        // Lets iterate through the content until we find an else that doesn't have a
        // sub condition in front of it.

        $running = '';
        while (($nests = array_shift($elses)) !== false) {
            $running .= $nests;
            if ($this->closedCondition($running)) {
                $trueCondition = $running;
                $falseCondition = join($elses, '{% else %}');
                return array($trueCondition,$falseCondition);
            }
            $running .= '{% else %}';
        }
        return array($data,'');
    }

    protected function parseFormatVariables($content, $fv, $isFinalPass = false)
    {

        // $this->Benchmark->start('parse-format-vars');

        //if(LOG_ENABLE) System::log(self::$logType, 'Replace Format Variables');


        if (!empty($fv['Count']))
            $content = str_replace('%Count%',$fv['Count'],$content);
        else if ($isFinalPass && !isset($fv['NoFinalClear']))
            $content = str_replace('%Count%','1',$content);

        $this->tempFV         = $fv;
        $this->tempFinalParse = $isFinalPass;

        $ret = preg_replace_callback("/\%([A-Za-z#][A-Za-z0-9\-\_\:\.\[\]\=\#\~]+)\%/", array($this, 'callbackFormatVariables'), $content);

        // $this->Benchmark->end('parse-format-vars');
        return $ret;
    }

    protected function callbackFormatVariables($m)
    {
        if(substr($m[1], 0, 7) == 'Global:')
        {
            if(!$this->tempFinalParse)
                return $m[0];

            $m[1] = substr($m[1], 7);
        }

        if (array_key_exists($m[1], $this->tempFV))
            return $this->filterForOutput($this->tempFV[$m[1]]);
        elseif(strpos($m[1], '.') !== false)
        {
            $parts = explode('.', $m[1]);
            if(!empty($parts))
            {
                $broken = true;
                $array = $this->tempFV;
                foreach($parts as $name)
                {
                    if(isset($array[$name])) {
                        $array = $array[$name];
                        $broken = false;
                    } else {
                        $broken = true;
                        break;
                    }
                }
                if(!$broken)
                    return $this->filterForOutput($array);
            }
        }

        if($this->tempFinalParse && !isset($this->tempFV['NoFinalClear']))
            return '';

        return $m[0];
    }

    protected function filterForOutput($value)
    {
        return str_replace('%', '&#37;', @htmlspecialchars( (string)$value, ENT_QUOTES, 'UTF-8', false ));
    }


    protected function parseFormatVariablesAndFilters($content, $fv, $isFinalPass = false)
    {
        $content = $this->parseConditions($content, $fv, $isFinalPass);
        $content = $this->parseFilters($content, $fv, $isFinalPass);
        $content = $this->parseAssets($content, $fv, $isFinalPass);
        return $this->parseFormatVariables($content, $fv, $isFinalPass);
    }


    protected function parseParameterValue($value, $locals, $isFinalPass = false)
    {
        $dataparam = null;
//        $deferable = false;
        if (substr($value, 0, 1) == '%' && substr($value, -1) == '%' && strpos(substr($value, 1, -1), '%') === false){
            $paramval = 'Data:'.substr($value, 1, -1);
            $dataparam = substr($value, 1, -1);
        } else {
            $paramval = $value;

            if (strpos($paramval, 'Data:') === 0)
                $dataparam = substr($paramval, 5);
            elseif(strpos($paramval, 'Global:') === 0) {
                $dataparam = substr($paramval, 7);
                if(!$isFinalPass)
                    throw new DeferralException('Deferred condition due to parameter: '.$value);
                //$deferable = true;
            }
        }

        if (strlen($dataparam) >= 3 && strpos($dataparam, '%') !== false) {
            $dataparam = $this->parseFormatVariables($dataparam, $locals, $isFinalPass);
        }

        if ($dataparam != null) {
            if (array_key_exists($dataparam, $locals))
                return $locals[$dataparam];
            elseif (array_key_exists($dataparam, $this->getConstants()))
                return $this->getConstant($dataparam);
            elseif (substr($value, 0, 6) == 'Random')
                return rand(1,intVal(substr($value, 6)));
//            elseif (!$deferable && array_key_exists($dataparam, $this->globals))
//                return $this->globals[$dataparam];
//            elseif (array_key_exists($dataparam, $this->globals))
//                return $this->globals[$dataparam];
            elseif(strpos($dataparam, '.') !== false)
            {
                $parts = explode('.', $dataparam);
                if(!empty($parts))
                {
                    $broken = true;
                    $array = $locals;
                    foreach($parts as $name)
                    {
                        if(isset($array[$name])) {
                            $array = $array[$name];
                            $broken = false;
                        } else {
                            $broken = true;
                            break;
                        }
                    }
                    if(!$broken)
                        return $array;
//                    else if(!$isFinalPass && $deferable) {
//                        throw new DeferralException('Deferred condition due to parameter: '.$value);
//                    }
                }
            }
//            elseif(!$isFinalPass && $deferable)
                // defer me
//                throw new DeferralException('Deferred condition due to parameter: '.$value);

            return '';
        }

        if (count($split = explode('?', $value, 2)) == 2) {
            list($method, $args) = $split;
            $nvps = explode('&', html_entity_decode($args, ENT_QUOTES));

            foreach ($nvps as $param) {
                $split = explode('=',$param, 2);
                if ($split === FALSE || count($split) == 1)
                    throw new Exception('Bad NVP in condition: '.$value);

                list($name, $v) = $split;
                $params[$name]      = $this->parseParameterValue($v, $locals, $isFinalPass);
            }

            return $this->handleFilter($method, $params, $locals);
        }

        $val = StringUtils::trimOnce($value, "'");
        if (strlen($val) >= 4 && strpos($val, '%') !== false)
            $val = $this->parseFormatVariables($val, $locals, $isFinalPass);

        return $val;
        //return trim_once($value, "'");
    }

    private function _intmath($val)
    {
        if(is_null($val))
            return $val;
        elseif(''.$val === '')
            return 0;
        elseif(is_numeric(''.$val))
            return intVal(''.$val);
        elseif($val instanceof Date)
            return intVal($val->format('U'));

        require_once PATH_SYSTEM.'/vendors/EvalMath.php';

        return EvalMath::getInstance()->e($val);
    }

    protected function evalOneCondition($condition, $locals, $isFinalPass = false)
    {

        if (count($args = explode(' == ', $condition, 2)) == 2)
            return ($this->_intmath($this->parseParameterValue($args[0], $locals, $isFinalPass)) ===
                    $this->_intmath($this->parseParameterValue($args[1], $locals, $isFinalPass)));
        elseif (count($args = explode(' != ', $condition, 2)) == 2)
            return ($this->_intmath($this->parseParameterValue($args[0], $locals, $isFinalPass)) !==
                    $this->_intmath($this->parseParameterValue($args[1], $locals, $isFinalPass)));
        elseif (count($args = explode(' eq ', $condition, 2)) == 2)
            return (strcmp($this->parseParameterValue($args[0], $locals, $isFinalPass),
                            $this->parseParameterValue($args[1], $locals, $isFinalPass)) == 0);
        elseif (count($args = explode(' !eq ', $condition, 2)) == 2)
            return (strcmp($this->parseParameterValue($args[0], $locals, $isFinalPass),
                    $this->parseParameterValue($args[1], $locals, $isFinalPass)) !== 0);
        elseif (count($args = explode(' instr ', $condition, 2)) == 2)
            return (strpos($this->parseParameterValue($args[1], $locals, $isFinalPass),
                    $this->parseParameterValue($args[0], $locals, $isFinalPass)) !== false);
        elseif (count($args = explode(' !instr ', $condition, 2)) == 2)
            return (strpos($this->parseParameterValue($args[1], $locals, $isFinalPass),
                    $this->parseParameterValue($args[0], $locals, $isFinalPass)) === false);
        elseif (count($args = explode(' > ', $condition, 2)) == 2)
            return ($this->_intmath($this->parseParameterValue($args[0], $locals, $isFinalPass)) >
                    $this->_intmath($this->parseParameterValue($args[1], $locals, $isFinalPass)));
        elseif (count($args = explode(' >= ', $condition, 2)) == 2)
            return ($this->_intmath($this->parseParameterValue($args[0], $locals, $isFinalPass)) >=
                    $this->_intmath($this->parseParameterValue($args[1], $locals, $isFinalPass)));
        elseif (count($args = explode(' < ', $condition, 2)) == 2)
            return ($this->_intmath($this->parseParameterValue($args[0], $locals, $isFinalPass)) <
                    $this->_intmath($this->parseParameterValue($args[1], $locals, $isFinalPass)));
        elseif (count($args = explode(' <= ', $condition, 2)) == 2)
            return ($this->_intmath($this->parseParameterValue($args[0], $locals, $isFinalPass)) <=
                    $this->_intmath($this->parseParameterValue($args[1], $locals, $isFinalPass)));
        elseif (count($args = explode(' ieq ', $condition, 2)) == 2)
            return (strcasecmp($this->parseParameterValue($args[0], $locals, $isFinalPass),
                    $this->parseParameterValue($args[1], $locals, $isFinalPass)) == 0);
        elseif (count($args = explode(' !ieq ', $condition, 2)) == 2)
            return (strcasecmp($this->parseParameterValue($args[0], $locals, $isFinalPass),
                    $this->parseParameterValue($args[1], $locals, $isFinalPass)) !== 0);
        elseif (count($args = explode(' iinstr ', $condition, 2)) == 2)
            return (stripos($this->parseParameterValue($args[1], $locals, $isFinalPass),
                    $this->parseParameterValue($args[0], $locals, $isFinalPass)) !== false);
        elseif (count($args = explode(' !iinstr ', $condition, 2)) == 2)
            return (stripos($this->parseParameterValue($args[1], $locals, $isFinalPass),
                    $this->parseParameterValue($args[0], $locals, $isFinalPass)) === false);
        else {

            if (($pos = strpos($condition, '!')) === 0) {
                if (substr($condition, 1, 5) == 'Data:'
                 || substr($condition, 1, 7) == 'Global:')
                {

//                    $this->Logger->error($condition);
                    $name = substr($condition, 1);
                    try {
                        $val = $this->parseParameterValue($name, $locals, false);

                        if (is_scalar($val)) {
                            $val = (string) $val;
                        } else {
                            if ($val instanceof Meta) {
                                $val = (string) $val->getValue();
                            } elseif (is_array($val)) {
                                $val = 'Array';
                            } elseif (is_object($val)) {
                                $val = 'Object';
                            } else {
                                throw new DeferralException("[{$name}] is an unknown structure.");
                            }
                        }

                    } catch(DeferralException $e) {
                        if (!$isFinalPass)
                            throw $e;

                        $name = substr($name, strpos($name, ':')+1);
//                        $this->Logger->error('testing: '.$name);
                        if(array_key_exists($name, $locals))
                           $val = $locals[$name];
                        elseif(array_key_exists($name, $this->getConstants()))
                            $val = $this->getConstant($name);
                        else
                            return true;
                    }
                    return empty($val) || $val == "0";
                } else {

                    $condition = substr($condition, 1);

                    if(count($split = explode('?', $condition, 2)) == 1)
                    {
                        $method = $condition;
                        $params = array();
                    } else {

                        list($method, $args) = $split;

                        $nvps = explode('&', html_entity_decode($args, ENT_QUOTES));

                        foreach ($nvps as $param) {
                            $split = explode('=',$param, 2);
                            if ($split === FALSE || count($split) == 1)
                                throw new Exception('Bad NVP in condition: '.$condition);

                            list($name, $value) = $split;
                            $params[$name]      = $this->parseParameterValue($value, $locals, $isFinalPass);
                        }
                    }

                    $filterRes = $this->handleFilter($method, $params, $locals);

                    return empty($filterRes) || $filterRes == "0";
                }
            } else {
                if (substr($condition, 0, 5) == 'Data:'
                 || substr($condition, 0, 7) == 'Global:')
                {

//                    $this->Logger->error($condition);
                    try {
                        $val = $this->parseParameterValue($condition, $locals, false);

                        if (is_scalar($val)) {
                            $val = (string) $val;
                        } else {
                            if ($val instanceof Meta) {
                                $val = (string) $val->getValue();
                            } elseif (is_array($val)) {
                                $val = 'Array';
                            } elseif (is_object($val)) {
                                $val = 'Object';
                            } else {
                                throw new DeferralException("[{$condition}] is an unknown structure.");
                            }
                        }

                    } catch(DeferralException $e) {
                        if (!$isFinalPass)
                            throw $e;
                        $name = substr($condition, strpos($condition, ':')+1);
//                        $this->Logger->error('testing: '.$name);
                        if(array_key_exists($name, $locals))
                           $val = $locals[$name];
                        elseif(array_key_exists($name, $this->getConstants()))
                            $val = $this->getConstant($name);
                        else
                            return false;
                    }
                    return !empty($val) && $val != "0";
                } else {

                    if(count($split = explode('?', $condition, 2)) == 1)
                    {
                        $method = $condition;
                        $params = array();
                    } else {
                        list($method, $args) = $split;

                        $nvps = explode('&', html_entity_decode($args, ENT_QUOTES));

                        foreach ($nvps as $param) {
                            $split = explode('=',$param, 2);
                            if ($split === FALSE || count($split) == 1)
                                throw new Exception('Bad NVP in condition: '.$condition);

                            list($name, $value) = $split;
                            $params[$name]      = $this->parseParameterValue($value, $locals, $isFinalPass);
                        }
                    }

                    $filterRes = $this->handleFilter($method, $params, $locals);

                    return !empty($filterRes) && $filterRes != "0";

                }
            }
        }

    }

    protected function callbackFinalAssets($match)
    {

        $assetBlock  = $match[1];
        $assetType   = $match[2];
        $assetParams = $match[4];

        $after = !empty($match[5])?$match[5]:'';

        try{

            if ($assetType == "css" || $assetType == "js") {

                $paramArray = array();
                $path       = '';

               // System::log(self::$logType, 'Parsing '.$assetBlock);

                $nvps = explode('&', html_entity_decode($assetParams, ENT_QUOTES));
                foreach ((array)$nvps as $param) {
                    if (count($split = explode('=',$param, 2)) == 1)
                        throw new Exception('Bad NVP in asset call: '.$match[0]);

                    list($name, $value) = $split;

                    if ($name == 'src')
                        $path = $value;
                    else
                        $paramArray[$name] = $value;
                }

                ksort($paramArray);

                $key = $assetType . http_build_query($paramArray);

                //make assets having the exclude option use a universally unique key
                if (strpos($key, "exclude") !== false)
                    $key .= $this->excludeCounter++;

                if ($this->lastKey == false) {
                    $this->lastKey  = $key;
                    $this->lastType = $assetType;
                }


                //$return = $assetBlock;
                $return = $assetBlock;
                if ($this->lastKey != $key) {
                    $return = $this->renderAssets($this->lastType, $this->assetPaths, $this->previousParamArray) .
                                $assetBlock;

                    $this->assetPaths = array();
                }

                $this->assetBlocksToReplace[$assetBlock] = '';
                $this->lastAssetBlock                    = $assetBlock;
                $this->lastKey                           = $key;
                $this->lastType                          = $assetType;

                //add to list
                $this->assetPaths[]                      = $path;
                $this->previousParamArray                = $paramArray;

                // if there is non-whitespace after this asset block, render now
                if(trim($after) != "")
                {
                    $return = $return.$this->renderAssets($this->lastType, $this->assetPaths, $this->previousParamArray);

                    $this->lastKey = false;
                    $this->lastAssetBlock = '';
                    $this->lastType = '';
                    $this->assetPaths = array();
                    $this->previousParamArray = array();
                }

                return $return.$after;
            }

            throw new Exception("ERROR PARSING ASSETS: unrecognized type, allowed types = [css,js].  Asset Block: " . $assetBlock);


        }catch(Exception $e)
        {
            if($this->throwRenderExceptions)
                throw $e;
            else {
                $this->Logger->error($e->getMessage() . "\nURL: " . URLUtils::fullUrl());
            }
        }

        return $assetBlock.$after;
    }

    protected function callbackAssets($match)
    {

        $assetBlock  = $match[0];
        $assetType   = $match[1];
        $assetParams = $match[3];

        try{

            if ($assetType == "version") {

                $fname = $this->parseParameterValue( substr($assetParams, 4), $this->tempAssetsFV, $this->tempAssetsFinalParse ); //removes 'src=/'

                return $this->AssetAggregator->renderVersionedFile($fname);
            } elseif ($assetType == "resolve") {

                $fname = $this->parseParameterValue( substr($assetParams, 4), $this->tempAssetsFV, $this->tempAssetsFinalParse ); //removes 'src=/'

                return $this->AssetAggregator->renderResolvedFile($fname);
            } elseif ($assetType == "relative") {

                $fname = $this->parseParameterValue( substr($assetParams, 4), $this->tempAssetsFV, $this->tempAssetsFinalParse ); //removes 'src=/'

                $url = $this->AssetAggregator->renderResolvedFile($fname);

                $pos = strpos($url,'/',8);

                return substr($url,$pos);
            }


        }catch(Exception $e)
        {
            if($this->throwRenderExceptions)
                throw $e;
            else {
                $this->Logger->error($e->getMessage() . "\nURL: " . URLUtils::fullUrl());
            }
        }

        return $assetBlock;

        //throw new Exception("ERROR PARSING ASSETS: unrecognized type, allowed types = [css,js,version]");
    }

    protected function parseAssets($unparsedContent, $fv, $isFinalPass = false)
    {

        $this->tempAssetsFV         = $fv;
        $this->tempAssetsFinalParse = $isFinalPass;

        return preg_replace_callback("/\{\% asset (.+?)(\?(.+?))? \%\}/", array($this, 'callbackAssets'), $unparsedContent);
    }


    protected function parseFinalAssets($unparsedContent)
    {
        $this->excludeCounter       = 1;
        $this->lastType             = false;
        $this->lastKey              = '';
        $this->lastAssetBlock       = '';
        $this->assetPaths           = array();
        $this->previousParamArray   = array();
        $this->assetBlocksToReplace = array();

        if($this->benchmarkRendering) $this->Benchmark->start('parse-assets');

        $parsedContent = preg_replace_callback("/(\{\% asset (.+?)(\?(.+?))? \%\})([^{]+)?/", array($this, 'callbackFinalAssets'), $unparsedContent);

        if (!empty($this->assetPaths)) {
            $parsedContent = str_replace($this->lastAssetBlock,
                                        $this->renderAssets($this->lastType, $this->assetPaths, $this->previousParamArray),
                                        $parsedContent);

            unset($this->assetBlocksToReplace[$this->lastAssetBlock]);
        }

        if (!empty($this->assetBlocksToReplace))
            $parsedContent = str_replace(array_keys($this->assetBlocksToReplace), '', $parsedContent);

        if($this->benchmarkRendering) $this->Benchmark->end('parse-assets');

        return $parsedContent;
    }

    protected function renderAssets($assetType, $assetPaths, $paramArray)
    {
        if($this->benchmarkRendering) $this->Benchmark->start('render-assets');

        $minify = (array_key_exists('min', $paramArray) ? json_decode($paramArray['min'])  : false);
        $pack   = (array_key_exists('pack', $paramArray)? json_decode($paramArray['pack']) : false);

        if ($minify)
            $mode = 'min';
        elseif ($pack)
            $mode = 'pack';
        else
            $mode = 'none';

        $iecond = (array_key_exists('iecond', $paramArray) ? $paramArray['iecond'] : '');
        $media  = (array_key_exists('media', $paramArray)  ? $paramArray['media']  : 'screen');

        // $this->Logger->debug('Rendering');
        // $this->Logger->debug($assetPaths);

        //render then clear the list
        if ($assetType == 'css')
            $c = $this->AssetAggregator->renderCSS($assetPaths, true, $minify, $media, $iecond);
        else
            $c = $this->AssetAggregator->renderJS($assetPaths, true, $mode, $iecond);

        if($this->benchmarkRendering) $this->Benchmark->end('render-assets');
        return $c;
    }

}

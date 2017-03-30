<?php
/**
 * Router
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
 * @version     $Id: Router.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * The Router is used to find the appropriate action and view files for an incoming Request
 *
 * @package     CrowdFusion
 */
class Router implements RouterInterface
{
    protected $redirectService   = null;
    protected $routeService      = null;

    protected $Logger;
    protected $TemplateCache;
    protected $cachedRedirectTTL = 0;
    protected $DateFactory;

    /**
     * Constructs the Router. Always created by the IoC controller
     *
     * @param Request  $Request    autowired
     * @param Response $Response   Autowired
     */
    public function __construct(Request $Request, Response $Response)
    {
        $this->Request  = $Request;
        $this->Response = $Response;
    }

    /**
     * Autowire to setup RouteService
     *
     * @param RouteService $RouteService autowired
     *
     * @return void
     */
    public function setRouteService(RouteService $RouteService)
    {
        $this->routeService = $RouteService;
    }

    /**
     * Autowire to setup RedirectService
     *
     * @param RedirectService $RedirectService autowired
     *
     * @return void
     */
    public function setRedirectService(RedirectService $RedirectService)
    {
        $this->redirectService = $RedirectService;
    }

    /**
     * Autowire to setup Logger
     *
     * @param Logger $Logger autowired
     *
     * @return void
     */
    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

    public function setTemplateCache(TemplateCacheInterface $TemplateCache)
    {
        $this->TemplateCache = $TemplateCache;
    }

    public function setCachedRedirectTtl($cachedRedirectTTL)
    {
        $this->cachedRedirectTTL = $cachedRedirectTTL;
    }

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    protected function sanitizeURI($uri)
    {

        $sluggedUri = SlugUtils::createSlug($uri, true);
        if(strcmp($sluggedUri, $uri) !== 0) {
            $this->Response->sendStatus(Response::SC_MOVED_PERMANENTLY);
            $this->Response->sendRedirect($sluggedUri);
        }

        return $sluggedUri;
    }

    /**
     * Returns an array that describes the result of the current route.
     *
     * The resultant array will contain keys like 'view', and 'paging', etc.
     *
     * For full details, see the routes file as the array is built from that.
     *
     * @return array
     */
    public function route()
    {
        $this->Logger->debug("Request: {$this->Request->getFullURL()}");

        $uri = $this->Request->getAdjustedRequestURI();

//        $uri = $this->sanitizeURI($uri);

        $this->Logger->debug("Routing URI: {$uri}");



        $result = array();
        $qs = array_merge($_POST, $_GET);

        $routes = $this->routeService->findAll();

//        $regex = $this->compoundRegex(array_keys($routes));
//        error_log($regex);
//
//        if (! preg_match($regex, $uri, $matches)) {
//            $match = '';
//            error_log('no match');
//            exit;
//            return false;
//        }
//        $match = $matches[0];
//        error_log('match = '.$match);
//        exit;

        $this->Logger->debug("Testing Routes\n");

        foreach ((array)$routes as $test_uri => $nvps) {

            $this->Logger->debug("{$test_uri}");


            $m = null;
            $re = str_replace('/', '\/', $test_uri);
            $match = preg_match('/^' . $re . '$/i', $uri, $m);
            if ($match) {
                if (!empty($nvps) && is_array($nvps)) {
                    foreach ($nvps as $name => $value) {
                        foreach ($m as $n => $v) {
                            $value = str_replace('$'.$n, $v, $value);
                        }

                        foreach ($qs as $n => $v) {
                            if (is_scalar($v))
                                $value = str_replace('$'.$n, $v, $value);
                        }

                        $varparts = null;
                        if (preg_match("/(.+)\[(.+)\]/", $name, $varparts)) {
                            $result[$varparts[1]][$varparts[2]] = $value;
                        } else {
                            $result[$name] = $value;
                        }
                    }
                }

                foreach ($m as $n => $v)
                    if (is_int($n))
                        unset($m[$n]);

                $result = array_merge($m, $result);

                $this->Logger->debug('Routing result:');
                $this->Logger->debug($result);

                return $result;
            }
        }

        $this->checkRedirects();

        $this->Logger->debug("No routes matched.");

        return null;
    }


//    protected function compoundRegex($routes)
//    {
//        for ($i = 0, $count = count($routes); $i < $count; $i++) {
//            $routes[$i] = '(' . str_replace(
//                    array('/'),
//                    array('\/'),
//                    $routes[$i]) . ')';
//        }
//        return "/^" . implode("|", $routes) . "$/i";
//    }



    /**
     * Checks the redirects against the current URI and redirects the user's browser
     * if they've hit a redirect URL
     *
     * @return void
     */
    public function checkRedirects()
    {
        // remove trailing slash and lowercase so there aren't multiple copies to cache
        $uri = strtolower(rtrim($this->Request->getAdjustedRequestURI(), '/'));
        $qs = $this->Request->getQueryString();

        if (($previousRedirect = $this->TemplateCache->get('redirect:' . $uri)) !== false) {
            $this->Logger->debug('Using cached redirect: ' . $uri . ' -> ' . $previousRedirect);

            if ($previousRedirect == 'null')
                return;

            // let's not let the browser cache these permanently
            $this->setCachedRedirectTtl(120);
            $this->Response->addHeader('Cache-Control', 'max-age=' . $this->cachedRedirectTTL);
            $this->Response->addHeader('Expires', $this->DateFactory->newLocalDate('+' . $this->cachedRedirectTTL . ' seconds')->toRFCDate());

            $this->Response->sendStatus(Response::SC_MOVED_PERMANENTLY);
            $this->Response->sendRedirect($previousRedirect . (!empty($qs) ? '?' . $qs : ''));
            return;
        }

        $redirects = $this->redirectService->findAll();

        foreach ((array)$redirects as $test_uri => $new_url ) {
            $this->Logger->debug($test_uri);

            $m     = null;
            $re    = str_replace('/', '\/', $test_uri);
            $match = preg_match("/^". $re . "$/i", $uri, $m);

            if ($match) {
                if (!empty($new_url)) {
                    foreach ($m as $n => $v) {
                        $new_url = str_replace('$' . $n, $v, $new_url);
                    }
                }

                $this->TemplateCache->put('redirect:' . $uri, $new_url, $this->cachedRedirectTTL);

                // let's not let the browser cache these permanently
                $this->Response->addHeader('Cache-Control', 'max-age=' . $this->cachedRedirectTTL);
                $this->Response->addHeader('Expires', $this->DateFactory->newLocalDate('+' . $this->cachedRedirectTTL . ' seconds')->toRFCDate());

                $this->Response->sendStatus(Response::SC_MOVED_PERMANENTLY);
                $this->Response->sendRedirect($new_url . (!empty($qs) ? '?' . $qs : ''));
                return;
            }
        }

        $this->TemplateCache->put('redirect:' . $uri, 'null', $this->cachedRedirectTTL);
    }
}

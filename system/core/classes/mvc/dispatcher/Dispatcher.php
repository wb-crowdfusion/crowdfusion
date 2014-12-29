<?php

/**
 * The Dispatcher executes the main flow of the web request.  This involves: managing a model and view, invoking
 * interceptors (pre and post), executing the controller action, handling redirects, and rendering the view.
 * Finally, the Dispatcher will output the rendered content and flush the response to the browser.
 */
class Dispatcher
{
    protected $ApplicationContext;
    protected $Request;
    protected $Response;
    protected $Session;
    protected $RequestContext;

    protected $Router;
    protected $Renderer;
    protected $ControllerManager;

    protected $DateFactory;
    protected $Events;

    protected $view404;

    /**
     * Creates the Dispatcher. This is autowired and should not be instantiated anywhere by itself.
     *
     * @param ApplicationContext $ApplicationContext
     * @param LoggerInterface $Logger
     * @param BenchmarkInterface $Benchmark
     * @param SecurityInterface $Security
     * @param Request $Request
     * @param Response $Response
     * @param Session $Session
     * @param RequestContext $RequestContext
     * @param RouterInterface $Router
     * @param ControllerManagerInterface $ControllerManager
     * @param RendererInterface $Renderer
     * @param Events $Events
     * @param AbstractDeploymentService $AssetService
     * @param AbstractDeploymentService $TemplateService
     * @param AbstractDeploymentService $MessageService
     * @param DateFactory $DateFactory
     * @param string $context
     * @param string $siteDomain
     * @param string $deviceView
     * @param string $design
     * @param bool $isSiteDeployment
     * @param string $charset
     * @param string $view404
     *
     * @throws \Exception
     */
    public function __construct(
            ApplicationContext $ApplicationContext,
            LoggerInterface $Logger,
            BenchmarkInterface $Benchmark,
            SecurityInterface $Security,
            Request $Request,
            Response $Response,
            Session $Session,
            RequestContext $RequestContext,
            RouterInterface $Router,
            ControllerManagerInterface $ControllerManager,
            RendererInterface $Renderer,
            Events $Events,
            AbstractDeploymentService $AssetService,
            AbstractDeploymentService $TemplateService,
            AbstractDeploymentService $MessageService,
            DateFactory $DateFactory,
            $context,
            $siteDomain,
            $deviceView,
            $design,
            $isSiteDeployment,
            $charset,
            $view404 = '404.cft'
    ) {
        $this->ApplicationContext  = $ApplicationContext;
        $this->Request             = $Request;
        $this->Response            = $Response;
        $this->Session             = $Session;
        $this->RequestContext      = $RequestContext;

        $this->Events = $Events;

        $this->Router            = $Router;
        $this->ControllerManager = $ControllerManager;
        $this->Renderer          = $Renderer;

        $this->Logger = $Logger;
        $this->DateFactory = $DateFactory;
        $this->Benchmark = $Benchmark;
        $this->Security = $Security;

        $this->AssetService = $AssetService;
        $this->TemplateService = $TemplateService;
        $this->MessageService = $MessageService;

        $this->context = $context;
        $this->siteDomain = $siteDomain;
        $this->deviceView = $deviceView;
        $this->design = $design;
        $this->isSiteDeployment = $isSiteDeployment;

        $this->charset = $charset;

        $this->view404 = $view404;

        $this->Logger->debug("Context : {$this->context}");
        $this->Logger->debug("Sitename: {$this->siteDomain}");
        $this->Logger->debug("Device View  : {$this->deviceView}");
        $this->Logger->debug("Design  : {$this->design}");
        $this->Logger->debug("Sites?  : {$this->isSiteDeployment}");

        if (empty($this->context)) {
            throw new Exception('Invalid request: missing context');
        }

        if (empty($this->siteDomain)) {
            throw new Exception('Invalid request: missing siteDomain');
        }

        if (empty($this->deviceView)) {
            throw new Exception('Invalid request: missing deviceView');
        }

        if (empty($this->design)) {
            throw new Exception('Invalid request: missing design');
        }

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Triggers the shutdown event
     *
     * @return void
     */
    public function shutdown()
    {
        $this->Events->trigger('shutdown');
    }

    /**
     * Arguably the most important function in all of CrowdFusion, this function kickstarts our
     * response to the incoming request.
     *
     * It first locates the proper route and initializes the request parameters from the route.
     * Next, all the interceptors are given a chance to run.
     *
     * Then the appropriate controller is called and allowed to run
     *
     * The output is constructed via redirect or renderer and then
     * the function ends by allowing all interceptors to run their postHandle method
     *
     * @return void
     */
    public function processRequest()
    {
        $returnContent = true;

        $this->Benchmark->end('boot');

        /*** START OUTPUT BUFFERING FOR RESPONSE ***/
        $this->Response->start();


//        $this->Benchmark->start('route');

        /*** ROUTE THE REQUEST ***/
        $routeVariables = $this->Router->route();

        /*** ROUTE TO THE 404 ***/
        if(is_null($routeVariables))
            $routeVariables = array('view'=> $this->view404);

//        $this->Benchmark->end('route');

//        $this->Benchmark->start('input-clean');

        /*** INITIALIZE REQUEST PARAMETERS ***/
        $this->Request->addRouteParameters($routeVariables);
        $this->Request->addInputParameters(array_merge($_POST, $_GET));

        $this->Logger->debug($this->Security->filterLoggedParameters($this->Request->getParameters()));
//        $this->Benchmark->end('input-clean');

//        $this->Benchmark->start('controls');

        /*** INIT WEB CONTEXT & CONTROLS ***/
        $this->RequestContext->setControls(new Controls($this->Request->getRouteParameters(), $this->Security->filterInputControlParameters($this->Request->getInputParameters())));

        $this->Events->trigger('Dispatcher.preDeploy');

//        $this->Benchmark->end('controls');
        $this->Benchmark->start('deployment');

        // DEPLOY ASSETS
        $this->AssetService->deploy();

        //DEPLOY TEMPLATES
        $this->TemplateService->deploy();

        //DEPLOY MESSAGES
        $this->MessageService->deploy();

        $this->Benchmark->end('deployment');

        /*** PRE-INTERCEPTORS ***/
        $transport = new Transport();
        $transport->RouteVariables = $routeVariables;
        $this->Events->trigger('Dispatcher.preHandle', $transport);
        $routeVariables = $transport->RouteVariables;


        /*** INIT VIEW ***/
        $view = new View($this->RequestContext->getControls()->getControl('view'));

        $this->Logger->debug('---');

        $handler = $this->RequestContext->getControls()->getControl('view_handler');
        try {
            /*** CONTROLLER ACTION ***/
            if (($action = $this->RequestContext->getControls()->getControl('action')) != null) {

                $actionBuffer = $this->RequestContext->getControls()->getControl('action_buffer');
                if ($actionBuffer == true)
                    $this->Response->prepare();

                $this->Events->trigger('Dispatcher.preAction');

                $this->Logger->debug('Executing action ['.$action.']');
                $view = $this->ControllerManager->invokeAction($action);

                $this->Events->trigger('Dispatcher.postAction', $view);

                if ($actionBuffer == true)
                    $this->Response->flush();
            }

            /*** NO VIEW? BLANK PAGE ***/
            if ($view == null || $view->getName() == null || $view->getName() == 'null') {
                $this->Logger->debug('No view.');
                $this->Response->prepare()->flush();
                $this->Events->trigger('Dispatcher.terminate', new Transport());
                return;
            }

            /*** PROCESS REDIRECTS ***/
            if ($view->isRedirect()) {
                $this->Session->setFlashAttribute('original_referer', $this->Request->getFullURL());

                if ($view->isPermanentRedirect()) {
                    $this->Response->sendStatus(Response::SC_MOVED_PERMANENTLY);
                }
                $this->Response->sendRedirect(URLUtils::resolveUrl($view->getRedirect(), $view->getData()));  //END OF FLOW

            } else if ($view->getName() == 'original_referer') {

                $url = $this->Session->getFlashAttribute('original_referer');

                if (empty($url))
                    $url = $this->Request->getReferrer();

                if($url == $this->Request->getFullURL())
                    $this->Response->sendRedirect('/');

                $this->Response->sendRedirect($url); //END OF FLOW
            }

            $this->Session->keepFlashAttribute('original_referer');

            $this->Logger->debug('---');
            $this->Benchmark->start('render');

            /*** RENDER VIEW ***/
            $globals = array();

            list($content, $globals) = $this->Renderer->renderView($view, $handler,
                                            StringUtils::strToBool($this->RequestContext->getControls()->getControl('view_nocache')) == false &&
                                            $this->RequestContext->getControls()->getControl('action_executing') == null,
                                            $this->Request->getUserAgent() == 'crowdfusion-cli'?$this->RequestContext->getControls()->getControl('view_pushcache'):false);

            if($view->getName() === $this->view404)
                $this->Response->sendStatus(Response::SC_NOT_FOUND);

        } catch(NotFoundException $nfe) {
            $this->Router->checkRedirects();

            $this->Response->sendStatus(Response::SC_NOT_FOUND);

            if (!empty($this->view404)) {
                try {
                    // 404 view is responsible for loading 404 page template, if desired
                    list($content, $globals) = $this->Renderer->renderView(new View($this->view404, array_merge($view->getData(), array('NotFoundException'=>$nfe->getMessage()))), $handler);

                } catch(NotFoundException $nfe) {}
            }

            if(empty($content))
                $content = "404 Not Found";
        }

        if (empty($globals['Content-Type'])) {
            switch($handler) {
                case 'html':
                default:
                    $globals['Content-Type'] = 'text/html; charset="'.$this->charset.'"';
                    break;

                case 'txt':
                    $globals['Content-Type'] = 'text/plain; charset="'.$this->charset.'"';
                    break;

                case 'rss':
                case 'xml':
                    $globals['Content-Type'] = 'application/xml; charset="'.$this->charset.'"';
                    break;

                case 'json':
                    $globals['Content-Type'] = 'application/json; charset="'.$this->charset.'"';
                    break;

            }
        }

        $this->Response->addHeader('Content-Type', $globals['Content-Type']);

        if (empty($globals['BrowserCacheTime'])) {
            $this->Response->addHeader('Cache-Control', 'no-cache, must-revalidate, post-check=0, pre-check=0');
            $this->Response->addHeader('Expires', $this->DateFactory->newLocalDate()->toRFCDate());
        } else {
            $this->Response->addHeader('Cache-Control', 'max-age='.$globals['BrowserCacheTime']);
            $this->Response->addHeader('Expires', $this->DateFactory->newLocalDate('+'.$globals['BrowserCacheTime'].' seconds')->toRFCDate());
        }

        $this->Logger->debug('---');

        /* Process ETags */
        if (isset($globals['ETag'])
                && $this->Response->getStatus() === Response::SC_OK
                && !$this->Response->containsHeader('ETag')
        ) {
            $etag = $this->ApplicationContext->getSystemVersionTimestamp() . "-{$this->deviceView}-{$this->design}-" . $globals['ETag'] . ($this->Request->acceptsGzip() ? '-gzip' : '');
            $globals['ETag'] = md5($etag);
            $this->Logger->info('Raw ETag=' . $etag. ', md5=' . $globals['ETag']);

            $this->Response->addHeader('ETag', '"' . $globals['ETag'] . '"');
            $ifNoneMatch = trim($this->Request->getHeader('If-None-Match'), '"');
            $this->Logger->info('If-None-Match request header: ' . $ifNoneMatch);

            if (strcmp($ifNoneMatch, $globals['ETag']) === 0) {
                $this->Logger->info('ETag match: ' . $ifNoneMatch);

                // remove headers that MUST NOT be included with 304 Not Modified responses
                foreach (array('Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified') as $header) {
                    $this->Response->removeHeader($header);
                }
                $this->Response->sendStatus(Response::SC_NOT_MODIFIED);
                $returnContent = false;
            }
        }

        /*** POST-INTERCEPTORS ***/
        $transport = new Transport();
        $transport->Content = $content;
        $transport->Globals = $globals;
        $this->Events->trigger('Dispatcher.postHandle', $transport);

        $this->Benchmark->end('render');

        $this->Response->prepare();

        if ($returnContent) {
            echo $transport->Content;
        }

        $this->Response->flush();

        $this->Events->trigger('Dispatcher.terminate', $transport);

        $this->Benchmark->end('all');
    }

}
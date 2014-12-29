<?php

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Presenter Template Engine
 * WIP
 *
 */
class PresenterTemplateEngine implements TemplateEngineInterface
{
    /* @var \ApplicationContext */
    protected $applicationContext;

    /* @var string */
    protected $design = 'default';

    /* @var string */
    protected $deviceView = 'main';

    /* @var FileCache */
    protected $fileCache;

    /* @var LoggerInterface */
    protected $logger;

    /* @var TemplateCache */
    protected $templateCache;

    /* @var TemplateService */
    protected $templateService;

    /* @var boolean */
    protected $throwRenderExceptions = true;

    /**
     * @param ApplicationContext $ApplicationContext
     * @param string $design
     * @param string $deviceView
     * @param FileCache $FileCache
     * @param LoggerInterface $Logger
     * @param TemplateCache $TemplateCache
     * @param TemplateService $TemplateService
     * @param bool $throwRenderExceptions
     */
    public function __construct(
            ApplicationContext $ApplicationContext,
            $design = 'default',
            $deviceView = 'main',
            FileCache $FileCache,
            LoggerInterface $Logger,
            TemplateCache $TemplateCache,
            TemplateService $TemplateService,
            $throwRenderExceptions = true
    ) {
        $this->applicationContext = $ApplicationContext;
        $this->design = $design;
        $this->deviceView = $deviceView;
        $this->fileCache = $FileCache;
        $this->logger = $Logger;
        $this->templateCache = $TemplateCache;
        $this->templateService = $TemplateService;
    }

    /**
     * @see TemplateEngineInterface::parseTemplateIncludes
     */
    public function parseTemplateIncludes($unparsedContent, $contentType, $parentTemplate, &$globals, RendererInterface $renderer, $isFinalPass = false, $isDependentPass = false)
    {
        // does nothing as the presenter concept doesn't support nested template calls
    }

    /**
     * @see TemplateEngineInterface::firstPass
     */
    public function firstPass($viewName, $contentType, &$globals, RendererInterface $renderer)
    {
        return '';
    }

    /**
     * @see TemplateEngineInterface::finalPass
     */
    public function finalPass($unparsedContent, $contentType, &$globals, RendererInterface $renderer)
    {
        return $unparsedContent;
    }

    /**
     * @see TemplateEngineInterface::loadFromCache
     */
    public function loadFromCache(Template $template)
    {
        $template->setProcessedContent($template->getProcessedContent());
        return $template;
    }

    /**
     * @see TemplateEngineInterface::prepareForCache
     */
    public function prepareForCache(Template $template)
    {
        $template->setDeferredContents(array());
        return $template;
    }

    /**
     * @see TemplateEngineInterface::loadTemplateExtended
     */
    public function loadTemplateExtended(Template $template, &$globals)
    {
        $params = $template->getLocals();

        if (!empty($params['Presenter'])) {
            $template->setPresenter($params['Presenter']);
        }

        if (!empty($params['CacheTime'])) {
            $template->setCacheTime((int) $params['CacheTime']);
        } else {
            $template->setCacheTime(300);
        }

        if ($template->getCacheTime() > 0 && (empty($params['NoCache']) || StringUtils::strToBool($params['NoCache']) == false)) {
            $template->setCacheable(true);
        }

        return $template;
    }

    /**
     * @see TemplateEngineInterface::processTemplateContents
     */
    public function processTemplateContents(
            Template $template,
            &$globals,
            RendererInterface $renderer,
            $isFinalPass = false,
            $isDependentPass = false
    ) {
        return $this->render($template);
    }

    /**
     * @see TemplateEngineInterface::processTemplateSetGlobals
     */
    public function processTemplateSetGlobals(Template $template, &$globals, $isFinalPass = false)
    {
        return $globals;
    }

    /**
     * Loads a presenter, calls its method and returns the result.
     *
     * @param Template $template
     * @return string
     * @throws Exception
     */
    protected function render(Template $template)
    {
        $presenter = $this->createPresenter($template->getPresenter());

        try {
            $arguments = $this->getArguments($presenter, $template);
            $this->logger->debug($arguments);
            $result = call_user_func_array($presenter, $arguments);
        } catch(Exception $e) {
            if ($this->throwRenderExceptions) {
                throw $e;
            }

            $this->logger->error($e->getMessage() .
                    "\n\nURL: " . URLUtils::fullUrl() .
                    "\n\nTemplate:\n" . print_r($template, true) .
                    (isset($arguments) ? "\n\n$presenter args:\n" . print_r($arguments, true) : '')
                );

            $result = '';
        }

        return $result;
    }

    /**
     * Returns a callable for the given presenter.
     *
     * @param string $presenter A Presenter string
     *
     * @return mixed A PHP callable
     *
     * @throws TemplateEngineException
     */
    protected function createPresenter($presenter)
    {
        if (false === strpos($presenter, '::')) {
            throw new TemplateEngineException(sprintf('Presenter must be in the format class|service::method', $presenter));
        }

        list($class, $method) = explode('::', $presenter, 2);
        $presenter = $this->applicationContext->object($class);

        if (!method_exists($presenter, $method)) {
            throw new TemplateEngineException(sprintf('Presenter "%s" does not have a "%s" method.', $class, $method));
        }

        return array($presenter, $method);
    }

    /**
     * Returns the arguments to pass to the presenter.
     *
     * @param mixed $presenter A PHP callable
     * @param Template $template
     *
     * @return array
     *
     * @throws RuntimeException When value for argument given is not provided
     *
     * @api
     */
    protected function getArguments($presenter, Template $template)
    {
        if (is_array($presenter)) {
            $r = new ReflectionMethod($presenter[0], $presenter[1]);
        } elseif (is_object($presenter) && !$presenter instanceof Closure) {
            $r = new ReflectionObject($presenter);
            $r = $r->getMethod('__invoke');
        } else {
            $r = new ReflectionFunction($presenter);
        }

        return $this->doGetArguments($presenter, $r->getParameters(), $template);
    }

    /**
     * @param $presenter
     * @param array $parameters
     * @param Template $template
     * @return array
     * @throws RuntimeException
     */
    protected function doGetArguments($presenter, array $parameters, Template $template)
    {
        $attributes = $template->getLocals();

        if (!isset($attributes['deviceView'])) {
            $attributes['deviceView'] = $this->deviceView;
        }

        if (!isset($attributes['design'])) {
            $attributes['design'] = $this->design;
        }

        $arguments = array();

        /*
         * do not pass through the core cft values
         */
        unset($attributes['Presenter']);
        unset($attributes['CacheTime']);

        /* @var \ReflectionParameter $param */
        foreach ($parameters as $param) {
            if (array_key_exists($param->name, $attributes) && !empty($attributes[$param->name])) {
                $paramValue = $attributes[$param->name];
                // convert bool strings to real bools
                if (is_scalar($paramValue)) {
                    if ('true' === $paramValue) {
                        $paramValue = true;
                    } elseif ('false' === $paramValue) {
                        $paramValue = false;
                    }
                }
                $arguments[] = $paramValue;
            } elseif ($param->getClass() && $param->getClass()->getName() === 'Symfony\Component\HttpFoundation\ParameterBag') {
                $arguments[] = new ParameterBag($attributes);
            } elseif ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            } else {
                if (is_array($presenter)) {
                    $repr = sprintf('%s::%s()', get_class($presenter[0]), $presenter[1]);
                } elseif (is_object($presenter)) {
                    $repr = get_class($presenter);
                } else {
                    $repr = $presenter;
                }

                throw new RuntimeException(sprintf('Presenter "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->name));
            }
        }

        return $arguments;
    }
}
<?php

class Response
{
    const SC_CONTINUE = '100 Continue';
    const SC_SWITCHING_PROTOCOLS = '101 Switching Protocols';
    const SC_OK = '200 OK';
    const SC_CREATED = '201 Created';
    const SC_ACCEPTED = '202 Accepted';
    const SC_NON_AUTHORITATIVE_INFORMATION = '203 Non Authoritative Information';
    const SC_NO_CONTENT = '204 No Content';
    const SC_RESET_CONTENT = '205 Reset Content';
    const SC_PARTIAL_CONTENT = '206 Partial Content';
    const SC_MULTIPLE_CHOICES = '300 Multiple Choices';
    const SC_MOVED_PERMANENTLY = '301 Moved Permanently';
    const SC_MOVED_TEMPORARILY = '302 Found';
    const SC_SEE_OTHER = '303 See Other';
    const SC_NOT_MODIFIED = '304 Not Modified';
    const SC_USE_PROXY = '305 Use Proxy';
    const SC_TEMPORARY_REDIRECT = '307 Temporary Redirect';
    const SC_BAD_REQUEST = '400 Bad Request';
    const SC_UNAUTHORIZED = '401 Unauthorized';
    const SC_PAYMENT_REQUIRED = '402 Payment Required';
    const SC_FORBIDDEN = '403 Forbidden';
    const SC_NOT_FOUND = '404 Not Found';
    const SC_METHOD_NOT_ALLOWED = '405 Method Not Allowed';
    const SC_NOT_ACCEPTABLE = '406 Not Acceptable';
    const SC_PROXY_AUTHENTICATION_REQUIRED = '407 Proxy Authentication Required';
    const SC_REQUEST_TIMEOUT = '408 Request Timeout';
    const SC_CONFLICT = '409 Conflict';
    const SC_GONE = '410 Gone';
    const SC_LENGTH_REQUIRED = '411 Length Required';
    const SC_PRECONDITION_FAILED = '412 Precondition Failed';
    const SC_REQUEST_ENTITY_TOO_LARGE = '413 Request Entity Too Large';
    const SC_REQUEST_URI_TOO_LONG = '414 Request-URI Too Long';
    const SC_UNSUPPORTED_MEDIA_TYPE = '415 Unsupported Media Type';
    const SC_REQUESTED_RANGE_NOT_SATISFIABLE = '416 Requested Range Not Satisfiable';
    const SC_EXPECTATION_FAILED = '417 Expectation Failed';
    const SC_INTERNAL_SERVER_ERROR = '500 Internal Server Error';
    const SC_NOT_IMPLEMENTED = '501 Not Implemented';
    const SC_BAD_GATEWAY = '502 Bad Gateway';
    const SC_SERVICE_UNAVAILABLE = '503 Service Unavailable';
    const SC_GATEWAY_TIMEOUT = '504 Gateway Timeout';
    const SC_HTTP_VERSION_NOT_SUPPORTED = '505 HTTP Version Not Supported';

    const HEADER_PRAGMA = 'Pragma';
    const HEADER_EXPIRES = 'Expires';
    const HEADER_CACHE_CONTROL = 'Cache-Control';

    protected $sc = Response::SC_OK;
    protected $headers = array();
    protected $sentHeaders = array();
    protected $cookies = array();

    /** @var \LoggerInterface */
    protected $Logger;

    /** @var \RequestContext */
    protected $RequestContext;

    /** @var \Request */
    protected $Request;

    /** @var \Events */
    protected $Events;
    protected $routerBase;
    protected $outputBuffering;
    protected $vary;

    /**
     * @param LoggerInterface $Logger
     * @param RequestContext $RequestContext
     * @param Request $Request
     * @param Events $Events
     * @param string $routerBase
     * @param string $charset
     * @param bool $responseOutputBuffering
     * @param string $responseVary
     */
    public function __construct(
            LoggerInterface $Logger,
            RequestContext $RequestContext,
            Request $Request,
            Events $Events,
            $routerBase,
            $charset,
            $responseOutputBuffering = true,
            $responseVary = 'Accept-Encoding, User-Agent'
    ) {
        $this->Logger = $Logger;
        $this->RequestContext = $RequestContext;
        $this->Request = $Request;
        $this->Events = $Events;
        $this->routerBase = $routerBase;
        $this->outputBuffering = $responseOutputBuffering;
        $this->vary = $responseVary;

        if (!empty($_SERVER['HTTP_HOST'])) {
            $this->addHeader('X-Powered-By', 'Crowd Fusion');

            /*
             * CrowdFusion is designed to be able to deliver different content
             * by device class on the same url.  this ensures that external
             * caches are aware of this.
             *
             */
            if (!empty($this->vary)) {
                $this->addHeader('Vary', $this->vary);
            }
        }
    }

    protected function resolveURI($uri)
    {
        if(empty($uri) || StringUtils::startsWith($uri,'/'))
            return rtrim($this->routerBase, '/').'/'.ltrim($uri, '/');
            //return $this->RequestContext->getSite()->getBaseURL().'/'.ltrim($uri, '/');

        return $uri;
    }

    /**
     * setProtocol
     * all response protocol decisions made here
     * handle the exceptions to rules for protocol 1.1 vs 1.0
     * default to 1.0 when protocol not specified
     * RFC @ http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10
     * @param statuscode $statuscode
     */
    protected function setProtocol($statuscode)
    {
    	if ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' || $statuscode == Response::SC_CONTINUE || $statuscode == Response::SC_SWITCHING_PROTOCOLS || $statuscode == Response::SC_TEMPORARY_REDIRECT) {
        	header('HTTP/1.1 ' . $statuscode);
        } elseif (empty($_SERVER['SERVER_PROTOCOL'])) {
    		header('HTTP/1.0 ' . $statuscode);
    	} else {
    		header($_SERVER['SERVER_PROTOCOL'] . ' ' . $statuscode);
    	}
    }

    public function sendCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false) {
        $cookie = array($name, $value, ((strcmp(strtolower($expire), 'never') === 0) ? (time() + 60 * 60 * 24 * 6000) : $expire),
                        $path, $domain, $secure);

        $this->cookies[] = $cookie;
    }

    public function clearCookie($name, $path = '/', $domain = '', $secure = false)
    {
        $this->cookies[] = array($name, '', time() - 3600, $path, $domain, $secure);
    }

    public function sendHeader($name, $value) {
        $this->headers[$name] = array($value);
    }

    public function sendDateHeader($name, Date $value) {
        $this->headers[$name] = array($value->toRFCDate());
    }

    public function addHeader($name, $value) {
        $this->headers[$name][] = $value;
    }

    public function containsHeader($name) {
        return isset($this->headers[$name]);
    }

    public function removeHeader($name) {
        if($this->containsHeader($name))
            unset($this->headers[$name]);
    }

    public function sendError($sc) {
        ob_end_clean();
        $this->Logger->info("Sending error [STATUS: {$sc}]");
        $this->setProtocol($sc);
        $this->prepareResponse();
        exit;
    }


    public function sendRedirect($location) {
        if (headers_sent())
            throw new RequestException('Cannot send redirect when headers already sent');

        $location = $this->resolveURI($location);

        $this->Logger->info("Sent Redirect to {$location} [STATUS: {$this->sc}]");
        $this->addHeader('Cache-Control', 'max-age=120');
        $this->setProtocol($this->sc);
        $this->prepareResponse();

        /*
         * the response should not be ending the flow but it does, for now.
         * this is a hack to get around scattered use of the response singleton
         * through CF app and plugins.
         */
        $this->Events->trigger('Dispatcher.terminate', new Transport());

        header("Location: ".$location);
        header("Connection: close");
        exit;
    }

    public function sendStatus($status_code) {
        $this->sc = $status_code;

        return $this;
    }

    public function getStatus()
    {
        return $this->sc;
    }

    public function start() {
        if ($this->outputBuffering)
            ob_start();

        return $this;
    }

    public function clean() {
        if ($this->outputBuffering)
            ob_end_clean();

        return $this;
    }

    protected function prepareResponse()
    {
        // headers
        foreach ($this->headers as $name => $values) {

            // dont send any dup headers
            if (in_array($name, $this->sentHeaders))
                continue;

            foreach($values as $value)
            {
                header($name . ': ' . $value, false);
                $this->Logger->info("Sent header '{$name}': '{$value}'");
                // remember this one
                $this->sentHeaders[] = $name;
            }
        }

        foreach($this->cookies as $cookie) {
            $this->Logger->info("Set cookie: '{$cookie[0]}' : '{$cookie[1]}', expires {$cookie[2]}, path: {$cookie[3]}, domain: {$cookie[4]}");
            call_user_func_array('setcookie', $cookie);
        }
    }

    public function prepare() {
        if (headers_sent())
            return $this;

        $this->setProtocol($this->sc);

        $this->Logger->info("STATUS: {$this->sc}");

        $this->prepareResponse();
        return $this;
    }

    public function flush() {

        if($this->outputBuffering)
        {
            ob_end_flush();

//            if (extension_loaded('zlib')
//                && ini_get('zlib.output_compression') != true
//                && isset($_SERVER['HTTP_ACCEPT_ENCODING'])
//                && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE)
//                ob_start('ob_gzhandler');
        }

        return $this;

    }

}

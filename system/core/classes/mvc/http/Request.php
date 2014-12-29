<?php

class Request
{
    const fieldMarkerPrefix = '_';

    protected $parameters = array();
    protected $rawParameters = array();

    protected $routeParameters = array();
    protected $inputParameters = array();

    protected $ip_address = false;
    protected $languages = false;
    protected $charsets = false;
    protected $encodings = null;

    protected $session = null;

    protected $cleanedCookies = false;

    public function __construct(InputCleanInterface $InputClean, $routerBase) {

        $this->InputClean = $InputClean;
        $this->routerBase = $routerBase;

    }

    /* PARAMETERS & RAW */

    public function addRouteParameters(array $params)
    {
        $this->parameters = array_merge($this->parameters,$params);
        $this->rawParameters = array_merge($this->rawParameters, $params);

        $this->routeParameters = $params;
    }

    public function addInputParameters(array $params)
    {
        $rawParams = $params;

        // clean all the input once
        foreach((array)$params as $key => $val)
        {

            // fieldMarker is used to indicate checkboxes or tags were on the form
            //  but they were unchecked or empty
            // EXAMPLES: _ACheckboxField = 0 puts ACheckboxField = 0 into the request
            if(strpos($key, self::fieldMarkerPrefix) === 0) {
                $fieldName = substr($key, 1);
                if(empty($fieldName)) {
                    $params[$key] = $this->InputClean->clean($val, '');
                    continue;
                }

                if(!array_key_exists($fieldName, $params)) {
                    $params[$fieldName] = $this->InputClean->clean($val, '');
                    $rawParams[$fieldName] = $val;
                    unset($params[$key]);
                    unset($rawParams[$key]);
                }
            } else {
                $params[$key] = $this->InputClean->clean($val, '');
            }

        }

        $params = (( get_magic_quotes_gpc() )?StringUtils::stripslashesDeep($params):$params );
        $rawParams = (( get_magic_quotes_gpc() )?StringUtils::stripslashesDeep($rawParams):$rawParams );

        $this->parameters = array_merge($this->parameters, $params) ;
        $this->rawParameters = array_merge($this->rawParameters, $rawParams);

        $this->inputParameters = $params;
    }

    public function getInputParameters() {
        return $this->inputParameters;
    }

    public function getRouteParameters() {
        return $this->routeParameters;
    }

    public function getParameters() {
        return $this->parameters;
    }

    public function getRawParameters() {
        return $this->rawParameters;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        if (!array_key_exists($name, $this->parameters)) {
            return $default;
        }

        return $this->parameters[$name];
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function getRequiredParameter($name)
    {
        $val = $this->getParameter($name);
        if ($val === null) {
            throw new Exception('Required parameter ['.$name.'] is missing');
        }

        return $val;
    }

    public function getRawParameter($name) {
        if(!array_key_exists($name, $this->rawParameters))
            return null;

        return $this->rawParameters[$name];
    }

    /* UPLOADED FILES */

    public function getUploadedFile($name) {
        if(!isset($_FILES[$name])) return false;

        if(is_array($_FILES[$name]['name'])) {
            $result = array();
            foreach($_FILES[$name]['name'] as $key => $name) {
                $file = new UploadedFile($name, $_FILES[$name]['tmp_name'][$key], $_FILES[$name]['size'][$key], $_FILES[$name]['type'][$key], $_FILES[$name]['error'][$key] );
                $result[] = $file;
            }
            return $result;
        }else {
            $file = new UploadedFile($_FILES[$name]['name'], $_FILES[$name]['tmp_name'], $_FILES[$name]['size'], $_FILES[$name]['type'], $_FILES[$name]['error']);
            return $file;
        }
    }

    public function getUploadedFiles() {
        $result = array();
        foreach((array)array_keys($_FILES) as $name) {
            $result[$name] = $this->getUploadedFile($name);
        }
        return $result;
    }


    /* COOKIES */

    protected function cleanCookies()
    {
        if(!$this->cleanedCookies)
        {
            if( get_magic_quotes_gpc() )
                $_COOKIE = StringUtils::stripslashesDeep($_COOKIE);

            $this->cleanedCookies = true;
        }
    }

    public function getCookies() {

        $this->cleanCookies();

        return $_COOKIE;
    }

    public function getCookie($name) {
        if(!isset($_COOKIE[$name])) return null;

        $this->cleanCookies();

        return $_COOKIE[$name];
    }


    public function getAcceptedLanguages() {
        if ((count($this->languages) == 0) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] != '')
        {
            $languages = preg_replace('/(;q=.+)/i', '', trim($_SERVER['HTTP_ACCEPT_LANGUAGE']));

            $languages = explode(',',$languages);

            $this->languages = array();

            foreach($languages as $language) {
                $l = explode('-',trim($language));
                $this->languages[] = $l[0];
            }
        }

        if (count($this->languages) == 0)
        {
            $this->languages = array();
        }

        return $this->languages;

    }

    public function getAcceptedCharsets() {
        if ((count($this->charsets) == 0) && isset($_SERVER['HTTP_ACCEPT_CHARSET']) && $_SERVER['HTTP_ACCEPT_CHARSET'] != '')
        {
            $charsets = preg_replace('/(;q=.+)/i', '', trim($_SERVER['HTTP_ACCEPT_CHARSET']));

            $this->charsets = explode(',', $charsets);
        }

        if (count($this->charsets) == 0)
        {
            $this->charsets = array();
        }
        return $this->charsets;
    }

    /**
     * Returns the encodings the client accepts.
     * from HTTP_ACCEPT_ENCODING
     *
     * @return string
     */
    public function getAcceptedEncodings()
    {
        if (null === $this->encodings) {
            if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
                $this->encodings = strtolower(trim($_SERVER['HTTP_ACCEPT_ENCODING']));
            } else {
                $this->encodings = '';
            }
        }

        return $this->encodings;
    }

    /**
     * Returns true if the supplied encoding is accepted by
     * the client.
     *
     * @param $encoding
     * @return bool
     */
    public function acceptsEncoding($encoding)
    {
        return (strpos($this->getAcceptedEncodings(), strtolower(trim($encoding))) !== false);
    }

    /**
     * Returns true if the client accepts gzip.
     *
     * @return bool
     */
    public function acceptsGzip()
    {
        return $this->acceptsEncoding('gzip');
    }

    public function acceptsLanguage($lang = 'en')
    {
        return (in_array(strtolower($lang), $this->getAcceptedLanguages(), TRUE)) ? TRUE : FALSE;
    }


    public function acceptsCharset($charset = 'utf-8')
    {
        return (in_array(strtolower($charset), $this->getAcceptedCharsets(), TRUE)) ? TRUE : FALSE;
    }


    /* SERVER ATTRIBUTES */

    public function getServerAttributes()
    {
        return $_SERVER;
    }

    public function getServerAttribute($index = '')
    {
        if (!isset($_SERVER[$index]))
        {
            return FALSE;
        }

        return $_SERVER[$index];
    }

    public function getHeader($name)
    {
        return $this->getServerAttribute('HTTP_'.strtoupper(str_replace(array('-'), array('_'), $name)));
    }

    public function getQueryString() {
        return $this->getServerAttribute('QUERY_STRING');
    }

    public function getMethod() {
        return $this->getServerAttribute('REQUEST_METHOD');
    }


    public function isReferral() {
        return ( ! isset($_SERVER['HTTP_REFERER']) OR $_SERVER['HTTP_REFERER'] == '') ? FALSE : TRUE;
    }

    public function getReferrer() {
        return ( ! isset($_SERVER['HTTP_REFERER']) OR $_SERVER['HTTP_REFERER'] == '') ? '' : trim($_SERVER['HTTP_REFERER']);
    }


    public function getPathTranslated() {
        return trim($this->getServerAttribute('PATH_TRANSLATED'));
    }

    public function getHost() {
        return $this->getServerAttribute('HTTP_HOST');
    }

    public function getServerName() {
        return $this->getServerAttribute('SERVER_NAME');
    }

    public function getServerIP() {
        return $this->getServerAttribute('SERVER_ADDR');
    }

    public function getBaseURL() {
        $s        = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? 's' : (empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "");
        $protocol = StringUtils::strLeft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
        $port     = ($_SERVER["SERVER_PORT"] == "80" || $_SERVER["SERVER_PORT"] == "443") ? "" : (":".$_SERVER["SERVER_PORT"]);
        $uri      = !empty($this->routerBase) ? rtrim($this->routerBase, '/').'/' : '/';
        return $protocol."://".$_SERVER['SERVER_NAME'].$port.$uri;
    }

    public function getServerPort() {
        return $this->getServerAttribute('SERVER_PORT');
    }

    public function isSecure() {
        return strtolower($this->getServerAttribute('HTTPS')) == 'on';
    }

    public function getServerProtocol() {
        return $this->getServerAttribute('SERVER_PROTOCOL');
    }

    public function getScheme() {
        return $this->isSecure()?'https':'http';
    }

    public function getFullURL() {
        return URLUtils::fullUrl();
    }

    public function getAdjustedRequestURI()
    {
        $uri = $this->getRequestURI();

        $base = rtrim($this->routerBase, '/');

        if(!empty($base) && strpos($uri, $base) === 0)
            $uri = substr($uri, strlen($base));

        if(trim($uri) == '')
            return '/';

        return $uri;
    }

    public function getRequestURI() {
        $path = $this->getServerAttribute('REQUEST_URI');
        if($path && strpos($path, '?')) $path = substr($path, 0, strpos($path,'?'));
        return $path;
    }

    public function getUserAgent() {
        return $this->getServerAttribute('HTTP_USER_AGENT');
    }

    public function getIPAddress() {

        if ($this->ip_address !== FALSE)
            return $this->ip_address;

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
             $this->ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        elseif(isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['HTTP_CLIENT_IP']))
             $this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
        elseif (isset($_SERVER['REMOTE_ADDR']))
             $this->ip_address = $_SERVER['REMOTE_ADDR'];
        elseif (isset($_SERVER['HTTP_CLIENT_IP']))
             $this->ip_address = $_SERVER['HTTP_CLIENT_IP'];

        if ($this->ip_address === FALSE)
            $this->ip_address = '0.0.0.0';

        if(strpos($this->ip_address, ',') !== FALSE)
            $this->ip_address = trim(end(explode(',',$this->ip_address)));

        return $this->ip_address;
    }
}

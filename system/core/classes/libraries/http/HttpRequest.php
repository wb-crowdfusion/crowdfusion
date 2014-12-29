<?php
/**
 * HttpRequest
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
 * @version     $Id: HttpRequest.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Simple implementation for working with sending HTTP requests via PHP's curl extension.
 * PHP's file_get_contents() is used as a failover.
 *
 * @package     CrowdFusion
 */
class HttpRequest implements HttpRequestInterface
{

    protected $timeout = null;
    protected $redirects = null;
    protected $userAgent = null;

    /**
     * Create a HttpRequest object. Multiple URLs can be fetched from a single instance.
     *
     * @param int    $httpRequestTimeout      The timeout (in seconds) before curl will error out. Default is 10s.
     * @param int    $httpRequestMaxRedirects The number of redirects curl will follow. Default is 5.
     * @param string $httpRequestUserAgent    The user agent string passed as a header to the request. Optional.
     *
     * @see HttpRequestInterface
     */
    public function __construct($httpRequestTimeout = 10, $httpRequestMaxRedirects = 5, $httpRequestUserAgent = null)
    {

        $this->timeout = $httpRequestTimeout;
        $this->redirects = $httpRequestMaxRedirects;
        $this->userAgent = $httpRequestUserAgent;
    }


    /**
     * Fetch the contents of a URL as a JSON array
     *
     * @param string $url Fully-qualified URL to request
     * @param bool   $followRedirects If true, follow redirects to default max of 5
     * @param string $username Username to use in http authentication header
     * @param string $password Password to use in http authentication header
     * @param array  $headers  Array of http headers to append to the existing headers list
     *
     * @return array PHP array version of JSON contents
     * @throws HttpRequestException If contents cannot be fetched
     */
    public function fetchJSON($url, $followRedirects = true, $username = null, $password = null, $headers = array())
    {
        $contents = $this->fetchURL($url, $followRedirects, $username, $password, $headers);
        return JSONUtils::decode($contents,true);
    }

    /**
     * Fetch the headers of a URL
     *
     * @param string $url Fully-qualified URL to request
     * @param bool   $followRedirects If true, follow redirects to default max of 5
     * @param string $username Username to use in http authentication header
     * @param string $password Password to use in http authentication header
     * @param array  $headers  Array of http headers to append to the existing headers list
     *
     * @return string Contents of the HTTP headers
     * @throws HttpRequestException If contents cannot be fetched
     */
    public function headURL($url, $followRedirects = true, $username = null, $password = null, $headers = array())
    {

        if (function_exists('curl_init')) {

                $contents = $this->curlRequest($url, true, $followRedirects, $username, $password, $headers);
                return $contents;

        } else {

            throw new HttpRequestException('headURL not supported without curl');

        }
    }

    protected function curlRequest($url, $headOnly = false, $followRedirects = true, $username = null, $password = null, $headers = array())
    {

        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $url);
        if ($this->timeout != null) {
            curl_setopt($c, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        }

        if($headOnly) {
            curl_setopt($c,CURLOPT_HEADER, true);
            curl_setopt($c,CURLOPT_NOBODY,1);
        }

        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);

        if (is_array($curl = curl_version()))
        {
            $version = $curl['version'];
        }

        if (!ini_get('open_basedir') && !ini_get('safe_mode') && version_compare($version, '7.15.2', '>='))
        {
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, $followRedirects);
            curl_setopt($c, CURLOPT_MAXREDIRS, $this->redirects);
        }

        if ($username && $password) {
            curl_setopt($c, CURLOPT_USERPWD, "$username:$password");
        }

        if (!empty($headers)) {
            curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($c, CURLOPT_REFERER, $url);
        if ($this->userAgent != null)
            curl_setopt($c, CURLOPT_USERAGENT, $this->userAgent);

        $contents = curl_exec($c);
        $error = curl_error($c);
        $responseCode = curl_getinfo($c, CURLINFO_HTTP_CODE);

        curl_close($c);

        // errors
        switch (true) {
            case $contents === false:
                throw new HttpRequestTransferException("Error fetching URL '".$url."': ".$error);
            case $responseCode >= 500:
                $e = new HttpRequestServerException("Request to URL '$url' returned server error $responseCode.  Body: '$contents'", $responseCode);
                $e->responseBody = $contents;
                throw $e;
            case $responseCode >= 400:
                $e = new HttpRequestServerException("Request to URL '$url' returned client error $responseCode.  Body: '$contents'", $responseCode);
                $e->responseBody = $contents;
                throw $e;
            default:
        }

        unset($error);
        unset($url);
        unset($responseCode);
        unset($curl);
        unset($c);
        unset($version);

        return $contents;
    }

    /**
     * Posts the data to the given URL
     *
     * @param string $url Fully-qualified URL to request
     * @param mixed $postData The full data to post in a HTTP "POST" operation. To post a file, prepend a filename with
     * @ and use the full path. This can either be passed as a urlencoded string like 'para1=val1&para2=val2&...' or as
     * an array with the field name as key and field data as value.
     * @param string $username Username to use in http authentication header
     * @param string $password Password to use in http authentication header
     * @param array  $headers  Array of http headers to append to the existing headers list
     *
     * @return string Contents of the HTTP response body
     * @throws HttpRequestException
     */
    public function postURL($url, $postData, $username = null, $password = null, $headers = array(), &$responseHeaders = false)
    {


        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $url);
        if ($this->timeout != null) {
            curl_setopt($c, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        }

        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, $postData);

        if ($username && $password) {
            curl_setopt($c, CURLOPT_USERPWD, "$username:$password");
        }

        if (!empty($headers)) {
            curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        }

        if ($responseHeaders !== false) {
            curl_setopt($c, CURLOPT_HEADER, true);
        }

        curl_setopt($c, CURLOPT_REFERER, $url);
        if ($this->userAgent != null)
            curl_setopt($c, CURLOPT_USERAGENT, $this->userAgent);

        $rawContents = curl_exec($c);
        $error = curl_error($c);
        $responseCode = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);

        if ($rawContents && $responseHeaders !== false) {
            list($headerBlock, $contents) = explode("\\r\\n\\r\\n", $rawContents, 2);
            $responseHeaders = array();
            foreach(explode("\\r\\n", $headerBlock) as $headerString) {
                @list($headerName, $headerValue) = explode(':', $headerString, 2);
                $responseHeaders[trim($headerName)] = trim($headerValue);
            }
        } else {
            $contents = $rawContents;
        }

        // errors
        switch (true) {
            case $contents === false:
                throw new HttpRequestTransferException("Error posting to URL '".$url."': ".$error);
            case $responseCode >= 500:
                $e = new HttpRequestServerException("Request to URL '$url' returned server error $responseCode.  Body: '$contents'", $responseCode);
                $e->responseBody = $contents;
                $e->responseHeaders = $headers;
                throw $e;
            case $responseCode >= 400:
                $e = new HttpRequestServerException("Request to URL '$url' returned client error $responseCode.  Body: '$contents'", $responseCode);
                $e->responseBody = $contents;
                $e->responseHeaders = $headers;
                throw $e;
            default:
        }

        unset($error);
        unset($responseCode);
        unset($curl);
        unset($url);
        unset($c);

        return $contents;

    }



    /**
     * Fetch the contents of a URL
     *
     * @param string $url Fully-qualified URL to request
     * @param bool   $followRedirects If true, follow redirects to default max of 5
     * @param string $username Username to use in http authentication header
     * @param string $password Password to use in http authentication header
     * @param array  $headers  Array of http headers to append to the existing headers list
     *
     * @return string Contents of the HTTP response body
     * @throws HttpRequestException If contents cannot be fetched
     */
    public function fetchURL($url, $followRedirects = true, $username = null, $password = null, $headers = array())
    {

        if (function_exists('curl_init')) {

            $contents = $this->curlRequest($url, false, $followRedirects, $username, $password, $headers);

            return $contents;

        } else {

            if ($this->userAgent != null) {
                $opts = array(
                    'http'=> array(
                    'user_agent'=> $this->userAgent,
                    'max_redirects' => $this->redirects,              // stop after 10 redirects
                  )
                );

            }

            if ($username && $password) {
                $opts = ($opts) ? $opts : array('http' => array());
                $opts['http']['header'] =
                    array('Authorization: Basic '
                        . base64_encode("$username:$password"));
            }

            if ($headers) {
                $opts['http']['header'] =$headers;
            }

            $context = ($opts) ? stream_context_create($opts) : null;

            unset($opts);

            $contents = @file_get_contents($url, false, $context);

            if ($contents === false)
                throw new HttpRequestException("Unknown error fetching URL '".$url."'");

            unset($context);
            unset($url);

            return $contents;
        }
    }
}

<?php
/**
 * Interface for working with HTTP Requests
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
 * @version     $Id: HttpRequestInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Interface for working with HTTP Requests
 *
 * @package     CrowdFusion
 */
interface HttpRequestInterface
{

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
    public function fetchURL($url, $followRedirects = true, $username = null, $password = null, $headers = array());



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
    public function fetchJSON($url, $followRedirects = true, $username = null, $password = null, $headers = array());


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
    public function headURL($url, $followRedirects = true, $username = null, $password = null, $headers = array());




    /**
     * Post the data to a URL
     *
     * @param string $url Fully-qualified URL to request
     * @param mixed $postData The full data to post in a HTTP "POST" operation. To post a file, prepend a filename with
     * @ and use the full path. This can either be passed as a urlencoded string like 'para1=val1&para2=val2&...' or as
     * an array with the field name as key and field data as value.
     * @param string $username Username to use in http authentication header
     * @param string $password Password to use in http authentication header
     * @param array  $headers  Array of http headers to append to the existing headers list
     * @param mixed &$responseHeaders pass in an array and they will be populated
     *
     * @return string Contents of the HTTP response body
     * @throws HttpRequestServerException
     * @throws HttpRequestTransferException
     */
    public function postURL($url, $postData, $username = null, $password = null, $headers = array(), &$responseHeaders = false);

}
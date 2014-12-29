<?php
/**
 * MessageCodeResolver
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
 * @version     $Id: MessageCodeResolver.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Provides the ability to resolve a "message code" to a localized message string
 * from the property files.
 *
 * @package     CrowdFusion
 */
class MessageCodeResolver
{
    protected $prefix         = '';
    protected $basename       = 'messages';
    protected $RequestContext = null;
    protected $Request;
    protected $MessageService = null;
    protected $VersionService;
    protected $MessageCacheStore;

    /**
     * [IoC] Injects the MessageCacheStore
     *
     * @param MessageCacheStore $MessageCacheStore The MessageCacheStore
     *
     * @return void
     */
    public function setMessageCacheStore(CacheStoreInterface $MessageCacheStore)
    {
        $this->MessageCacheStore = $MessageCacheStore;
    }

    /**
     * [IoC] Injects our prefix, which is added to all codes.
     *
     * @param string $prefix The prefix to prepend to codes. Default: ''
     *
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * [IoC] Injects the basename, which is the base filename for the properties file
     * that holds our messages
     *
     * @param string $basename The basename of the properties file to access.
     *                          Default: 'messages'
     *
     * @return void
     */
    public function setBasename($basename)
    {
        $this->basename = $basename;
    }

    /**
     * [IoC] Injects the RequestContext
     *
     * @param RequestContext $RequestContext The RequestContext
     *
     * @return void
     */
    public function setRequestContext(RequestContext $RequestContext)
    {
        $this->RequestContext = $RequestContext;
    }

    /**
     * [IoC] Injects the MessageService
     *
     * @param MessageService $MessageService The MessageService
     *
     * @return void
     */
    public function setMessageService(MessageService $MessageService)
    {
        $this->MessageService = $MessageService;
    }

    /**
     * [IoC] Injects the VersionService
     *
     * @param VersionService $VersionService The VersionService
     *
     * @return void
     */
    public function setVersionService(VersionService $VersionService)
    {
        $this->VersionService = $VersionService;
    }

    /**
     * [IoC] Injects the Request
     *
     * @param Request $Request The Request
     *
     * @return void
     */
    public function setRequest(Request $Request)
    {
        $this->Request = $Request;
    }

    /**
     * Returns the best matching message for the {@link $code} given.
     *
     * @param string $code           The code to resolve
     * @param array  $args           An array of arguments, used to process the message
     *                                  (usually dynamic content that's part of the resultant string)
     * @param string $defaultMessage The default message to display,
     *                                  if no suitable messages could be resolved.
     *
     * @return string The best matching message for the given code
     */
    public function resolveMessageCode($code, $args = null, $defaultMessage = '')
    {
        $code = $this->prefix.$code;

        $paths = $this->getPropertyFilepaths();

        $cachekey = null;

        if($this->MessageCacheStore != null) {
            $cachekey = $this->buildCacheKey($paths);

            if($this->MessageCacheStore->containsKey($cachekey)) {
                $propsets = $this->MessageCacheStore->get($cachekey);

                if($propsets !== FALSE)
                    return $this->getBestMatch($propsets, $code, $defaultMessage, $args);
            }
        }

        $propsets = array();
        foreach ((array)$paths as $path) {
            $props = $this->parsePropertiesFile($path);
            $propsets[] = $props;
        }

        if($this->MessageCacheStore != null) {
            $this->MessageCacheStore->put($cachekey,$propsets,0);
        }

        return $this->getBestMatch($propsets, $code, $defaultMessage, $args);
    }

    protected function getBestMatch(array $propsets, $code, $defaultMessage, $args)
    {
        $best = null;
        foreach($propsets as $props) {
            if(array_key_exists($code,$props))
                $best = $this->processMessage($props[$code], $args);
        }

        if($best == null)
            return $this->processMessage($defaultMessage, $args);

        return $best;
    }

    protected function buildCacheKey($paths)
    {
        $cachekey = "msgs-{$this->VersionService->getSystemVersion()}-{$this->VersionService->getDeploymentRevision()}-paths(".implode('|', $paths).")";

        return $cachekey;
    }


    /**
     * Returns a message containing {@link $args} where appropriate.
     *
     * @param string $msg  The message to process
     * @param array  $args The arguments that get included in the message
     *
     * @return string A string with the args interpolated in appropriate locations.
     */
    protected function processMessage($msg, $args)
    {
        return $args == null ? $msg : vsprintf($msg, (array)$args);
    }

    /**
     * Get a list of reverse-specific absolute property file paths
     *
     * @return array
     */
    protected function getPropertyFilepaths()
    {
        $file = $this->basename.'.properties';

        if($this->MessageService->fileExists($file))
            return $this->MessageService->resolveFile($file)->getLocalPath();

        return null;
    }

    /**
     * Returns an array representing all the properties contained
     * in the file specified by {@link $path}
     *
     * @param string $path The name of a file containing properties
     *
     * @return array Like ['property' => 'value', 'other.property' => 'another %s value']
     */
    protected function parsePropertiesFile($path)
    {
        $props = array();

        if (file_exists($path)) {
            $f = fopen($path, "r");
            if ($f !== false) {
                while (!feof($f)) {
                    $line = fgets($f);
                    $r    = preg_match("/([\w.]+)\s+=\s+(.+)/", $line, $m);
                    if ($r !== false && count($m) == 3)
                        $props[$m[1]] = $m[2];
                }
                fclose($f);
            }
        }

        return $props;
    }
}

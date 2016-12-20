<?php
/**
 * ErrorCodeResolver
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
 * @version     $Id: ErrorCodeResolver.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Provides the ability to resolve Error "codes" into localized, human-readable messages.
 *
 * @package     CrowdFusion
 */
class ErrorCodeResolver extends MessageCodeResolver
{
    protected $basename = 'errors';

    /**
     * Returns the best matching error message for the {@link $code} given.
     *
     * @param string $code           The error code to resolve
     * @param array  $args           An array of arguments, used to process the message
     *                                  (usually dynamic content that's part of the resultant string)
     * @param string $defaultMessage The default message to display,
     *                                  if no suitable messages could be resolved.
     *
     * @return string The best matching message for the given code
     */
    public function resolveMessageCode($code, $args = NULL, $defaultMessage = ''))
    {
        $best  = null;
        $paths = $this->getPropertyFilepaths();

        foreach ((array)$paths as $path) {
            $props = $this->parsePropertiesFile($path);

            // code (least specific)
            $code = $this->prefix.$errorCode;
            if (array_key_exists($code, $props))
                $best = $this->processMessage($props[$code], $args);
        }

        if ($best == null)
            return $defaultMessage;

        return $best;
    }

}

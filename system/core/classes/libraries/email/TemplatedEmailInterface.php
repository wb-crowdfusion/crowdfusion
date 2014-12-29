<?php
/**
 * TemplatedEmailInterface
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
 * @version     $Id$
 */

/**
 * TemplatedEmailInterface
 *
 * @package     CrowdFusion
 */
interface TemplatedEmailInterface
{

    /**
     * Sends an email using a CFT template as the means of generating the markup
     *
     * @abstract
     * @param  $templateFile
     * @param  $parameters
     * @param string $viewHandler
     * @return void
     */
    public function send($templateFile, $parameters, $viewHandler = 'html');

}
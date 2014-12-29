<?php
/**
 * MessageService
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
 * @version     $Id: MessageService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Service for the management of template files.  All classes interacting with
 * templates use this service for retrieving and saving templates.
 *
 * In this implementation, the storage medium for a template is a file.
 *
 * @package     CrowdFusion
 */
class MessageService extends AbstractFileDeploymentService
{
    protected $subject = "messages";
}
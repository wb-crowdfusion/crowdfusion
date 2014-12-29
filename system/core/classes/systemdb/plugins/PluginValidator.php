<?php
/**
 * PluginValidator
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
 * @version     $Id: PluginValidator.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PluginValidator
 *
 * @package     CrowdFusion
 */
class PluginValidator extends AbstractSystemValidator
{
    /**
     * [IoC] Creates the PluginValidator
     *
     * @param DAOInterface $PluginDAO PluginDAO
     */
    public function __construct(DAOInterface $PluginDAO)
    {
        parent::__construct($PluginDAO);
    }

}
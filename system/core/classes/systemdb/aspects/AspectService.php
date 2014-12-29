<?php
/**
 * AspectService
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
 * @version     $Id: AspectService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Aspects Service. Provides generic management of aspects (see AbstractSystemService)
 *
 * @package     CrowdFusion
 */
class AspectService extends AbstractSystemService
{
    /**
     * Builds the service
     *
     * @param DateFactory        $DateFactory     DateFactory
     * @param DAOInterface       $AspectDAO       AspectDAO
     * @param ValidatorInterface $AspectValidator AspectValidator
     */
    public function __construct(DateFactory $DateFactory, DAOInterface $AspectDAO, ValidatorInterface $AspectValidator)
    {
        parent::__construct($DateFactory, $AspectDAO, $AspectValidator);
    }

}
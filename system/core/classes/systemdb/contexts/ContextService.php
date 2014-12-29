<?php
/**
 * ContextService
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
 * @version     $Id: ContextService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ContextService
 *
 * @package     CrowdFusion
 */
class ContextService extends AbstractSystemService
{
    /**
     * [IoC] Creates the ContextService using injection
     *
     * @param DateFactory        $DateFactory   The datefactory
     * @param DAOInterface       $ContextDAO       The ContextDAO
     * @param ValidatorInterface $ContextValidator The ContextValidator
     */
    public function __construct(DateFactory $DateFactory, DAOInterface $ContextDAO, ValidatorInterface $ContextValidator)
    {
        parent::__construct($DateFactory, $ContextDAO, $ContextValidator);
    }

    public function add(ModelObject $obj) {
        throw new Exception('Adding Contexts is deprecated');
    }

    public function edit(ModelObject $obj) {
        throw new Exception('Editing Contexts is deprecated');
    }

    public function delete($objSlug) {
        throw new Exception('Deleting Contexts is deprecated');
    }

}
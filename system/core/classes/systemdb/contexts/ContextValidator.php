<?php
/**
 * ContextValidator
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
 * @version     $Id: ContextValidator.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ContextValidator
 *
 * @package     CrowdFusion
 */
class ContextValidator extends AbstractSystemValidator
{
    /**
     * Creates the validator
     *
     * @param DAOInterface $ContextDAO The ContextDAO object
     */
    public function __construct(DAOInterface $ContextDAO)
    {
        parent::__construct($ContextDAO);
    }

    protected function edit(ModelObject $Context)
    {
        parent::edit($Context);

        if (($existing = $this->dao->getByID($Context->ContextID)) == false) {
            $this->getErrors()->reject('Context not found for: '.$Context->ContextID)->throwOnError();
        }

    }
}
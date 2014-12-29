<?php
/**
 * SiteValidator
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
 * @version     $Id: SiteValidator.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SiteValidator
 *
 * @package     CrowdFusion
 */
class SiteValidator extends AbstractSystemValidator
{
    /**
     * Creates the validator
     *
     * @param DAOInterface $SiteDAO The SiteDAO object
     */
    public function __construct(DAOInterface $SiteDAO)
    {
        parent::__construct($SiteDAO);
    }

    protected function edit(ModelObject $site)
    {
        parent::edit($site);

        if (($existing = $this->dao->getByID($site->SiteID)) == false) {
            $this->getErrors()->reject('Site not found for: '.$site->SiteID)->throwOnError();
        }

    }
}
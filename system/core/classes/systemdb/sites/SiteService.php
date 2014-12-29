<?php
/**
 * SiteService
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
 * @version     $Id: SiteService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SiteService
 *
 * @package     CrowdFusion
 */
class SiteService extends AbstractSystemService
{
    /**
     * [IoC] Creates the SiteService using injection
     *
     * @param DateFactory        $DateFactory   The datefactory
     * @param DAOInterface       $SiteDAO       The siteDAO
     * @param ValidatorInterface $SiteValidator The SiteValidator
     */
    public function __construct(DateFactory $DateFactory, DAOInterface $SiteDAO, ValidatorInterface $SiteValidator)
    {
        parent::__construct($DateFactory, $SiteDAO, $SiteValidator);
    }

    public function add(ModelObject $obj) {
        throw new Exception('Adding sites is deprecated');
    }

    public function edit(ModelObject $obj) {
        throw new Exception('Editing sites is deprecated');
    }

    public function delete($objSlug) {
        throw new Exception('Deleting sites is deprecated');
    }

    public function getAnchoredSite()
    {
        foreach($this->findAll()->getResults() as $site)
            if($site->isAnchor())
                return $site;

        return null;
    }

}
<?php
/**
 * CMSNavItemValidator
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
 * @version     $Id: CMSNavItemValidator.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * CMSNavItemValidator
 *
 * @package     CrowdFusion
 */
class CMSNavItemValidator extends AbstractSystemValidator
{
    /**
     * [IoC] Creates the Validator
     *
     * @param DAOInterface $CMSNavDAO The DAO
     */
    public function __construct(DAOInterface $CMSNavItemDAO)
    {
        parent::__construct($CMSNavItemDAO);
    }

    /**
     * Prevent deleting items that have children
     *
     * @param string $slug The slug of the item to remove
     *
     * @return void
     */
    public function delete($slug)
    {
        parent::delete($slug);

        // Children are just orphaned.
        // $item = $this->dao->getBySlug($slug);
        // if ($item) {
        //     // Look up items by parent slug
        //     $dto = new DTO();
        //     $dto->setParameter('ParentSlug', $slug);
        //     $dto_result = $this->dao->findAll($dto);
        //
        //     if (count($dto_result->getResults()) > 0)
        //         $this->getErrors()->reject('Cannot delete CMSNavItem that has child items!');
        // }
    }
}
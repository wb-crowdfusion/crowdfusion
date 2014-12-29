<?php
/**
 * CMSNavItemService
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
 * @version     $Id: CMSNavItemService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Service to store and retrieve CMSNavItems
 *
 * @package     CrowdFusion
 */
class CMSNavItemService extends AbstractSystemService
{

    protected $SystemCache;

    /**
     * Creates the CmsNavigationService
     *
     * @param DateFactory        $DateFactory         Our DateFactory (see AbstractSystemService)
     * @param DAOInterface       $CMSNavDAO           The DAO for CMSNavItems
     * @param ValidatorInterface $CMSNavItemValidator The Validator
     */
    public function __construct(DateFactory $DateFactory, DAOInterface $CMSNavItemDAO, ValidatorInterface $CMSNavItemValidator)
    {
        parent::__construct($DateFactory, $CMSNavItemDAO, $CMSNavItemValidator);
    }

    /**
     * [IoC] Injects the SystemCache
     *
     * @param SystemCacheInterface $SystemCache SystemCache
     *
     * @return void
     */
    public function setSystemCache(SystemCacheInterface $SystemCache)
    {
        $this->SystemCache = $SystemCache;
    }

    /**
     * Returns an array that represents the CMS Navigation menu
     *
     * @param DTO $dto DTO to pass through to our DAO's findAll()
     *
     * @return array An array representing the cms nav menu
     */
    public function getMenu(DTO $dto=null)
    {
        if (!$menu = $this->SystemCache->get('cms-menu')) {
            $menu = array();

            if ($dto === null)
                $dto = new DTO();

            $dto->setOrderBy('SortOrder', 'ASC');
            $dto->setParameter('FlattenChildren', false);
            $dto->setParameter('Enabled', true);

            $menu = $this->dao->findAll($dto)->getResults();

            if (count($menu) < 1)
                throw new Exception('No menu items exist on the root level.');

            $this->SystemCache->put('cms-menu', $menu, 0);
        }

        return $menu;
    }

}

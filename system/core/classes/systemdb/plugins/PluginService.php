<?php
/**
 * PluginService
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
 * @version     $Id: PluginService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PluginService
 *
 * @package     CrowdFusion
 */
class PluginService extends AbstractSystemService
{
    /**
     * [IoC] Creates the PluginService
     *
     * @param DateFactory        $DateFactory     DateFactory
     * @param DAOInterface       $PluginDAO       PluginDAO
     * @param ValidatorInterface $PluginValidator PluginValidator
     */
    public function __construct(DateFactory $DateFactory, DAOInterface $PluginDAO, ValidatorInterface $PluginValidator)
    {
        parent::__construct($DateFactory, $PluginDAO, $PluginValidator);
    }

    /**
     * Returns an array of all active plugins
     *
     * @return array
     */
    public function getAllActive()
    {
        $dto = new DTO();

        $dto->setParameter("Status", "enabled");
        $dto->setParameter("Installed", "1");
        $dto->setOrderBy("Priority");

        $dto = $this->findAll($dto);

        return $dto->getResults();
    }

}
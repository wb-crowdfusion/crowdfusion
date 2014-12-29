<?php
/**
 * AbstractDAO
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
 * @version     $Id: AbstractDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Defines some basic items shared across all DAO implementations
 *
 * @package     CrowdFusion
 */
abstract class AbstractDAO implements DAOInterface
{
    protected $Logger;
    protected $DateFactory;
    protected $DTOHelper;

    /**
     * Sets up our DateFactory.
     * This is filled during the autowire process, so you probably won't need to use it.
     *
     * @param DateFactory $DateFactory Our DateFactory to use
     *
     * @return void
     */
    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    /**
     * Sets up the Logger we'll use.
     * This is filled during the autowire process, so you probably won't need to use it.
     *
     * @param LoggerInterface $Logger The Logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

    public function setDTOHelper(DTOHelper $DTOHelper)
    {
        $this->DTOHelper = $DTOHelper;
    }

    public function __construct()
    {
        
    }

}
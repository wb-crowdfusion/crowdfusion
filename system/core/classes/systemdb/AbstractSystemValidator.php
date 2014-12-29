<?php
/**
 * AbstractSystemValidator
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
 * @version     $Id: AbstractSystemValidator.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Abstract System Validator.
 * Provides generic validation services that can be used on most ModelObjects
 *
 * @package     CrowdFusion
 */
abstract class AbstractSystemValidator extends AbstractValidator
{
    protected $dao;

    /**
     * Builds our validator around the specified DAO
     *
     * @param DAOInterface $dao The DAO to build the validator around
     */
    public function __construct(DAOInterface $dao)
    {
        parent::__construct();

        $this->dao = $dao;
    }

    /**
     * Validates the model object and ensures that slug doesn't exist anywhere.
     *
     * @param ModelObject $obj The object to validate
     *
     * @return void
     */
    protected function add(ModelObject $obj)
    {
        // Check that all required fields exist.
        $this->getErrors()->validateModelObject($obj);

        if ($this->dao->slugExists($obj->Slug))
            $this->errors->reject("The slug for this ".get_class($obj)." record is already in use.");
    }

    /**
     * Ensures we have a valid model object, and that it's slug is not in conflict,
     * then validates the object
     *
     * @param ModelObject $obj The object to validate
     *
     * @return void
     */
    protected function edit(ModelObject $obj)
    {
        if ($obj->{$obj->getPrimaryKey()} == null)
            $this->errors->reject("{$obj->getPrimaryKey()} is required to edit.")->throwOnError();

        if ($this->dao->getByID($obj->{$obj->getPrimaryKey()}) == false)
            $this->getErrors()->reject("Record not found for ID: " . $obj->{$obj->getPrimaryKey()})->throwOnError();

        if ($this->dao->slugExists($obj->Slug, $obj->{$obj->getPrimaryKey()}))
            $this->errors->reject("The slug for this ".get_class($obj)." record is already in use.")->throwOnError();

        $this->errors->validateModelObject($obj);
    }

    /**
     * Ensures that the record exists for the given slug
     *
     * @param string $slug The slug to lookup
     *
     * @return void
     */
    protected function delete($slug)
    {
        if ($this->dao->getBySlug($slug) == false)
            $this->getErrors()->reject('Record not found for slug:' . $slug);
    }

}
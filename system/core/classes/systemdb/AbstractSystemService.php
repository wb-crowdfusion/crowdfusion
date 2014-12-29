<?php
/**
 * AbstractSystemService
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
 * @version     $Id: AbstractSystemService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Abstract System Service.
 * Provides an interface to the given DAO for the following methods:
 *  add (after Validation)
 *  edit (after validation)
 *  delete (after validation)
 *  slugExists
 *  getBySlug
 *  multiGetBySlug
 *  getByID
 *  findAll
 *
 * @package     CrowdFusion
 */
abstract class AbstractSystemService
{
    protected $dao;
    protected $validator;
    protected $DateFactory;

    /**
     * Creates the AbstractSystemService.
     * Any class that extends this must use pass the required objects to the constructor,
     * or not define a constructor
     *
     * @param DateFactory        $DateFactory The DateFactory to create dates
     * @param DAOInterface       $dao         The Data Access Object that supplies functionality to our methods
     * @param ValidatorInterface $validator   The Validator that will check the ModelObjects
     */
    public function __construct(DateFactory $DateFactory, DAOInterface $dao, ValidatorInterface $validator)
    {
        $this->DateFactory = $DateFactory;
        $this->dao         = $dao;
        $this->validator   = $validator;

    }

    /**
     * Performs an INSERT for the given ModelObject through our DAO.
     * CreationDate and ModifiedDate are set, and the validator is run,
     *
     * Validation errors will throw a ValidationException
     *
     * @param ModelObject $obj The Object to Add
     *
     * @return mixed See the add() method of our DAO
     */
    public function add(ModelObject $obj)
    {
        $obj->CreationDate = $this->DateFactory->newStorageDate();
        if(!$obj->hasModifiedDate())
            $obj->ModifiedDate = $this->DateFactory->newStorageDate();

        $this->validator->validateFor(__FUNCTION__, $obj)->throwOnError();

        return $this->dao->add($obj);
    }

    /**
     * Performs an UPDATE for the given ModelObject through our DAO.
     * ModifiedDate is updated, and the validator is run.
     *
     * Validation Errors will throw a ValidationException
     *
     * @param ModelObject $obj The ModelObject to edit
     *
     * @return mixed See the edit() method of our DAO
     */
    public function edit(ModelObject $obj)
    {
        if(!$obj->hasModifiedDate())
            $obj->ModifiedDate = $this->DateFactory->newStorageDate();

        $this->validator->validateFor(__FUNCTION__, $obj)->throwOnError();
        return $this->dao->edit($obj);
    }

    /**
     * Performs a DELETE for the given slug through our DAO.
     * The validator is run.
     *
     * Validation Errors will throw a ValidationException
     *
     * @param string $objSlug The slug of the item to delete
     *
     * @return mixed See the delete() method of our DAO
     */
    public function delete($objSlug)
    {
        $this->validator->validateFor(__FUNCTION__, $objSlug)->throwOnError();

        return $this->dao->delete($objSlug);
    }

    /**
     * Returns the result from slugExists() in our DAO.
     *
     * @param string  $slug      A slug
     * @param integer $excludeID Look at all items except the item with this id.
     *
     * @return boolean see slugExists() on our DAO
     */
    public function slugExists($slug, $excludeID = false)
    {
        return $this->dao->slugExists($slug, $excludeID);
    }

    /**
     * Passthrough function for getBySlug for our DAO
     *
     * @param string $slug A slug
     *
     * @return mixed see our DAO::getBySlug()
     */
    public function getBySlug($slug)
    {
         return $this->dao->getBySlug($slug);
    }

    /**
     * Passthrough function for DAO::multiGetBySlug()
     *
     * @param array $slugs An array of slugs
     *
     * @return mixed See DAO::multiGetBySlug()
     */
    public function multiGetBySlug(array $slugs)
    {
        return $this->dao->multiGetBySlug($slugs);
    }

    /**
     * Passthrough function for DAO::getByID()
     *
     * @param integer $id An ID
     *
     * @return mixed See DAO::getByID()
     */
    public function getByID($id)
    {
         return $this->dao->getByID($id);
    }

    /**
     * Passthrough function for DAO::findAll()
     *
     * @param DTO $dto (Optional) a DTO
     *
     * @return mixed See DAO::findAll()
     */
    public function findAll(DTO $dto = null)
    {
        if(is_null($dto))
            $dto = new DTO();
        return $this->dao->findAll($dto);
    }
}

<?php   
/**
 * SystemServiceInterface
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
 * @version     $Id: SystemServiceInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SystemServiceInterface
 *
 * @package     CrowdFusion
 */
interface SystemServiceInterface
{
    /**
     * Writes the ModelObject into the database (or wherever it'll be stored.)
     * This creates a new navigation item.
     *
     * @param ModelObject $obj The navigation item to create
     *
     * @return ModelObject The created nav item
     */
    public function add(ModelObject $obj);

    /**
     * Updates the ModelObject passed into the function in the database (or wherever it's stored)
     *
     * @param ModelObject $obj The nav item to update
     *
     * @return ModelObject The updated nav item
     */
    public function edit(ModelObject $obj);

    /**
     * Deletes the object with the given {@link $objID}, permanently.
     *
     * @param int $objID The item id to obliterate.
     *
     * @return void
     */
    public function delete($objID);

    /**
     * Used to fetch the ModelObject tree in array or object format
     *
     * @param DTO $dto The DTO that determines what to find
     *
     * @return array An array of results
     */
    public function findAll(DTO $dto = null);

    /**
     * Retrieves the ModelObject specified by {@link $slug}
     *
     * @param string $slug The slug to fetch
     *
     * @return ModelObject
     */
    public function getBySlug($slug);

    /**
     * Determines if a ModelObject with the specified {@link $slug} exists
     *
     * @param string $slug The slug of the ModelObject to check
     *
     * @return boolean
     */
    public function slugExists($slug);
}
?>
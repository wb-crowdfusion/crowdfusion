<?php
/**
 * CMSNavItemDAO
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
 * @version     $Id: CMSNavItemDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * CMSNavItemDAO
 *
 * @package     CrowdFusion
 */
class CMSNavItemDAO extends AbstractSystemXMLDAO
{

    /**
     * Creates the CmsNavigationService
     */
    public function __construct()
    {
        parent::__construct(new CMSNavItem());
    }


    protected function loadObject($object)
    {
        parent::loadObject($object);

        if($object->hasChildren())
            foreach($object->getChildren() as $child)
                parent::loadObject($child);
    }

    protected function persistObject($object)
    {
        if($object->hasParentSlug())
            return null;

        $children = array();
        foreach($this->objectsBySlug as $obj)
        {
            if($object->Slug == $obj->ParentSlug)
            {
                $children[] = clone $obj;
            }
        }

        $object->setChildren($children);
        return $object;

    }

    public function findAll(DTO $dto)
    {
        $sd = __CLASS__.(string)serialize($dto);
        $slugs = $this->SystemCache->get($sd);

        if ($slugs === false) {

            $this->loadSource();

            $slugs = array();
            $sort_array = array();

            $dir = 'ASC';

            $orderbys = $dto->getOrderBys();
            if(!empty($orderbys))
                foreach($orderbys as $col => $dir) {}
            else
                $col = 'Slug';

            foreach($this->objectsBySlug as $slug => $obj)
            {

                if($dto->hasParameter('Enabled') && $obj->Enabled != $dto->getParameter('Enabled'))
                    continue;

                if(!$dto->hasParameter('FlattenChildren') || StringUtils::strToBool($dto->getParameter('FlattenChildren')) == false)
                {
                    if($obj->hasParentSlug())
                        continue;
                }

                if(($val = $dto->getParameter('Slug')) != null)
                {
                    if($obj->Slug != $val)
                        continue;
                }

                if(($val = $dto->getParameter('PluginID')) != null)
                {
                    if($obj->PluginID != $val)
                        continue;
                }

                $slugs[] = $slug;

                $sort_array[] = $obj[$col];
            }

            array_multisort($sort_array, strtolower($dir)=='asc'?SORT_ASC:SORT_DESC, SORT_REGULAR, $slugs);
            $this->SystemCache->put($sd, $slugs, 0);
        }


        // retrieve objects
        $rows = $this->multiGetBySlug($slugs);

        $results = array();
        foreach ($slugs as $slug) {
            if(isset($rows[$slug]))
               $results[] = $rows[$slug];
        }

        return $dto->setResults($results);
    }
}
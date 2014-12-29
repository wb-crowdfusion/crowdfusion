<?php
/**
 * ElementValidator
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
 * @version     $Id: ElementValidator.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ElementValidator
 *
 * @package     CrowdFusion
 */
class ElementValidator extends AbstractSystemValidator
{
    /**
     * Creates the validator
     *
     * @param DAOInterface $ElementDAO The ElementDAO
     */
    public function __construct(DAOInterface $ElementDAO)
    {
        parent::__construct($ElementDAO);
    }

    protected function add(ModelObject $obj)
    {
        $roles = array();
        foreach((array)$obj->Aspects as $aspect)
        {
            foreach($aspect->getSchema()->getTagDefs() as $id => $tagDef)
            {
                if(array_key_exists($id, $roles)){
                    $aspectName = $roles[$id];
                    $this->getErrors()->reject('Aspect ['.$aspect->Name.'] '.$tagDef->Direction.'bound tag definition [#'.$id.'] conflicts with the ['.$aspectName.'] aspect')->throwOnError();
                }
                $roles[$tagDef->Id] = $aspect->Name;
            }

            foreach($aspect->getSchema()->getMetaDefs() as $id => $metaDef)
            {
                if(array_key_exists($id, $roles)){
                    $aspectName = $roles[$id];
                    $this->getErrors()->reject('Aspect ['.$aspect->Name.'] meta definition [#'.$id.'] conflicts with the ['.$aspectName.'] aspect')->throwOnError();
                }
                $roles[$id] = $aspect->Name;
            }

            $elementMode = $aspect->ElementMode;
            $hasElements = $this->dao->findAll(new DTO(array('IncludesAspect'=> $aspect->Slug)))->hasResults();

            if($elementMode == 'one' || $elementMode == 'anchored') {
                if($hasElements)
                    $this->getErrors()->reject('Aspect ['.$aspect->Name.'] cannot be used by more than one Element')->throwOnError();
//            } else if ($elementMode == 'anchored')
//            {
//                if($hasElements)
//                    $this->getErrors()->reject('Aspect ['.$aspect->Name.'] cannot be used by more than one Element')->throwOnError();
//                else if(!$obj->isAnchored())
//                    $this->getErrors()->reject('Element using Aspect ['.$aspect->Name.'] must be anchored')->throwOnError();

            }
        }

        parent::add($obj);
    }

    protected function edit(ModelObject $obj)
    {
        parent::edit($obj);

        if (($existing = $this->dao->getByID($obj->ElementID)) == false) {
            $this->getErrors()->reject('Element not found for: '.$obj->ElementID)->throwOnError();
        }

        if($existing->Slug != $obj->Slug) {
            $this->getErrors()->reject('Element slug cannot be changed.')->throwOnError();
        }

        $roles = array();

        foreach((array)$obj->Aspects as $aspect)
        {
            foreach($aspect->getSchema()->getTagDefs() as $id => $tagDef)
            {
                if(array_key_exists($id, $roles)){
                    $aspectName = $roles[$id];
                    $this->getErrors()->reject('Aspect ['.$aspect->Name.'] '.$tagDef->Direction.'bound tag definition [#'.$id.'] conflicts with the ['.$aspectName.'] aspect')->throwOnError();
                }
                $roles[$tagDef->Id] = $aspect->Name;
            }

            foreach($aspect->getSchema()->getMetaDefs() as $id => $metaDef)
            {
                if(array_key_exists($id, $roles)){
                    $aspectName = $roles[$id];
                    $this->getErrors()->reject('Aspect ['.$aspect->Name.'] meta definition [#'.$id.'] conflicts with the ['.$aspectName.'] aspect')->throwOnError();
                }
                $roles[$id] = $aspect->Name;
            }

            $elementMode = $aspect->ElementMode;
            $dto = $this->dao->findAll(new DTO(array('IncludesAspect'=> $aspect->Slug)));
            $hasElements = $dto->hasResults();

            if($elementMode == 'one' || $elementMode == 'anchored') {
                if($hasElements && $dto->getResult()->getSlug() != $obj->getSlug())
                    $this->getErrors()->reject('Aspect ['.$aspect->Name.'] cannot be used by more than one Element')->throwOnError();
//            } else if ($elementMode == 'anchored')
//            {
//                if($hasElements && $dto->getResult()->getSlug() != $obj->getSlug())
//                    $this->getErrors()->reject('Aspect ['.$aspect->Name.'] cannot be used by more than one Element')->throwOnError();
//                else if(!$obj->isAnchored())
//                    $this->getErrors()->reject('Element using Aspect ['.$aspect->Name.'] must be anchored')->throwOnError();
            }
        }

    }

}
<?php
/**
 * ElementService
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
 * @version     $Id: ElementService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ElementService
 *
 * @package     CrowdFusion
 */
class ElementService extends AbstractSystemService
{
    protected $AspectService;

    /**
     * [IoC] Creates the Element Service
     *
     * @param DateFactory        $DateFactory      The DateFactory
     * @param DAOInterface       $ElementDAO       The ElementDAO
     * @param ValidatorInterface $ElementValidator The ElementValidator
     */
    public function __construct(DateFactory $DateFactory, DAOInterface $ElementDAO, ValidatorInterface $ElementValidator, AbstractSystemService $AspectService)
    {
        parent::__construct($DateFactory, $ElementDAO, $ElementValidator);

        $this->AspectService = $AspectService;
//        $this->AspectService->findAll(new DTO());
//        $this->findAll(new DTO());
    }

    /**
     * Returns all elements that have the specified aspect
     *
     * @param string $aspectName The name of the desired aspect
     *
     * @return array An array of the results
     */
    public function findAllWithAspect($aspectName, $restrictSiteSlug = null)
    {
//        if (!$this->AspectService->getBySlug($aspectName))
//            throw new Exception('Cannot find Elements with Aspect ['.$aspectName.'], Aspect does not exist');

        $dto = new DTO();

        $dto->setParameter("IncludesAspect", ltrim($aspectName, '@'));
        if(!is_null($restrictSiteSlug))
            $dto->setParameter("AnchoredSiteSlug", $restrictSiteSlug);

        $dto = $this->findAll($dto);

        return $dto->getResults();
    }

    /**
     * Returns all elements for given aspect names and element slugs
     *
     * @param string $aspectOrElement Comma separated list of aspects names
     *                                and/or elements slugs
     *
     * @return array An array of Element keyed by the elements' ids
     */
    public function findAllFromString($aspectsOrElements)
    {
        $results = array();
        foreach (explode(',', $aspectsOrElements) as $aspectOrElement) {
            $aspectOrElement = trim($aspectOrElement);
            if (substr($aspectOrElement, 0, 1) == '@') {
                $els = $this->findAllWithAspect($aspectOrElement);
                foreach ($els as $el) {
                    $results[$el->ElementID] = $el;
                }
            } else {
                $el = $this->getBySlug($aspectOrElement);
                $results[$el->ElementID] = $el;
            }
        }

        return $results;
    }

    /**
     * Returns one Element with the given aspect
     *
     * @throws NodeException
     * @param  $aspect
     * @param  $restrictSiteSlug
     * @return mixed
     */
    public function oneFromAspect($aspect, $restrictSiteSlug = null)
    {

        $elements = $this->findAllWithAspect($aspect, $restrictSiteSlug);

        if(empty($elements))
            throw new NodeException('No elements have aspect ['.$aspect.']');

        if(count($elements) > 1)
            throw new NodeException('More than 1 element has aspect ['.$aspect.']');

        $element = current($elements);

        return $element;
    }


}

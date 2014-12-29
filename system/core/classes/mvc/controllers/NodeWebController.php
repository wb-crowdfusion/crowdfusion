<?php
/**
 * NodeWebController
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
 * @version     $Id: NodeWebController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeWebController
 *
 * @package     CrowdFusion
 */
class NodeWebController extends AbstractWebController
{

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }



    protected function single() {

        $dto = new NodeQuery();
        $dto->setLimit(1);

        if($this->getTemplateVariable('Site.all') == null)
        {
            if($this->getTemplateVariable('Sites.in') == null)
            {
                $site = $this->RequestContext->getSite();
                $dto->setParameter('Sites.in', $site->getSlug());
            }

            $this->passthruTemplateVariable($dto, 'Sites.in');
            //$this->passthruTemplateVariable($dto, 'SiteIDs.in');
        }

        $this->passthruTemplateVariable($dto, 'Elements.in');
//        $this->passthruTemplateVariable($dto, 'Slugs.in');

        $this->passthruTemplateVariable($dto, 'Meta.select');
        $this->passthruTemplateVariable($dto, 'OutTags.select');
        $this->passthruTemplateVariable($dto, 'InTags.select');
//        $this->passthruTemplateVariable($dto, 'Sections.select');

//        $this->passthruTemplateVariable($dto, 'Title.like');
//        $this->passthruTemplateVariable($dto, 'Title.ieq');
//        $this->passthruTemplateVariable($dto, 'Title.eq');
//        $this->passthruTemplateVariable($dto, 'Title.firstChar');
//
//        if(!$this->Permissions->checkPermission('cms-view')) {
//            $dto->setParameter('Status.isActive', true);
//        }

//        $this->passthruTemplateVariable($dto, 'ActiveDate.before');
//        $this->passthruTemplateVariable($dto, 'ActiveDate.after');
//        $this->passthruTemplateVariable($dto, 'ActiveDate.start');
//        $this->passthruTemplateVariable($dto, 'ActiveDate.end');
//
//        $this->passthruTemplateVariable($dto, 'CreationDate.before');
//        $this->passthruTemplateVariable($dto, 'CreationDate.after');
//        $this->passthruTemplateVariable($dto, 'CreationDate.start');
//        $this->passthruTemplateVariable($dto, 'CreationDate.end');
//
//        $this->passthruTemplateVariable($dto, 'OutTags.exist');
//        $this->passthruTemplateVariable($dto, 'InTags.exist');
//        $this->passthruTemplateVariable($dto, 'Meta.exist');
//        $this->passthruTemplateVariable($dto, 'Sections.exist');
//
//        foreach($this->templateVars as $name => $value)
//        {
//            if(strpos($name, '#') === 0)
//                $dto->setParameter($name, $value);
//        }

        $slug = $this->getTemplateVariable('Slugs.in');
        if($slug != null) {
            $dto->setParameter('Slugs.in', $slug);

            $dto = $this->NodeRefService->normalizeNodeQuery($dto);

            $nodeRefs = $dto->getParameter('NodeRefs.normalized');
            $nodePartials = $dto->getParameter('NodePartials.eq');
            $allFullyQualified = $dto->getParameter('NodeRefs.fullyQualified');


            if(!$allFullyQualified || count($nodeRefs) > 1) {
                $this->Logger->debug('node-single query is not fully-qualified NodeRef or more than 1 node is being returned');
                $dto->setResults(array());
            } else {

                $row = $this->RegulatedNodeService->getByNodeRef(current($nodeRefs), $nodePartials, ($this->getTemplateVariable('ForceReadWrite')!=null?StringUtils::strToBool($this->getTemplateVariable('ForceReadWrite')):false));

                $showPending = (StringUtils::strToBool($this->getTemplateVariable('Status.allowInactive')) || $this->Permissions->checkPermission('cms-view'));

                //Allow draft records to be viewed if exact URL is known.
                if(empty($row)) {
                    $this->Logger->debug('Row not found');
                    $dto->setResults(array());
                } else if ($row->Status == 'deleted') {
                    $this->Logger->debug('Node status is deleted');
                    $dto->setResults(array());
                } else if (!$row->isIsActive() && !$showPending) {
                    $this->Logger->debug('Record is not active (and user is not permitted to see pending status)');
                    $dto->setResults(array());
                } else {

                    $this->Logger->debug('Found node ['.$row->getNodeRef().']');

                    $this->Events->trigger('NodeWebController.single', $this->templateVars, $row);
                    foreach ((array)$row->getNodeRef()->getElement()->getAspects() as $aspect)
                        $this->Events->trigger('NodeWebController'.'.@'.$aspect->Slug.'.'.'single', $this->templateVars, $row);

                    $dto->setResults(array($row));
                }
            }

        } else {
            $dto->setResults(array());
        }

        return $this->readNodeQuery($dto);
    }

    protected function items() {

        $dto = new NodeQuery();
        $this->buildLimitOffset($dto);

        if($this->getTemplateVariable('Site.all') == null)
        {
            if($this->getTemplateVariable('Sites.in') == null)
            {
                $site = $this->RequestContext->getSite();
                $dto->setParameter('Sites.in', $site->getSlug());
            }

            $this->passthruTemplateVariable($dto, 'Sites.in');
            //$this->passthruTemplateVariable($dto, 'SiteIDs.in');
        }

        $this->passthruTemplateVariable($dto, 'NodeRefs.in');
        $this->passthruTemplateVariable($dto, 'Elements.in');
        $this->passthruTemplateVariable($dto, 'Slugs.in');

        $this->passthruTemplateVariable($dto, 'Meta.select');
        $this->passthruTemplateVariable($dto, 'OutTags.select');
        $this->passthruTemplateVariable($dto, 'InTags.select');
        //$this->passthruTemplateVariable($dto, 'Sections.select');
        $this->passthruTemplateVariable($dto, 'OrderByInTag');
        $this->passthruTemplateVariable($dto, 'OrderByOutTag');

        $this->passthruTemplateVariable($dto, 'Title.like');
        $this->passthruTemplateVariable($dto, 'Title.ieq');
        $this->passthruTemplateVariable($dto, 'Title.eq');
        $this->passthruTemplateVariable($dto, 'Title.firstChar');
		$this->passthruTemplateVariable($dto, 'Status.isActive');
        $this->passthruTemplateVariable($dto, 'Status.all');
        $this->passthruTemplateVariable($dto, 'Status.eq');

        if($this->getTemplateVariable('Status.isActive') === null && $this->getTemplateVariable('Status.all') === null && $this->getTemplateVariable('Status.eq') === null)
        {
	        $dto->setParameter('Status.isActive', true);
        }

        $this->passthruTemplateVariable($dto, 'ActiveDate.before');
        $this->passthruTemplateVariable($dto, 'ActiveDate.after');
        $this->passthruTemplateVariable($dto, 'ActiveDate.start');
        $this->passthruTemplateVariable($dto, 'ActiveDate.end');

        $this->passthruTemplateVariable($dto, 'CreationDate.before');
        $this->passthruTemplateVariable($dto, 'CreationDate.after');
        $this->passthruTemplateVariable($dto, 'CreationDate.start');
        $this->passthruTemplateVariable($dto, 'CreationDate.end');

        $this->passthruTemplateVariable($dto, 'OutTags.exist');
        $this->passthruTemplateVariable($dto, 'InTags.exist');
        $this->passthruTemplateVariable($dto, 'Meta.exist');
        //$this->passthruTemplateVariable($dto, 'Sections.exist');

        $this->passthruTemplateVariable($dto, 'TreeID.childOf');
        $this->passthruTemplateVariable($dto, 'TreeID.depth');

        foreach($this->templateVars as $name => $value)
        {
            if(strpos($name, '#') === 0)
                $dto->setParameter($name, $value);
        }


        $dto->setOrderBy($this->getTemplateVariable('OrderBy'));

        $this->Events->trigger('NodeWebController.items', $dto);

        $dto = $this->RegulatedNodeService->findAll($dto, ($this->getTemplateVariable('ForceReadWrite')!=null?StringUtils::strToBool($this->getTemplateVariable('ForceReadWrite')):false));
        return $this->readNodeQuery($dto);
    }

}

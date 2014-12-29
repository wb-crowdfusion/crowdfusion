<?php
/**
 * AbstractNodeSearchWebController
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
 * @version     $Id: AbstractNodeSearchWebController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractNodeSearchWebController
 *
 * @package     CrowdFusion
 */
abstract class AbstractNodeSearchWebController extends NodeWebController
{

    protected $Index;

    protected function go()
    {
        if(!isset($this->Index))
            throw new Exception('Missing property Index on node search controller');

        $dto = new NodeQuery();

        if($this->getTemplateVariable('Site.all') == null)
        {
            if($this->getTemplateVariable('Sites.in') == null)
            {
                $site = $this->RequestContext->getSite();
                $dto->setParameter('Sites.in', $site->getSlug());
            }

            $this->passthruTemplateVariable($dto, 'Sites.in');
//            $this->passthruTemplateVariable($dto, 'SiteIDs.in');
        }

        if($this->getTemplateVariable('Elements.in') == null)
            $dto->setParameter('Elements.in', $this->Index->getElements());

        $this->passthruTemplateVariable($dto, 'Elements.in');

        $dto->setParameter('Status.isActive', true);

        $this->passthruTemplateVariable($dto, 'SearchKeywords');
        $this->passthruTemplateVariable($dto, 'SearchThreshold');

        $this->passthruTemplateVariable($dto, 'Meta.select');
        $this->passthruTemplateVariable($dto, 'OutTags.select');
        $this->passthruTemplateVariable($dto, 'InTags.select');

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

        return $this->readNodeQuery($this->Index->search($dto));
    }

}
<?php
/**
 * Node Database convenience methods.
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009-2011 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted.
 *
 * @package     CrowdFusion
 * @copyright   2009-2011 Crowd Fusion Inc.
 * @license     http://www.crowdfusion.com/licenses/enterprise CF Enterprise License
 * @version     $Id: NodeApiController.php 706 2011-10-05 15:42:32Z clayhinson $
 */

/**
 * Node Database convenience methods.
 *
 * @package     CrowdFusion
 */
class NodeApiController extends AbstractApiController
{
    /**
     * @var RegulatedNodeService
     */
    protected $RegulatedNodeService;
    protected $SiteService;
    protected $ElementService;
    protected $NodeMapper;
    protected $NodeBinder;
    protected $Events;

    public function setNodeBinder(NodeBinder $NodeBinder)
    {
        $this->NodeBinder = $NodeBinder;
    }

    public function setNodeMapper(NodeMapper $NodeMapper)
    {
        $this->NodeMapper = $NodeMapper;
    }

    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function setSiteService(SiteService $SiteService)
    {
        $this->SiteService = $SiteService;
    }

    public function setRegulatedNodeService(RegulatedNodeService $RegulatedNodeService)
    {
        $this->RegulatedNodeService = $RegulatedNodeService;
    }

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }


    public function get()
    {
        $data = array();

        $noderef = $this->getNodeRef();

        $nodePartials = new NodePartials(
            $this->Request->getParameter('Meta_select'),
            $this->Request->getParameter('OutTags_select'),
            $this->Request->getParameter('InTags_select'));

        $node = $this->RegulatedNodeService->getByNodeRef($noderef,$nodePartials);

        $data[] = $node;

        $this->bindToActionDatasource($data);
        return new View($this->successView());
    }

    public function exists()
    {
        $data = array();

        $noderef = $this->getNodeRef();

        $exists = $this->RegulatedNodeService->refExists($noderef);

        $data[] = array(
            'SiteSlug'      => $noderef->getSite()->getSlug(),
            'ElementSlug'   => $noderef->getElement()->getSlug(),
            'NodeSlug'      => $noderef->getSlug(),
            'Exists'        => $exists);

        $this->bindToActionDatasource($data);
        return new View($this->successView());
    }

    public function incrementMeta()
    {
        $data = array();

        $metaId = $this->Request->getParameter('MetaID');

        $noderef = $this->getNodeRef();

        $this->checkNonce();

        $this->RegulatedNodeService->incrementMeta($noderef,$metaId);

        $data[] = array(
            'SiteSlug'      => $noderef->getSite()->getSlug(),
            'ElementSlug'   => $noderef->getElement()->getSlug(),
            'NodeSlug'      => $noderef->getSlug(),
            'MetaID'        => $metaId,
            'Success'       => true);

        $this->bindToActionDatasource($data);
        return new View($this->successView());
    }

    public function decrementMeta()
    {
        $this->checkNonce();

        $data = array();

        $metaId = $this->Request->getParameter('MetaID');

        $noderef = $this->getNodeRef();


        $this->RegulatedNodeService->decrementMeta($noderef,$metaId);

        $data[] = array(
            'SiteSlug'      => $noderef->getSite()->getSlug(),
            'ElementSlug'   => $noderef->getElement()->getSlug(),
            'NodeSlug'      => $noderef->getSlug(),
            'MetaID'        => $metaId,
            'Success'       => true);

        $this->bindToActionDatasource($data);
        return new View($this->successView());
    }

    public function updateMeta()
    {
        $this->checkNonce();

        $data = array();

        $metaId = $this->Request->getParameter('MetaID');
        $value = $this->Request->getParameter('Value');

        if($value == '')
            $value = null;


        //TODO: consider using TypeConverter::convertFromString(); but will need schema & MetaDef for this
        if(strtolower($value) === 'true')
            $value = true;
        if(strtolower($value) === 'false')
            $value = false;


        $noderef = $this->getNodeRef();


        $this->RegulatedNodeService->updateMeta($noderef,$metaId,$value);

        $data[] = array(
            'SiteSlug'      => $noderef->getSite()->getSlug(),
            'ElementSlug'   => $noderef->getElement()->getSlug(),
            'NodeSlug'      => $noderef->getSlug(),
            'MetaID'        => $metaId,
            'Success'       => true);

        $this->bindToActionDatasource($data);
        return new View($this->successView());
    }

    public function updateTags()
    {
        $this->checkNonce();

        $tagResponse = array();
        $tags = $this->Request->getParameter('tags');
        $role = '#'.ltrim($this->Request->getParameter('tagRole'),'#');

        $noderef = $this->getNodeRef();
        $direction = $this->Request->getParameter('TagDirection');
        $partial = new TagPartial($role);

        $node = $this->RegulatedNodeService->getByNodeRef(
            $noderef,
            new NodePartials(
                '',
                $direction == 'out' ? $partial : '',
                $direction == 'in' ? $partial : ''
            )
        );

        // get rid of the old tags
        $oldTags = $direction == 'out' ? $node->getOutTags($partial->TagRole) : $node->getInTags($partial->TagRole);
        foreach ($oldTags as $o) {
            $this->RegulatedNodeService->removeTag($noderef,$o);
        }

        // replace the tags with the newly reordered ones
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $newTag = new Tag(
                    $tag['TagElement'],
                    $tag['TagSlug'],
                    $tag['TagRole'],
                    $tag['TagValue'],
                    $tag['TagValueDisplay']
                );
                $newTag->TagSortOrder = $tag['TagSortOrder'];

                $this->RegulatedNodeService->addTag($noderef,$newTag);

                $tagnode = $this->RegulatedNodeService->getByNodeRef(
                    new NodeRef(
                        $this->ElementService->getBySlug($newTag->TagElement),
                        $newTag->TagSlug
                    ),
                    new NodePartials()
                );

                $tagResponse[] = array(
                    'TagDirection' => $newTag->TagDirection,
                    'TagElement' => $newTag->TagElement,
                    'TagSlug' => $newTag->TagSlug,
                    'TagRole' => $newTag->TagRole,
                    'TagRoleDisplay' => $newTag->TagRoleDisplay,
                    'TagValue' => $newTag->TagValue,
                    'TagValueDisplay' => $newTag->TagValueDisplay,
                    'TagLinkNode' => $tagnode,
                    'TagSortOrder' => $newTag->TagSortOrder
                );
            }
        }

        $data[] = array(
            'SiteSlug'      => $noderef->getSite()->getSlug(),
            'ElementSlug'   => $noderef->getElement()->getSlug(),
            'NodeSlug'      => $noderef->getSlug(),
            'Tags'          => $tagResponse,
            'Success'       => true
        );

        $this->bindToActionDatasource($data);
        return new View($this->successView());
    }

    public function addTag()
    {
        $this->checkNonce();

        $data = array();

        $tag = $this->getTag();
        $tag->TagSortOrder = $this->Request->getParameter('TagSortOrder');
        $noderef = $this->getNodeRef();


        $this->RegulatedNodeService->addTag($noderef,$tag);

        $tagnode = $this->RegulatedNodeService->getByNodeRef(
            new NodeRef(
                $this->ElementService->getBySlug($tag->TagElement),
                $tag->TagSlug
            ),
            new NodePartials());

        $data[] = array(
            'SiteSlug'      => $noderef->getSite()->getSlug(),
            'ElementSlug'   => $noderef->getElement()->getSlug(),
            'NodeSlug'      => $noderef->getSlug(),
            'Tag'           => array(
                'TagDirection' => $tag->TagDirection,
                'TagElement' => $tag->TagElement,
                'TagSlug' => $tag->TagSlug,
                'TagRole' => $tag->TagRole,
                'TagRoleDisplay' => $tag->TagRoleDisplay,
                'TagValue' => $tag->TagValue,
                'TagValueDisplay' => $tag->TagValueDisplay,
                'TagLinkNode' => $tagnode,
                'TagSortOrder' => $tag->TagSortOrder
            ),
            'Success'       => true);

        $this->bindToActionDatasource($data);
        return new View($this->successView());
    }

    public function removeTag()
    {

        $this->checkNonce();

        $data = array();

        $tag = $this->getTag();
        $noderef = $this->getNodeRef();


        $this->RegulatedNodeService->removeTag($noderef,$tag);

        $data[] = array(
            'SiteSlug'      => $noderef->getSite()->getSlug(),
            'ElementSlug'   => $noderef->getElement()->getSlug(),
            'NodeSlug'      => $noderef->getSlug(),
            'Tag'           => strval($tag),
            'Success'       => true);

        $this->bindToActionDatasource($data);
        return new View($this->successView());
    }

    public function getTags()
    {
        $data = array();

        $noderef = $this->getNodeRef();
        $direction = $this->Request->getParameter('TagDirection');
        $partial = new TagPartial($this->Request->getParameter('TagPartial'));

        $node = $this->RegulatedNodeService->getByNodeRef(
            $noderef,
            new NodePartials(
                '',
                $direction == 'out' ? $partial : '',
                $direction == 'in' ? $partial : ''
            )
        );

        $tags = $direction == 'out' ? $node->getOutTags($partial->TagRole) : $node->getInTags($partial->TagRole);

        foreach($tags as $tag) {
            $data[] = array(
                'TagDirection' => $tag->TagDirection,
                'TagElement' => $tag->TagElement,
                'TagSlug' => $tag->TagSlug,
                'TagRole' => $tag->TagRole,
                'TagRoleDisplay' => $tag->TagRoleDisplay,
                'TagValue' => $tag->TagValue,
                'TagValueDisplay' => $tag->TagValueDisplay,
                'TagLinkTitle' => $tag->TagLinkTitle,
                'TagLinkStatus' => $tag->TagLinkStatus,
                'TagLinkURL' => $tag->TagLinkURL,
                'TagLinkIsActive' => $tag->TagLinkIsActive,
                //'TagLinkNode' => $tagnode,
            );
        }

        $this->bindToActionDatasource(array(array('Tags'=>$data)));
        return new View($this->successView());
    }

    public function delete()
    {
        $this->checkNonce();

        $data = array();

        $noderef = $this->getNodeRef();
        $mergenoderef = null;

        $mergenodeslug = $this->Request->getParameter('MergeNodeSlug');
        if(!empty($mergenodeslug))
            $mergenoderef = new NodeRef($noderef->getElement(),$mergenodeslug);


        $this->RegulatedNodeService->delete($noderef,$mergenoderef);

        $data[] = array(
            'SiteSlug'          => $noderef->getSite()->getSlug(),
            'ElementSlug'       => $noderef->getElement()->getSlug(),
            'NodeSlug'          => $noderef->getSlug(),
            'Success'           => true);

        if($mergenoderef != null)
            $data[0]['MergeNodeSlug'] = $mergenodeslug;

        $this->bindToActionDatasource($data);
        return new View($this->successView());
    }

    public function undelete()
    {
        $this->checkNonce();

        $data = array();

        $noderef = $this->getNodeRef();


        $this->RegulatedNodeService->undelete($noderef);

        $data[] = array(
            'SiteSlug'      => $noderef->getSite()->getSlug(),
            'ElementSlug'   => $noderef->getElement()->getSlug(),
            'NodeSlug'      => $noderef->getSlug(),
            'Success'       => true);

        $this->bindToActionDatasource($data);
        return new View($this->successView());
    }

    public function add()
    {
        $this->checkNonce();

        $noderef = $this->getNodeRef();

        $node = $noderef->generateNode();

        $this->NodeMapper->defaultsOnNode($node);

        $this->NodeBinder->bindPersistentFields($node, $this->getErrors(), $this->params, $this->rawParams);
        $this->NodeBinder->fireAddBindEvents($node, $this->getErrors(), $this->params, $this->rawParams);

        $this->getErrors()->throwOnError();


        $this->RegulatedNodeService->add($node);

        //$this->NodeMapper->populateNodeCheaters($node);
        $this->bindToActionDatasource(array($node));

        return new View($this->successView());
    }

    public function quickAdd()
    {

        $this->checkNonce();

        if(empty($this->params['Title']))
            throw new Exception('Title parameter is required',210);

        if(empty($this->params['ElementSlug']))
            throw new Exception('ElementSlug parameter is required',220);

        $title = $this->params['Title'];
        $slug = SlugUtils::createSlug($title);

        $noderef = new NodeRef(
            $this->ElementService->getBySlug($this->Request->getParameter('ElementSlug')),
            $slug
        );


        // create node
        $node = $noderef->generateNode();
        $this->NodeMapper->defaultsOnNode($node);
        $node->Title = $title;
        $node->Slug = $slug;

        $this->getErrors()->throwOnError();

        $node = $this->RegulatedNodeService->quickAdd($node);

        $node = $this->RegulatedNodeService->getByNodeRef($node->getNodeRef(),new NodePartials());

        $this->bindToActionDatasource(array($node));
        return new View($this->successView());

    }

    public function replace()
    {

        $this->checkNonce();

        if(empty($this->params['Title']))
            throw new Exception('Title parameter is required',210);

        if(empty($this->params['ElementSlug']))
            throw new Exception('ElementSlug parameter is required',220);

        $title = $this->params['Title'];
        $slug = SlugUtils::createSlug($title);

        $noderef = new NodeRef(
            $this->ElementService->getBySlug($this->Request->getParameter('ElementSlug')),
            $slug
        );


        // create node
        $node = $noderef->generateNode();
        $this->NodeMapper->defaultsOnNode($node);
        $node->Title = $title;
        $node->Slug = $slug;

        $this->getErrors()->throwOnError();


        // edit existing record
        if($this->RegulatedNodeService->refExists($noderef))
        {
            $existing = $this->RegulatedNodeService->getByNodeRef($noderef);
            $existing->setNodePartials($node->getNodePartials());
            $existing->setMetas($node->getMetas());
            $existing->setOutTags($node->getOutTags());

            $node = $this->RegulatedNodeService->edit($node);

        //create new record
        } else {

            $node = $this->RegulatedNodeService->quickAdd($node);
        }

        $node = $this->RegulatedNodeService->getByNodeRef($node->getNodeRef(),new NodePartials());

        $this->bindToActionDatasource(array($node));
        return new View($this->successView());
    }

    public function edit()
    {

        $this->checkNonce();

        $noderef = $this->getNodeRef();

        $node = $this->RegulatedNodeService->getByNodeRef($noderef);

        $this->NodeBinder->bindPersistentFields($node, $this->getErrors(), $this->params, $this->rawParams);
        $this->NodeBinder->fireEditBindEvents($node, $this->getErrors(), $this->params, $this->rawParams);

        $this->getErrors()->throwOnError();


        $this->RegulatedNodeService->edit($node);

        $this->bindToActionDatasource(array($node));
        return new View($this->successView());

    }

    public function findAll()
    {
        $dto = new NodeQuery();
        $this->buildLimitOffset($dto);
        $this->buildSorts($dto);
        $this->buildFilters($dto);

        $this->passthruParameter($dto, 'Elements.in');
        $this->passthruParameter($dto, 'Sites.in');
        //$this->passthruParameter($dto, 'SiteIDs.in');
        $this->passthruParameter($dto, 'Slugs.in');

        $this->passthruParameter($dto, 'Meta.select');
        $this->passthruParameter($dto, 'OutTags.select');
        $this->passthruParameter($dto, 'InTags.select');
        $this->passthruParameter($dto, 'Sections.select');
        $this->passthruParameter($dto, 'OrderByInTag');
        $this->passthruParameter($dto, 'OrderByOutTag');

        $this->passthruParameter($dto, 'Title.like');
        $this->passthruParameter($dto, 'Title.ieq');
        $this->passthruParameter($dto, 'Title.eq');
        $this->passthruParameter($dto, 'Title.firstChar');
        $this->passthruParameter($dto, 'Status.isActive');
        $this->passthruParameter($dto, 'Status.all');
        $this->passthruParameter($dto, 'Status.eq');
        $this->passthruParameter($dto, 'TreeID.childOf');
        $this->passthruParameter($dto, 'TreeID.eq');

        $this->passthruParameter($dto, 'ActiveDate.before');
        $this->passthruParameter($dto, 'ActiveDate.after');
        $this->passthruParameter($dto, 'ActiveDate.start');
        $this->passthruParameter($dto, 'ActiveDate.end');

        $this->passthruParameter($dto, 'CreationDate.before');
        $this->passthruParameter($dto, 'CreationDate.after');
        $this->passthruParameter($dto, 'CreationDate.start');
        $this->passthruParameter($dto, 'CreationDate.end');

        $this->passthruParameter($dto, 'OutTags.exist');
        $this->passthruParameter($dto, 'InTags.exist');
        $this->passthruParameter($dto, 'Meta.exist');
        $this->passthruParameter($dto, 'Sections.exist');

        foreach($this->Request->getParameters() as $name => $value)
        {
            if(strpos($name, '#') === 0)
                $dto->setParameter(str_replace('_','.', $name), $value);
        }

        $dto->isRetrieveTotalRecords(true);

        if($this->Request->getParameter('OrderBy') != null) {
            $dto->setOrderBys(array());
            $dto->setOrderBy($this->Request->getParameter('OrderBy'));
        }

        $this->Events->trigger('NodeApiController.findAll', $dto);

        $dto = $this->RegulatedNodeService->findAll($dto,
                ($this->Request->getParameter('ForceReadWrite')!=null?StringUtils::strToBool($this->Request->getParameter('ForceReadWrite')):false) );

        echo $this->readDTO($dto);
        return null;
    }

}
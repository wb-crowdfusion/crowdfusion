<?php
/**
 * AspectDAO
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
 * @version     $Id: AspectDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AspectDAO
 *
 * @package     CrowdFusion
 */
class AspectDAO extends AbstractSystemXMLDAO
{

    protected $NodeSchemaParser;

    /**
     * [IoC] Injects the NodeSchemaParser
     *
     * @param NodeSchemaParser $NodeSchemaParser NodeSchemaParser
     */
    public function __construct(NodeSchemaParser $NodeSchemaParser, PluginService $PluginService)
    {
        parent::__construct(new Aspect());
        $this->NodeSchemaParser = $NodeSchemaParser;
        $this->PluginService = $PluginService;
    }

    /**
     * Returns the {@link $obj} with the parsed NodeSchema attached
     *
     * @param ModelObject $obj The object to translate
     *
     * @return ModelObject {@link $obj} with the 'Schema' field set.
     */
    public function preCacheTranslateObject(ModelObject $obj)
    {
        $plugin = $this->PluginService->getByID($obj->PluginID);
        if(empty($plugin) || !$plugin->isInstalled() || !$plugin->isEnabled())
            return null;

        if($obj->getXMLSchema() != '') {
            // resolve schema
            $schemaXML = "<?xml version='1.0'?><schema>";
            $schemaXML .= preg_replace('/\<\?xml([^\>\/]*)\>/', '' , $obj->getXMLSchema());
            $schemaXML .= "</schema>";

            try {
                $schema = $this->NodeSchemaParser->parse($schemaXML);
            }catch(Exception $e){
                throw new SchemaException("Unable to parse schema for aspect [{$obj->Slug}]:\n ". $e->getMessage());
            }

            $obj->setSchema($schema);
        } else {
            $obj->setSchema(new NodeSchema());
        }


        return $obj;
    }

    public function findAll(DTO $dto)
    {
        if($dto->getOrderBys() == array())
            $dto->setOrderBy('Name', 'ASC');

        return parent::findAll($dto);

    }

}

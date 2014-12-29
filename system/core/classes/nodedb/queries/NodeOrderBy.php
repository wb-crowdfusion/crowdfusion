<?php
/**
 * NodeOrderBy
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
 * @version     $Id: NodeOrderBy.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeOrderBy - used in the construction of SQL for node db querying
 *
 * @package     CrowdFusion
 */
class NodeOrderBy
{
    protected $column;
    protected $direction = false;
    protected $orderedValues = false;
    protected $orderByMetaPartial = null;
    protected $metaDataType = null;

    public function __construct($column, $direction, $orderByMeta = null, $datatype = null)
    {
        $this->column = $column;
        if(is_array($direction))
        {
            $this->orderedValues = $direction;
        } else {
            $this->direction = $direction;
        }

        $this->orderByMetaPartial = $orderByMeta;
        $this->metaDataType = $datatype;
    }

    public function isOrderedValues()
    {
        return $this->orderedValues != false;
    }

    public function isDirectional()
    {
        return $this->direction != false;
    }

    public function isMeta()
    {
        return !empty($this->orderByMetaPartial);
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function getDirection()
    {
        return $this->direction;
    }

    public function getOrderedValues()
    {
        return $this->orderedValues;
    }

    public function getOrderByMetaPartial()
    {
        return $this->orderByMetaPartial;
    }

    public function getOrderByMetaDataType()
    {
        return $this->metaDataType;
    }

}
<?php

class NodesHelper
{

    // You may ask yourself, well,
    // Why are you assigning constants to variables?
    // Well, read this thread: http://www.mail-archive.com/php-bugs@lists.php.net/msg126978.html
    protected $sortAsc  = SORT_ASC;
    protected $sortDesc = SORT_DESC;

    protected $sortReg = SORT_REGULAR;
    protected $sortStr = SORT_STRING;
    protected $sortInt = SORT_NUMERIC;

    protected $now = null;

    protected $Logger;

    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

    protected $DateFactory;

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    protected $TagsHelper;

    public function setTagsHelper(TagsHelper $TagsHelper)
    {
        $this->TagsHelper = $TagsHelper;
    }


    public function createNodePartialsFromExistsClauses(NodeQuery $nodeQuery)
    {
        $nodePartials = new NodePartials();

        foreach($nodeQuery->getParameters() as $key => $value) {
            switch($key) {
                case 'Meta.exist':
                    $nodePartials->increaseMetaPartials($value);
                    break;

                case 'OutTags.exist':
                    $nodePartials->increaseOutPartials($value);
                    break;

                case 'InTags.exist':
                    $nodePartials->increaseInPartials($value);
                    break;

//                    case 'Sections.exist':
//                        $nodePartials->increaseSectionPartials($value);
//                        break;
            }
        }
        $metaParams = $this->getMetaFilters($nodeQuery);
        foreach($metaParams as $mArgs)
        {
            $nodePartials->increaseMetaPartials($mArgs[0]);
        }

        return $nodePartials;
    }


    public function filterNodes($nodes, NodeQuery $nodeQuery )
    {

        $filterParams = $this->getNodeFilters($nodeQuery);
        $metaParams = $this->getMetaFilters($nodeQuery);

        if(empty($filterParams) && empty($metaParams))
            return $nodes;

        foreach ($nodes as $k => &$node) {
            if (!$this->filterNode($node, $filterParams, $metaParams)) {
                unset($nodes[$k]);
                continue;
            }
        }

        return $nodes;

    }


    public function getMetaFilters(NodeQuery $nodeQuery)
    {
        $parameters = $nodeQuery->getParameters();

        $metaParams = array();
        foreach($parameters as $key => $value)
        {
            if(($hash = strpos($key, '#')) === 0)
            {
                $dot = strpos($key, '.');
                if($dot !== false)
                {
                    $fullId = substr($key, 0, $dot);
                    $metaName = substr($key, $hash+1, $dot-1);
                    $operator = substr($key, $dot+1);

                    $metaParams[] = array($fullId, $metaName, $operator, $value);
                }

            }
        }

        return $metaParams;

    }


    public function getNodeFilters( NodeQuery $nodeQuery)
    {
        $parameters = $nodeQuery->getParameters();

        $filterParams = array();
        foreach($parameters as $key => $value) {
            switch($key) {
                case 'Title.firstChar':
                    $filterParams[$key] = strtolower(substr($value, 0, 1));
                    break;

                case 'ActiveDate.after':
                case 'CreationDate.after':
                    $filterParams[$key] = $this->DateFactory->newLocalDate($value);
                    break;

                case 'ActiveDate.before':
                case 'CreationDate.before':
                    $filterParams[$key] = $this->DateFactory->newLocalDate($value);
                    break;

                case 'ActiveDate.start':
                case 'CreationDate.start':
                    $value = $this->DateFactory->newLocalDate($value);
                    $value->setTime(0, 0, 0);
                    $filterParams[$key] = $value;
                    break;

                case 'ActiveDate.end':
                case 'CreationDate.end':
                    $value = $this->DateFactory->newLocalDate($value);
                    $value->setTime(23, 59, 59);
                    $filterParams[$key] = $value;
                    break;

                case 'Meta.exist':
                    $filterParams[$key] = PartialUtils::unserializeMetaPartials($value);
                    break;

                case 'OutTags.exist':
                case 'InTags.exist':
                    $filterParams[$key] = PartialUtils::unserializeOutPartials($value);
                    break;

                case 'Title.eq':
                case 'Title.ieq':
                case 'Title.like':

                case 'TreeID.childOf':
                case 'TreeID.eq':
                case 'TreeID.depth':
                case 'Status.eq':
                case 'Status.isActive':
                case 'Status.all':
                    $filterParams[$key] = $value;
                    break;
            }
        }

        if(!array_key_exists('Status.eq', $filterParams) &&
            !array_key_exists('Status.isActive', $filterParams) &&
            !array_key_exists('Status.all', $filterParams))
        {
            $filterParams['Status.all'] = false;
        }

        return $filterParams;
    }

    /**
     * Returns false if the node fails to match the parameters
     *
     * @throws NodeException
     * @param  $node
     * @param  $filterParams
     * @param  $metaParams
     * @return bool
     */
    protected function filterNode($node, $filterParams, $metaParams)
    {

        if(!empty($filterParams))
        {

            if(is_null($this->now))
                $this->now = $this->DateFactory->newStorageDate();

            foreach ($filterParams as $key => $value) {
                if (strpos($key, '#') === 0)
                    continue;

                switch($key) {

                    case 'Title.firstChar':
                        $firstChar = strtolower(substr($node['Title'], 0, 1));
                        if($value == '#')
                        {
                            $ord = ord($firstChar);
                            if($ord >= 97 && $ord <= 122)
                                return false;
                        } else if (strcmp($firstChar, $value) !== 0) {
                            return false;
                        }
                        break;

                    case 'Title.eq':
                        if(strcmp($value, $node['Title']) !== 0)
                            return false;
                        break;

                    case 'Title.ieq':
                        if(strcasecmp($value, $node['Title']) !== 0)
                            return false;
                        break;

                    case 'Title.like':
                        if(is_null($value))
                            break;
                        if(stripos($node['Title'],$value) === FALSE)
                            return false;
                        break;

                    case 'TreeID.childOf':
                        if(stripos($node['TreeID'],$value) !== 0)
                            return false;
                        break;

                    case 'TreeID.eq':
                        if(strcmp($value, $node['TreeID']) !== 0)
                            return false;
                        break;

                    case 'TreeID.depth':
                        if((strlen($node['TreeID'])/4) != $value)
                            return false;
                        break;

                    case 'ActiveDate.after':
                        if($node['ActiveDate']->toUnix() < $value->toUnix())
                            return false;
                        break;

                    case 'ActiveDate.before':
                        if($node['ActiveDate']->toUnix() >= $value->toUnix())
                            return false;
                        break;

                    case 'ActiveDate.start':
                        if($node['ActiveDate']->toUnix() <= $value->toUnix())
                            return false;
                        break;

                    case 'ActiveDate.end':
                        if($node['ActiveDate']->toUnix() >= $value->toUnix())
                            return false;
                        break;

                    case 'CreationDate.after':
                        if($node['CreationDate']->toUnix() < $value->toUnix())
                            return false;
                        break;

                    case 'CreationDate.before':
                        if($node['CreationDate']->toUnix() >= $value->toUnix())
                            return false;
                        break;

                    case 'CreationDate.start':
                        if($node['CreationDate']->toUnix() <= $value->toUnix())
                            return false;
                        break;

                    case 'CreationDate.end':
                        if($node['CreationDate']->toUnix() >= $value->toUnix())
                            return false;
                        break;

                    case 'Status.eq':
                        switch(strtolower($value)) {
                            case 'published':
                                if(strtolower($node['Status']) != 'published')
                                    return false;
                                break;
                            case 'draft':
                                if(strtolower($node['Status']) != 'draft')
                                    return false;
                                break;
                            case 'deleted':
                                if(strtolower($node['Status']) != 'deleted')
                                    return false;
                                break;
                            default:
                                if(strtolower($node['Status']) == 'deleted')
                                    return false;
                                break;
                        }
                        break;

                    case 'Status.isActive':
                        if(StringUtils::strToBool($value) == true && (strcmp('published', $node['Status']) !== 0 || $node['ActiveDate']->toUnix() >= $this->now->toUnix()))
                            return false;
                        break;

                    case 'Status.all':
                        if(StringUtils::strToBool($value) == false && strcmp('deleted', $node['Status']) === 0)
                            return false;
                        break;

    //                case 'IncludesMeta':
    //                    $found = false;
    //                    foreach($value as $partial) {
    //                        $meta = MetaUtils::filterMeta($node['Metas'],$partial->getMetaName());
    //                        if($partial->match($meta))
    //                            $found = true;
    //                    }
    //                    if(!$found)
    //                        return false;
    //                    break;

                    case 'Meta.exist':
                        foreach($value as $partial) {
                            $meta = $node->getMeta($partial->getMetaName());
                            if(empty($meta) || !$partial->match($meta)) {
                                return false;
                            }

                        }
                        break;

    //                case 'IncludesOutTags':
    //                    $found = false;
    //                    foreach($value as $partial) {
    //                        foreach($node['OutTags'] as $tag)
    //                        {
    //                            if($this->TagsHelper->matchPartial($partial, $tag)) {
    //                                $found = true;
    //                                break 2;
    //                            }
    //                        }
    //                    }
    //                    if(!$found)
    //                        return false;
    //
    //                    break;

                    case 'OutTags.exist':
                        $found = 0;
                        foreach($value as $partial) {
                            foreach($node['OutTags'] as $tag)
                            {
                                if($this->TagsHelper->matchPartial($partial, $tag)) {
                                    ++$found;
                                    break;
                                }
                            }
                        }
                        if($found != count($value))
                            return false;

                        break;

    //                case 'IncludesInTags':
    //                    $found = false;
    //                    foreach($value as $partial) {
    //                        foreach($node['InTags'] as $tag)
    //                        {
    //                            if($this->TagsHelper->matchPartial($partial, $tag)) {
    //                                $found = true;
    //                                break 2;
    //                            }
    //                        }
    //                    }
    //                    if(!$found)
    //                        return false;
    //
    //                    break;

                    case 'InTags.exist':
                        $found = 0;
                        foreach($value as $partial) {
                            foreach($node['InTags'] as $tag)
                            {
                                if($this->TagsHelper->matchPartial($partial, $tag)) {
                                    ++$found;
                                    break;
                                }
                            }
                        }
                        if($found != count($value))
                            return false;

                        break;

                }
            }
        }

        if (!empty($metaParams)) {
            $schema = $node['NodeRef']->getElement()->getSchema();
            foreach ($metaParams as $mArgs) {
                list($full, $name, $operator, $compareValue) = $mArgs;
                $def = $schema->getMetaDef($name);
                $datatype = $def->Datatype;

                if (is_object($node)) {
                    $realValue = $node->getMetaValue($name);
                } else {
                    $meta = MetaUtils::filterMeta($node['Metas'],$name);
                    $realValue = $meta->getMetaValue();
                }

                if ($datatype == 'flag') {
                    throw new NodeException('Unable to run meta clause on flag datatype');
                }

                switch($operator) {
                    case 'eq':
                        if (in_array($datatype, array('text', 'varchar')))
                            if (strcmp($compareValue, $realValue) !== 0)
                                return false;
                        else
                            if ($compareValue !== $realValue)
                                return false;
                        break;

                    case 'ieq':
                        if (strcasecmp($compareValue, $realValue) !== 0)
                            return false;
                        break;

                    case 'like':
                        if(is_null($compareValue))
                            break;

                        if (stripos($compareValue, $realValue) === FALSE)
                            return false;
                        break;

                    case 'before':
                        $d = $this->DateFactory->newLocalDate($value);
                        if ($realValue->toUnix() <= $d->toUnix())
                            return false;
                        break;

                    case 'after':
                        $d = $this->DateFactory->newLocalDate($compareValue);
                        if ($realValue->toUnix() > $d->toUnix())
                            return false;
                        break;

                    case 'start':
                        $d = $this->DateFactory->newLocalDate($compareValue);
                        $d->setTime(0, 0, 0);
                        if ($realValue->toUnix() >= $d->toUnix())
                            return false;
                        break;

                    case 'end':
                        $d = $this->DateFactory->newLocalDate($compareValue);
                        $d->setTime(23, 59, 59);
                        if ($realValue->toUnix() <= $d->toUnix())
                            return false;
                        break;

                    case 'notEq':
                        if (in_array($datatype, array('text', 'varchar')))
                            if (strcmp($compareValue, $realValue) === 0)
                                return false;
                        else
                            if ($compareValue === $realValue)
                                return false;
                        break;

                    case 'lessThan':
                        if ($compareValue >= $realValue)
                            return false;
                        break;

                    case 'lessThanEq':
                        if ($compareValue > $realValue)
                            return false;
                        break;

                    case 'greaterThan':
                        if ($compareValue <= $realValue)
                            return false;
                        break;

                    case 'greaterThanEq':
                        if ($compareValue < $realValue)
                            return false;
                        break;

                    /*
                     * case insensitive comparison for #meta.in filtering.
                     * works exactly like the SQL SomeValue IN ('item1', 'item2')
                     */
                    case 'in':
                        $inValues = explode(',', $compareValue);
                        $foundValue = false;

                        if (in_array($datatype, array('text', 'varchar'))) {
                            foreach ($inValues as $val) {
                                if (strcasecmp($val, $realValue) === 0) {
                                    $foundValue = true;
                                    break;
                                }
                            }
                        } else {
                            foreach ($inValues as $val) {
                                if ($val === $realValue) {
                                    $foundValue = true;
                                    break;
                                }
                            }
                        }

                        if (!$foundValue) {
                            return false;
                        }

                        break;
                }
            }
        }

        return true;
    }


    public function sortNodes($nodes, $orderObjects, $useMetaDirectly = false)
    {

        if(!empty($nodes) && count($orderObjects) > 0)
        {
            // reorder according to a key
            $firstOrderObject = current($orderObjects);
            if($firstOrderObject->isOrderedValues())
            {
                // order using key'd values
                $tempResults = array();
                $column = $firstOrderObject->getColumn();

                // if sorting by NodeRefs, delay for later
//                if($column == 'NodeRef')
//                    return $nodes;

                foreach($firstOrderObject->getOrderedValues() as $k => $kval){
                    foreach($nodes as $rrow)
                    {
                        if($rrow[$column] == $kval)
                            $tempResults[] = $rrow;
                    }
                }

                $nodes =& $tempResults;
            } else {

                $multiSortArgs = array();

                $sortArrays = array();

                $id_sort_array = array();

                $i = 0;
                foreach ($orderObjects as $orderObject) {
                    $pad    = false;
                    $date = false;
                    $column = $orderObject->getColumn();

                    if ($column == 'ActiveDate' || $orderObject->getOrderByMetaDataType() == 'date') {
                        //$column .= 'Unix';
                        $date = true;
                    } else if ($column == 'TreeID')
                        $pad = true;

                    $sort_array = array();
                    foreach ($nodes as $row) {
                        if ($pad)
                            $value = str_pad($row[$column], 254, '0', STR_PAD_RIGHT);
                        else if($orderObject->isMeta() && $useMetaDirectly)
                            $value = $row->getMetaValue($orderObject->getOrderByMetaPartial());
                        else if($date)
                            $value = $this->DateFactory->newStorageDate($row[$column])->toUnix();
                        else
                            $value  = $row[$column];

                        $sort_array[] = strtolower($value);

                        if($i == 0)
                            $id_sort_array[] = $row['ID'];
                    }
                    array_unshift($sortArrays, $sort_array);
                    $multiSortArgs[$i] =& $sortArrays[0];

                    if (strtolower($orderObject->getDirection()) == 'asc')
                        $multiSortArgs[++$i] =& $this->sortAsc;
                    else
                        $multiSortArgs[++$i] =& $this->sortDesc;

                    if($pad)
                        $multiSortArgs[++$i] =& $this->sortStr;
                    else if($date)
                        $multiSortArgs[++$i] =& $this->sortInt;
                    else
                        $multiSortArgs[++$i] =& $this->sortReg;

//                    $this->Logger->debug('Ordering by: '.$column.' '.$orderObject->getDirection());
                    ++$i;
                }

                // always needs to add ID, to match MySQL
                $multiSortArgs[$i] =& $id_sort_array;

                $multiSortArgs[++$i] =& $this->sortDesc;
                $multiSortArgs[++$i] =& $this->sortInt;

                $multiSortArgs[] =& $nodes;

                call_user_func_array('array_multisort', $multiSortArgs);

                unset($multiSortArgs);
                unset($sortArrays);
            }

        }



        return $nodes;
    }

    public function getOrderObjects(NodeQuery $nodeQuery, $ignoreNone = false)
    {
        $nodeRefs = $nodeQuery->getParameter('NodeRefs.normalized');
        $firstElement = current($nodeRefs)->getElement();

        $offset = $nodeQuery->getOffset()!=null?$nodeQuery->getOffset():0;
        $limit = $nodeQuery->getLimit();

        // ORDER BYS

        $arr = array(
            'Title',
            'ActiveDate',
            'CreationDate',
            'SortOrder',
            'TreeID'
        );

        $default = $firstElement->getDefaultOrder();
        if(empty($default))
            $default = 'ActiveDate DESC';

        if(count($order = explode(' ', $default)) == 2)
        {
            $field = $order[0];
            $direction = $order[1];
            $defaultSorts = array($field => $direction);
        }

        $dtoSorts = $nodeQuery->getOrderBys();

        if ($dtoSorts == null && !$ignoreNone)
            $dtoSorts = $defaultSorts;

        $diff = array_diff(array_keys($dtoSorts), array_keys($arr));
        $merged = array_merge($diff, $arr);
        $sorts = array_unique($merged);

        $orderObjects = array();
        $metaCount = 1;

        foreach ($sorts as $name => $column) {
            if (is_int($name)) $name = $column;
            if (isset($dtoSorts[$name])) {
                $direction = $dtoSorts[$name];
                if (strcasecmp($name, 'NodeRefs') === 0) {
                    //$dtoParameterToSortBy = $direction;
                    $nodeRefs = array_slice($nodeRefs, 0, ($limit+$offset));
                    $orderObjects[] = new NodeOrderBy('NodeRef', $nodeRefs);
                } else if(strpos($name, '#' ) !== false) {
                    //$orderByMeta = new MetaPartial($name);
                    $name = substr($name, strpos($name,'#')+1);
                    if($firstElement->getSchema()->hasMetaDef($name))
                    {
                        $s = $firstElement->getSchema()->getMetaDef($name);

                        $datatype = $s->Datatype;

                        $column = "{$datatype}Value{$metaCount}";
                        $metaCount++;
                        $orderObjects[] = new NodeOrderBy($column, $direction, $name, $datatype);
                    }
                } else {
                    $orderObjects[] = new NodeOrderBy($column, $direction);
                }
            }
        }

        return $orderObjects;
    }


    public function sliceNodes($nodes, $limit, $offset = 0, $forceSlice = false)
    {

        $this->Logger->debug('Count:  '.count($nodes));
        $this->Logger->debug('Limit:  '.$limit);
        $this->Logger->debug('Offset: '.$offset);

        if(!is_null($limit) && ( count($nodes) > $limit || $forceSlice) )
            $nodes = array_slice($nodes, $offset, $limit);

        return $nodes;
    }


    public function compareNodes($a, $b, $orderObjects)
    {
        $sorted = $this->sortNodes(array($a, $b), $orderObjects);
        return ($sorted[0] === $a);
    }



}

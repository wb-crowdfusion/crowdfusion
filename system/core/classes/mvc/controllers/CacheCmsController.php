<?php
/**
 * CacheCmsController
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
 * @version     $Id: CacheCmsController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * CacheCmsController
 *
 * @package     CrowdFusion
 */
class CacheCmsController extends AbstractCmsController
{
    protected $ApplicationContext;

    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    protected function info()
    {
        $this->checkPermission('cache-edit');

        $cacheStores = $this->ApplicationContext->getObjectsByNameMatchEnd('CacheStore');

        $results = array();
        foreach((array)$cacheStores as $storeName => $store)
            $results[] = array('CacheStoreName' => $storeName, 'ClassName' => get_class($store));

        return $results;
    }

    protected function clear()
    {
        if($this->buttonPushed())
        {
            $this->checkPermission('cache-edit');

            $store = $this->Request->getRequiredParameter('store');
            if($store == 'all')
            {

                $cacheStores = $this->ApplicationContext->getObjectsByNameMatchEnd('CacheStore');
                foreach($cacheStores as $cacheStore)
                    $cacheStore->expireAll();
            } else {

                $cacheStore = $this->ApplicationContext->object($store);
                if(!$cacheStore instanceof CacheStoreInterface)
                    $this->errors->reject('Object does not implement CacheStoreInterface');

                $cacheStore->expireAll();
            }

        }

        return new View($this->successView());
    }

}
<?php
/**
 * System API methods.
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
 * @version     $Id: NodeApiController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * System API methods.
 *
 * @package     CrowdFusion
 */
class SystemApiController extends AbstractApiController
{
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

    public function storagedate()
    {
        try {
            $data = array();

            $date = $this->DateFactory->newStorageDate();

            $data[] = array(
                'Unix' => $date->format('U'),
                'Year' => $date->format('Y'),
                'Month' => $date->format('m'),
                'Day' => $date->format('d'),
                'Hour' => $date->format('H'),
                'Minute' => $date->format('i'),
                'Second' => $date->format('s'),
                'Timezone' => $date->format('T'),
            );

            $this->bindToActionDatasource($data);
            return new View($this->successView());

        } catch(Exception $e) {
            $this->bindToActionDatasource(array());
            $this->errors->addGlobalError($e->getCode(), $e->getMessage())->throwOnError();
        }
    }

    public function localdate()
    {
        try {
            $data = array();

            $date = $this->DateFactory->newLocalDate();

            $data[] = array(
                'Date' => $date->format('Y-m-d'),
                'Time' => $date->format('g:i A'),
            );

            $this->bindToActionDatasource($data);
            return new View($this->successView());

        } catch(Exception $e) {
            $this->bindToActionDatasource(array());
            $this->errors->addGlobalError($e->getCode(), $e->getMessage())->throwOnError();
        }
    }

}

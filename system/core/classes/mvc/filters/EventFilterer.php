<?php
/**
 * EventFilterer
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
 * @version     $Id: EventFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * EventFilterer
 *
 * @package     CrowdFusion
 */
class EventFilterer extends AbstractFilterer
{

    protected $Events = null;

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    protected function getDefaultMethod()
    {
        return "trigger";
    }


    /**
     * Creates
     *
     * Expected Param:
     *  name string
     *
     * @return string
     */
    protected function trigger()
    {
        $eventName = $this->getParameter('name');

        if(StringUtils::strToBool($this->getParameter('allowTemplateCode')) == true)
            $this->allowTemplateCode();

        $params = $this->getParameters();

        $output = new Transport();
        $output->String = '';

        foreach($params as $k => $v) {
            if($k != 'String' && $k !='name') {
                $output->$k = $v;
            }
        }

        $this->Events->trigger($eventName, $output);

        return $output->String;
    }

    protected function filter()
    {
        $eventName = $this->getParameter('name');

        if(StringUtils::strToBool($this->getParameter('allowTemplateCode')) == true)
            $this->allowTemplateCode();

        $params = $this->getParameters();

        $output = new Transport();

        foreach($params as $k => $v) {
            $output->$k = $v;
        }

        if (!isset($output->Node)) {
            $output->Node = $this->getLocal('Node');
        }

        $this->Events->trigger($eventName, $output);

        return $output->value;


    }

}
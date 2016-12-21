<?php
/**
 * Events
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
 * @version     $Id: Events.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Events
 *
 * @package     CrowdFusion
 */
class Events {

    protected $Logger;

    public function __construct(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    public function setLogger(LoggerInterface $Logger)
    {
        $this->Logger = $Logger;
    }

    /**
     * Bind a callback function to an event, executed by priority ascending
     *
     * If two callbacks are bound with the same priority, they are executed in
     * the order in which they were bound.
     *
     * For information on callback type, see
     * {@link http://us.php.net/manual/en/language.pseudo-types.php#language.types.callback PHP documentation}
     *
     * @param string   $eventName Unique event name to bind function to
     * @param callback $function  Function to be called
     * @param int      $priority  Integer priority affects execution order
     *
     * @return void
     */
    public function bindEvent($eventName, $objectNameOrObject, $eventMethod, $priority = PHP_INT_MAX, $passContext = false)
    {
        $this->ApplicationContext->bindEvent($eventName, $objectNameOrObject, $eventMethod, $priority, $passContext);
    }

    public function unbindEvent($eventName, $objectName, $eventMethod)
    {
        $this->ApplicationContext->unbindEvent($eventName, $objectName, $eventMethod);
    }

    /**
     * Trigger the event, passing parameters to callbacks
     *
     * All additional parameters beyond the first parameter {@link $eventName}
     * will be passed through to the callback function as parameters.
     *
     * Executes all bound callbacks in ascending priority order.  If two callbacks
     * are bound with the same priority, they are executed in the order in
     * which they were bound.
     *
     * Does nothing if no callbacks are bound to {@link $eventName}
     *
     * It's important to note that this function will always return void.  If
     * you'd like an event callback to have the option of altering data,
     * you must pass additional parameters to the callback by reference.
     *
     * @param string $eventName Event name to trigger
     * @param mixed  $v, ...    Unlimited number of objects or variables to
     *                              pass thru to the event callback
     *
     * @return int
     * @throws EventsException If callback was invalid
     */
    public function trigger($eventName, $arg1 = 'NULLPARAMETER')
    {
        $this->Logger->debug('Triggering event ['.$eventName.']');
        if(($event = $this->ApplicationContext->getEvent($eventName)) == false)
            return;

        $args = array();
        $stack = null;
        if($arg1 !== 'NULLPARAMETER')
        {
            if (PHP_VERSION_ID < 50205)
                $stack = debug_backtrace();
            else
                $stack = debug_backtrace(false);

            if (isset($stack[0]["args"]))
                for ($i=1; $i < count($stack[0]["args"]); $i++)
                    $args[] =& $stack[0]["args"][$i];
        }

        $count = 0;
        foreach ($event as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                list($objectIDorObj, $method, $isObject, $passContext) = $callback;

                if (!$isObject)
                    $objectIDorObj = $this->ApplicationContext->object($objectIDorObj);

                $this->Logger->debug('Calling ['.get_class($objectIDorObj).'::'.$method.']');

                if ($passContext) {
                    $newArgs = $args;
                    if(is_null($stack))
                    {
                        if (PHP_VERSION_ID < 50205)
                            $stack = debug_backtrace();
                        else
                            $stack = debug_backtrace(false);
                    }
                    $callingClass = $stack[1]["class"];
                    array_unshift($newArgs, array('priority'=> $priority, 'index'=>$count, 'name'=>$eventName, 'callingClass'=>$callingClass));
                    call_user_func_array(array($objectIDorObj, $method), $newArgs);
                } else {
                    call_user_func_array(array($objectIDorObj, $method), $args);
                }
                ++$count;
            }
        }
        return $count;
    }

}
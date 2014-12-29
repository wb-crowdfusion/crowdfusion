<?php
/**
 * Benchmark
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
 * @version     $Id: Benchmark.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Benchmark provides simple execution profiling using named start() and end()
 * functions to determine which benchmark to measure.
 *
 * @package     CrowdFusion
 */
class Benchmark implements BenchmarkInterface
{

    protected $enabled = false;

    protected $timers = array();
    protected $globalMode = false;
    protected $firstTime = null;

    protected $Logger;

    /**
     * Turns the actions for this class on or off
     *
     * @param boolean $enabled if set to true, the class will perform benchmarking
     *
     * @return void
     */
    public function setBenchmarkEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Returns the enabled status of the benchmark system
     *
     * @return boolean if true, then the benchmark system is enabled
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Injects the Logger
     *
     * @param LoggerInterface $Logger The logger to use
     *
     * @return void
     */
    public function setLogger($Logger)
    {
        $this->Logger = $Logger;
    }

    /**
     * Starts a timer with the given name
     *
     * The {@link $preset} parameter is useful for starting a timer for a
     * process that begin before the Benchmark instance was available, ex.
     * at the start of the entire request
     *
     * The {@link $isGlobalStart} parameter is required if you'd like
     * {@link getTotals()} to return aggregate times for each stored timer.
     * Enables aggregation mode where every timer end will also store time in
     * relation to the entire request.
     *
     * Upon starting a timer by the same name as a previously stored timer,
     * this function will auto-increment the {@link $name} with a numeric digit
     * ex. 'timer-2'.
     *
     * @param string  $name          Name of timer
     * @param int     $preset        If not null, Unix timestamp with microseconds
     *                                  to store as the start time
     * @param boolean $isGlobalStart Whether or not this timer begins the entire
     *                                  request, used for aggregate profiling in {@link getTotals()}
     *
     * @return string Timer name
     */
    public function start($name, $preset = null, $isGlobalStart = false)
    {
        if(!$this->isEnabled()) return;

        // if(empty($name))
        //     throw new BenchmarkException("Empty timer name!");
        //
        // $name = $this->uniqueTimerName($name);

        $t = $this->getTime();

        $this->timers[$name] = array(
            'name'        => $name,
            // 'start'       => $preset == null ? $t : $preset,
            // 'end'         => null,
            'time'        => $preset == null ? $t : $preset,
            'aggregate'   => null,
            // 'checkpoints' => array()
        );

        if (!$this->globalMode && $isGlobalStart) {
            $this->globalMode = true;
            $this->firstTime  = $this->timers[$name]['time'];
        }

        return $name;
    }

    /**
     * Ends and stores the elapsed time for the timer with the given name
     *
     * If is in aggregation mode, also stores the aggregate time for this timer.
     * This is useful for tracing a request and noticing large lapses in
     * processing that may not have a timer implemented.
     *
     * If a timer by the same name has already been stored, this function will
     * auto-increment the {@link $name} with a numeric digit, ex. 'timer-2',
     * before attempting to store the end time.
     *
     * @param string $name Name of timer
     *
     * @return int Elapsed time in milliseconds
     * @throws BenchmarkException When ending a timer that was not started
     */
    public function end($name)
    {
        if(!$this->isEnabled()) return;

        $aggregate = 0;

        // if (empty($name))
        //     throw new BenchmarkException("Empty timer name!");
        //
        // if (array_key_exists($name, $this->timers) && !empty($this->timers[$name]['end'])) {
        //     $inc = 1;
        //     while (array_key_exists($name.'-'.$inc, $this->timers) && !empty($this->timers[$name]['end'])) {
        //         $inc++;
        //     }
        //     $name = $name.'-'.$inc;
        // }

        $oldTime = $this->firstTime;
        if (array_key_exists($name, $this->timers))
            $oldTime = $this->timers[$name]['time'];
            //throw new BenchmarkException("Timer name '{$name}' not found! Please call start(...) first!");

        // $this->timers[$name]['end'] = $this->getTime();
        $n = $this->getTime();

        // $this->timers[$name]['time'] = ($this->timers[$name]['end'] - $this->timers[$name]['start'])*1000;
        $time = ($n - $oldTime)*1000;
        if ($this->globalMode)
            // $this->timers[$name]['aggregate'] = ($n - $this->firstTime)*1000;
            $aggregate = ($n - $this->firstTime)*1000;

        // $this->Logger->debug("Timer [{$name}]: {$this->timers[$name]['time']}ms, aggregate {$this->timers[$name]['aggregate']}ms");
        $this->Logger->debug("Timer [{$name}]: {$time}ms".($this->globalMode?", aggregate {$aggregate}ms":""));
        unset($this->timers[$name]);

        return $time;
    }

    /**
     * Used to mark checkpoints in loops or repeated blocks of code without
     * the need for {@link start()} and {@link end()} every time.
     *
     * Given a previously started timer name "loop", this function will
     * store a timer named "loop-1".  Any subsequent calls will store the timer
     * for the previously started checkpoint by the same name.
     *
     * <code>
     *  $Benchmark->start('loop'); // starts timer
     *  while(...) {
     *      // do stuff
     *      $Benchmark->checkpoint('loop'); // stores 'loop-1', 'loop-2', etc.
     *  }
     *  $Benchmark->end('loop'); // stores full timer for 'loop'
     * </code>
     *
     * @param string $name Name of timer
     *
     * @return int Elapsed time in milliseconds
     * @throws BenchmarkException When marking a checkpoint for timer name that
     *  was not started
     */
    // public function checkpoint($name)
    // {
    //     if(!$this->isEnabled()) return;
    //
    //     if (empty($name))
    //         throw new BenchmarkException("Empty timer name!");
    //
    //     if (!array_key_exists($name, $this->timers))
    //         throw new BenchmarkException("Cannot create checkpoint for timer '{$name}', timer not found! Please call start(...) first!");
    //
    //     $this->timers[$name]['checkpoints'][] = $this->getTime();
    //
    //     $cTime = $this->getCurrentCheckpointTime($name);
    //
    //     $this->Logger->debug("Checkpoint [{$name}]: {$cTime}ms");
    //
    //     return $cTime;
    // }

    /**
     * Returns an array of all stored times.  A numerically keyed array, each
     * value is an array containing the following keys: name, time, aggregate
     *
     * <code>
     * array(
     *  0 => array('name' => 'boot',   'time' => 8,  'aggregate' => 8),
     *  1 => array('name' => 'dbconn', 'time' => 2,  'aggregate' => 10),
     *  2 => array('name' => 'render', 'time' => 50, 'aggregate' => 90)
     * )
     * </code>
     *
     * @return array
     */
    // public function getTotals()
    // {
    //     $arr = array();
    //
    //     foreach ($this->timers as $timer) {
    //         $arr[] = array(
    //             'name'      => $timer['name'],
    //             'time'      => $timer['time'],
    //             'aggregate' => $timer['aggregate']
    //         );
    //
    //         if (!empty($timer['checkpoints'])) {
    //
    //             $last = $timer['start'];
    //
    //             foreach ($timer['checkpoints'] as $k => $checkpoint) {
    //                 $arr[] = array(
    //                     'name'      => $timer['name'].'-'.$k,
    //                     'time'      => ($checkpoint - $last)*1000,
    //                     'aggregate' => ($checkpoint - $timer['start'])*1000
    //                 );
    //                 $last = $checkpoint;
    //             }
    //         }
    //     }
    //
    //     return $arr;
    // }

    /**
     * Increment, if necessary, the timer {@link $name} usint an numeric suffix, -1, -2, etc.
     * If 'loop-1' is passed, and that timer name already exists, then 'loop-1-1', 'loop-1-2', etc will be used.
     *
     * @param string $name The name of a timer that may already exist
     *
     * @return string New unique timer name, or original name if that timer didn't exist
     */
    // protected function uniqueTimerName($name)
    // {
    //     if (!array_key_exists($name, $this->timers))
    //         return $name;
    //
    //     $inc = 1;
    //     $newname = $name;
    //     while (array_key_exists($name.'-'.$inc, $this->timers)) {
    //         $inc++;
    //         $newname = $name.'-'.$inc;
    //     }
    //
    //     return $newname;
    // }

    /**
     * Generates a Unix timestamp
     *
     * @return int Unix timestamp
     */
    protected function getTime()
    {
        return microtime(true);
    }

    /**
     * Gets the last checkpoint elapsed time for a given timer {@link $name}
     *
     * @param string $name The name of a timer that has one or more checkpoints
     *
     * @return int Elapsed time in milliseconds
     * @throws BenchmarkException When timer doesn't have any checkpoints
     */
    // protected function getCurrentCheckpointTime($name)
    // {
    //     $count = count($this->timers[$name]['checkpoints']);
    //
    //     if ($count == 1) {
    //         return $this->timers[$name]['checkpoints'][0] - $this->timers[$name]['start'];
    //     } else if ($count > 1) {
    //         return $this->timers[$name]['checkpoints'][$count-1] - $this->timers[$name]['checkpoints'][$count-2];
    //     }
    //
    //     throw new BenchmarkException("Invalid checkpoint data!");
    // }

}

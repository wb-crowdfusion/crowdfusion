<?php
/**
 * Interface for Benchmark, provides simple execution profiling
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
 * @version     $Id: BenchmarkInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Interface for Benchmark, provides simple execution profiling
 *
 * @package     CrowdFusion
 */
interface BenchmarkInterface
{

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
     * @param int     $preset        If not null, Unix timestamp to store as the
     *  start time
     * @param boolean $isGlobalStart Whether or not this timer begins the entire
     * 	request, used for aggregate profiling in {@link getTotals()}
     *
     * @return void
     */
    public function start($name, $preset = null, $isGlobalStart = false);

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
     * @param string 	$name Name of timer
     *
     * @return int Elapsed time in milliseconds
     * @throws BenchmarkException When ending a timer that was not started
     */
    public function end($name);

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
    // public function checkpoint($name);

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
    // public function getTotals();


    /**
     * Returns the enabled status of the benchmark system
     *
     * @return boolean if true, then the benchmark system is enabled
     */
    public function isEnabled();
}
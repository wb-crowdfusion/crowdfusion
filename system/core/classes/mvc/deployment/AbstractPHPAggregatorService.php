<?php
/**
 * AbstractPHPAggregatorService
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
 * @version     $Id: AbstractPHPAggregatorService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractPHPAggregatorService
 *
 * @package     CrowdFusion
 */
abstract class AbstractPHPAggregatorService extends AbstractDeploymentService
{

    /**
     * Returns all url data-sets from the aggregate file type specified by {@link $subject} (in the implementing class.)
     *
     * @return array
     */
    public function findAll()
    {
        if (empty($this->context) || empty($this->siteDomain) || empty($this->deviceView) || empty($this->design))
            return array();

        if ($this->isSiteDeployment) {
            $design = $this->design;
            $deviceView = $this->deviceView;
        } else {
            $design = '';
            $deviceView = '';
        }

        $aggregatePath = $this->getAggregatePath();

        $files = null;
        $md5   = null;

        $this->Logger->debug("Aggregate Path: {$aggregatePath}");


        if ($this->isDevelopmentMode || $this->isOneOffRedeploy || !file_exists($aggregatePath))
            list($files,$md5) = $this->getFiles();

        if (file_exists($aggregatePath)) {

            //$contents = JSONUtils::decode(file_get_contents($aggregatePath),true);
            include $aggregatePath;

            //if this IS development mode, a refresh is performed everytime the md5's dont' match
            //if this is NOT development mode, the only way to force a refresh is to delete the .php file
            if (( $this->isDevelopmentMode === false && $this->isOneOffRedeploy === false)
             || ( ($this->isDevelopmentMode === true || $this->isOneOffRedeploy === true) && $md5 === $contents['md5']))
                if(isset(${$this->subject}))
                    return ${$this->subject}; // files haven't changed, return previously aggregated contents
                else
                    return array();
        }

        //rebuild file
        return $this->aggregateFiles($md5, $files, $aggregatePath);
    }

    /**
     * Returns the absolute filename of the subject file defined by the implementing class
     *
     * @return string
     */
    protected function getAggregatePath()
    {
        $aggregatePath = $this->getBaseDeployDirectory().'.'.$this->subject.'.php';

        return $aggregatePath;
    }

    /**
     * Combines all the listed files into a single file at {@link $aggregatePath}
     *
     * @param string $md5           The md5 (stored in the aggregate)
     * @param array  $files         A list of files to store
     * @param string $aggregatePath The name of the file to write our aggregate to
     *
     * @return array An array with urls as the keys and data as values
     */
    protected function aggregateFiles($md5, array $files, $aggregatePath)
    {
        $this->Logger->debug('Rebuilding...');


        $file = array("md5" => $md5, $this->subject => array(), "files" => $files);

        $aggregateContents = "<?php\n\n\$contents = \n".var_export($file, true)."\n\n?><?php
        \$".$this->subject." = array(
        ";

        foreach ($files as $filename) {
            if (!file_exists($filename))
                continue;

            $fileContents = file_get_contents($filename);
            $fileContents = preg_replace('/\$'.$this->subject.'\s*\=\s*array\s*\(/i', '',$fileContents);
            $fileContents = preg_replace('/\<\?php/i', '',$fileContents);
            $fileContents = preg_replace('/\)\;\s*(\?\>)?/', '',$fileContents);
            $aggregateContents .= $fileContents;

//            include $filename;
            //$subject = $this->subject;

//            if (isset($$subject)) {
//                foreach ($$subject as $url => $params)
//                    $file[$this->subject][$url] = $params;
//            }

        }

        $aggregateContents .= " ); ?>";

//        if (empty(${$this->subject}))
//            return array();

        FileSystemUtils::safeFilePutContents($aggregatePath, $aggregateContents);

        include $aggregatePath;

        return ${$this->subject};
    }

    /**
     * Returns an array with 2 items.
     * [array of all files matching the {@link $subject}, a unique MD5 that identifies this fileset]
     *
     * @return array
     * @throws Exception if no themes or plugins are active
     */
    protected function getFiles()
    {
        if ($this->isSiteDeployment) {
            if ($this->deviceView != 'main' && !is_dir("{$this->viewDirectory}/{$this->context}/{$this->siteDomain}/{$this->deviceView}")) {
                $this->deviceView = 'main';
            }

            if ($this->design != 'default' && !is_dir("{$this->viewDirectory}/{$this->context}/{$this->siteDomain}/{$this->deviceView}/{$this->design}")) {
                $this->design = 'default';
            }
        }

        $list = array();
        if (!empty($this->viewDirectory))
            $list[] = $this->viewDirectory;

        $files = array();

        $list = array_merge($this->ApplicationContext->getEnabledPluginDirectories(), $list);

        if (count($list) > 0) {
            krsort($list);
            $pattern = $this->subject.'.php';

            foreach ($list as $tp) {
                if (is_dir($tp)) {
                    $paths = $this->getPathsToCheck($tp, $pattern);
                    krsort($paths);

                    foreach ($paths as $path) {
                        $this->Logger->debug('Checking ['.$path.']...');
                        if (file_exists($path)) {
                            $this->Logger->debug('Found ['.$path.']');
                            $files[] = $path;
                            break;
                        }
                    }
                } else {
                    $this->Logger->warn('Directory does not exist: '.$tp);
                }
            }

            /*
             * special case handling for routes and redirects.  the domain, device and design
             * specific routes should be included last.  this is so the design can set routes
             * but still inherit the routes from the domain.
             *
             * this SEEMS wrong but the route patterns are the ID in the array so the last
             * route definition wins for a given pattern.
             *
             * order of includes
             * - all routes|redirects from enabled plugins
             * - app/view/context/domain/routes|redirects.php
             * - app/view/context/domain/deviceView/default/routes|redirects.php
             * - app/view/context/domain/deviceView/design/routes|redirects.php
             *
             * the array key is the ID and that IS the pattern, so last key wins.
             * This is not "great" but changing the router is a much bigger problem
             * and this is a little better than what exists now.
             *
             */
            if ('routes' === $this->subject || 'redirects' === $this->subject) {
                $path = "{$this->viewDirectory}/{$this->context}/{$this->siteDomain}/{$this->subject}.php";
                if (file_exists($path)) {
                    $this->Logger->debug('Found ['.$path.']');
                    $files[] = $path;
                }

                if ($this->design != 'default') {
                    $path = "{$this->viewDirectory}/{$this->context}/{$this->siteDomain}/{$this->deviceView}/default/{$this->subject}.php";
                    if (file_exists($path)) {
                        $this->Logger->debug('Found ['.$path.']');
                        $files[] = $path;
                    }
                }

                $path = "{$this->viewDirectory}/{$this->context}/{$this->siteDomain}/{$this->deviceView}/{$this->design}/{$this->subject}.php";
                if (file_exists($path)) {
                    $this->Logger->debug('Found ['.$path.']');
                    $files[] = $path;
                }
            }

            if (empty($files))
                return array(array(), null);

            $timestamps = array();
            foreach ($files as $filename)
                $timestamps[] = filemtime($filename);

            $md5 = md5(serialize(array($files, $timestamps, filemtime(__FILE__))));

            return array($files, $md5);
        }

        throw new Exception('No active themes or plugins.');
    }

}

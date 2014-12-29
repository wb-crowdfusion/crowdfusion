<?php
/**
 * View
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
 * @version     $Id: View.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Encapsulates a view and its data.  A view consists of a logical name (i.e. 'home.cft') and an array of view data.
 * There are two implicit parts to this name: the basename and the type.  The basename is an arbitrary identifier which
 * should resolve to a renderable view template file.  The type indicates the template engine which should be used to
 * render the resolved view template file.  The view data array contains variables which belong to the scope of the
 * view.  These could also be considered "view globals".  A view name could also represent a redirect (i.e.
 * 'redirect:/login').  In this scenario, a redirect is processed immediately by the Dispatcher and should never
 * be rendered.
 *
 * @package     CrowdFusion
 */
class View
{
    protected $name;
    protected $data;

    /**
     * [NOT AUTOWIRED] Creates a view object, accepting the name of the view (i.e. 'home.cft') and an array of view data.
     *
     * @param string $name The name of the view template
     * @param array  $data A key-value array of view data
     */
    public function __construct($name = null, array $data = array())
    {
        $this->setName($name);
        $this->setData($data);
    }

    /**
     * Returns the name for the view
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the view name
     *
     * @param string $name The name of the view (i.e. 'home.cft')
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the view data
     *
     * @return array A key-value array used in the view
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets the view data
     *
     * @param array $data A key-value array used in the view
     *
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Returns true if this view is a redirect
     *
     * @return boolean
     */
    public function isRedirect()
    {
        if(empty($this->name))
            return false;

        return substr($this->name, 0, 9) == 'redirect:';
    }

    /**
     * Returns true if this view is a permanent redirect
     *
     * @return boolean
     */
    public function isPermanentRedirect()
    {
        if (empty($this->name))
            return false;

        return substr($this->name, 0, 19) == 'redirect:permanent:';
    }

    /**
     * Returns the redirect value for this view, providing it's a redirect
     *
     * @return string
     */
    public function getRedirect()
    {
        if (!$this->isRedirect())
            return null;

        if ($this->isPermanentRedirect())
            return substr($this->name, 19);

        return substr($this->name, 9);
    }
}
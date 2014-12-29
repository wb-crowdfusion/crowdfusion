<?php
/**
 * CmsFilterer
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
 * @version     $Id: CmsFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * CmsFilterer
 *
 * @package     CrowdFusion
 */
class CmsFilterer extends AbstractFilterer
{

    /**
     * [IoC] Inject the CMSNavItemService
     *
     * @param CMSNavItemService $CMSNavItemService injected
     *
     * @return void
     */
    public function setCMSNavItemService(CMSNavItemService $CMSNavItemService)
    {
        $this->CMSNavItemService = $CMSNavItemService;
    }

    /**
     * [IoC] Inject Permissions
     *
     * @param Permissions $Permissions injected
     *
     * @return void
     */
    public function setPermissions(Permissions $Permissions)
    {
        $this->Permissions = $Permissions;
    }

    /**
     * [IoC] Inject ElementService
     *
     * @param ElementService $ElementService injected
     *
     * @return void
     */
    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    /**
     * [IoC] Inject SiteService
     *
     * @param SiteService $SiteService injected
     *
     * @return void
     */
    public function setSiteService(SiteService $SiteService)
    {
        $this->SiteService = $SiteService;
    }

    /**
     * [IoC] Inject Benchmark
     *
     * @param BenchmarkInterface $Benchmark Benchmark
     *
     * @return void
     */
    public function setBenchmark(BenchmarkInterface $Benchmark)
    {
        $this->Benchmark = $Benchmark;
    }

    /**
     * [IoC] Inject Request
     *
     * @param Request $Request Request
     *
     * @return void
     */
    public function setRequest(Request $Request)
    {
        $this->Request = $Request;
    }

    /**
     * [IoC] Inject RequestContext
     *
     * @param RequestContext $RequestContext injected
     *
     * @return void
     */
    public function setRequestContext(RequestContext $RequestContext)
    {
        $this->RequestContext = $RequestContext;
    }

    protected $cmsOnerrorAlertEnabled;
    public function setCmsOnerrorAlertEnabled($cmsOnerrorAlertEnabled)
    {
        $this->cmsOnerrorAlertEnabled = $cmsOnerrorAlertEnabled;
    }


    /**
     * Returns the name of the default method, so we can call {% filter cms %} to execute the method
     * returned by this function
     *
     * @return string
     */
    protected function getDefaultMethod()
    {
        return "menu";
    }

    /**
     * Returns a full HTML rendering of the CMS navigation menu
     *
     * @return string
     */
    protected function menu()
    {

        $this->Benchmark->start('getMenu');
        try {
            $menu = $this->CMSNavItemService->getMenu();
        } catch (Exception $e) {
            return '';
        }
        $this->Benchmark->end('getMenu');

        $this->Benchmark->start('buildMenu');
        $dom_id    = $this->getParameter('id');
        $dom_class = $this->getParameter('class');

        $this->matchedCurrentURI = false;

        $root_options = array();
        if (!empty($dom_id))
            $root_options[] = "id='{$dom_id}'";
        if (!empty($dom_class))
            $root_options[] = "class='{$dom_class}'";

        $family_tree = "<ul " . join(' ', $root_options). ">\n";

        $this->requestURI = ltrim($this->Request->getAdjustedRequestURI(), '/');

        foreach ( $menu as $parent ) {
            $family_tree .= $this->_buildMenuTree($parent, "parent", "<span>%s</span>", true);
        }

        $family_tree .= "</ul>\n";
        $this->Benchmark->end('buildMenu');

        return $family_tree;
    }


    /**
     * Recursive function that build the menu tree for each menu item.
     *
     * @param CMSNavItem $parent        The parent item
     * @param string     $classes       Any css classes that should be applied to the items generated
     * @param string     $format_string A format string that determines how menu items will appear in the html
     * @param boolean    $is_top_level  If TRUE, we're processing the top level items
     *
     * @return string
     */
    private function _buildMenuTree($parent, $classes = '', $format_string = '%s', $is_top_level = false)
    {

        // Permissions check
        if (!empty($parent->Permissions)) {
            $permissions = StringUtils::smartExplode($parent->Permissions);

            foreach ( $permissions as $perm ) {
                if (!$this->Permissions->checkPermission(trim($perm)))
                    return '';
            }
        }

        if($parent->Enabled == false)
            return '';

        $contains_current_uri = false;
        $content = '';
        if (($this->requestURI == $parent->URI || stripos($this->requestURI, $parent->URI) === 0) && !$this->matchedCurrentURI) {
            $contains_current_uri    = true;
            $this->matchedCurrentURI = true;
        }

        if (!empty($parent->DoAddLinksFor)) {
            try {
                // Look up all items
                $add_links = array();
                $add_menu_items = StringUtils::smartExplode($parent->DoAddLinksFor);
                foreach ( $add_menu_items as $element_or_aspect )
                    $add_links = array_merge($add_links, $this->lookupElementsForNav(trim($element_or_aspect)));

                $uri = $parent->URI;
                if (substr($uri, -1) != '/')
                    $uri .= '/';

                // Build a submenu with all items.
                if (count($add_links) == 1) {
                    $content .= "\t<a href='{$uri}{$add_links[0]['URI']}/'>";
                    $content .= sprintf($format_string, 'Add '.$add_links[0]['Name']);
                    $content .= "</a>\n";
                } elseif (count($add_links) > 1) {
                    $content .= "\t<a class='daddy'>{$parent->Label}</a>\n";
                    $content .= "<ul>\n";
                    foreach ( $add_links as $link ) {
                        $content .= "<li><a href='{$uri}{$link['URI']}/'>{$link['Name']}</a></li>\n";
                    }
                    $content .= "</ul>\n";
                }
            } catch(Exception $e) {
            }

        } else {
            $content .= "\t<a href='{$parent->URI}'>";
                $content .= sprintf($format_string, $parent->Label);
            $content .= "</a>\n";

            $ccontent = '';
            if (count($parent->Children) > 0) {
                foreach ( $parent->Children as $kid ) {
                    $res = $this->_buildMenuTree($kid);
                    if (!empty($res)) {
                        list($child_content, $child_has_uri) = $res;
                        if ($child_has_uri)
                            $contains_current_uri = true;

                        $ccontent .= $child_content;
                    }
                }
            }

            if (!empty($ccontent)) {
                $content .= "<ul>\n";
                $content .= $ccontent;
                $content .= "</ul>\n";
            }
        }

        $item = "<li id='nav-{$parent->Slug}' class='{$classes}'>\n{$content}</li>\n";

        if (!$is_top_level)
            return array($item, $contains_current_uri);
        else
            return "<li id='nav-{$parent->Slug}' class='{$classes} ". (($contains_current_uri) ? "selected" : "") . "'>\n{$content}</li>\n";
    }

    /**
     * REturns all the elements for the specified element or aspect
     *
     * @param string $element_or_aspect The element or aspect to lookup
     *
     * @return array
     */
    protected function lookupElementsForNav($element_or_aspect)
    {
        $results = array();

        if (substr($element_or_aspect, 0, 1) == '@') {
            $elements = $this->ElementService->findAllWithAspect(substr($element_or_aspect, 1));
            foreach ( $elements as $element ) {
                $results[] = array('Name' => $element->Name, 'URI' => $element->Slug);
            }
        } else {
            // Assume it's an element slug
            $element = $this->ElementService->getBySlug($element_or_aspect);
            $results[] = array('Name' => $element->Name, 'URI' => $element->Slug);
        }

        return $results;
    }

    /**
     *  Returns a URL with sort parameters
     *
     *  Expected Params:
     *   filter  string  the string to operate upon
     *   sort  integer (optional) the starting point for our substring, default 0
     *   field integer (optional) the length of the substring. default 1
     *   order integer (optional) the length of the substring. default 1
     *
     * @return string
     */
    public function sortingLink()
    {
        $filterlist = '';

        if ($this->getParameter('filter') != null) {
            foreach ($this->getParameter('filter') as $get => $value) {
                $filterlist .= 'filter['.$get.'][]='.$value.'&';
            }
        }
        if ($this->getParameter('sort') != null) {
            $sort = $this->getParameter('sort');
        } else {
            $sort = array();
        }
        if (!empty($sort[$this->getParameter('field')])) {
            if ($sort[$this->getParameter('field')] == 'ASC') {
                return $filterlist.'sort['.$this->getParameter('field').']=DESC';
            } else {
                return $filterlist.'sort['.$this->getParameter('field').']=ASC';
            }
        }
        return $filterlist.'sort['.$this->getParameter('field').']='.$this->getParameter('order');
    }


    public function onerrorAlertEnabled()
    {
        return $this->cmsOnerrorAlertEnabled;
    }
}
?>

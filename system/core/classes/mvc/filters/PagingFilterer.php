<?php
/**
 * PagingFilterer
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
 * @version     $Id: PagingFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * PagingFilterer
 *
 * @package     CrowdFusion
 */
class PagingFilterer extends AbstractFilterer
{

    public function setRequest(Request $Request)
    {
        $this->Request = $Request;
    }

    public function setRequestContext(RequestContext $RequestContext)
    {
        $this->RequestContext = $RequestContext;
    }

    protected function getDefaultMethod()
    {
        return "pagination";
    }

    /**
     * Creates a block of HTML that displays paging meta information like:
     *  '<div class="pagination-display">Displaying 1-10 of 51 Results</div>'
     *
     * Expected Params (If any of these aren't found, they're looked for in the Locals instead):
     *  TotalRecords integer The total number of results
     *  RowsPerPage  integer The number of results shown per page
     *  Page         integer The current page number
     *  Start        integer The index of the first record on the page
     *  End          integer The index of the last record on the page
     *
     * @return string a block of HTML with meta paging information
     */
    public function display()
    {
        $totalrecords = $this->getParamDefaultLocal('TotalRecords');
        $maxrows      = $this->getParamDefaultLocal('RowsPerPage');
        $page         = $this->getParamDefaultLocal('Page');
        $start        = $this->getParamDefaultLocal('Start');
        $end          = $this->getParamDefaultLocal('End');

        $formats = array_merge(array(
            'FullTagOpen' => '<div class="pagination-display">',
            'FullTagClose' => '</div>',
            'Prefix' => 'Displaying ',
            'Postfix' => ' Results'
        ), $this->params);

        $str = '';

        $str .= $formats['FullTagOpen'];

        if ($totalrecords <= $end) {
            if ($page > 1 && floor(( ($totalrecords-1) / $maxrows ) + 1) == $page )
                $str .= $formats['Prefix'].$start.'-'.$totalrecords.' of '.$totalrecords;
            else
                $str .= $formats['Prefix'].$totalrecords.$formats['Postfix'];


        } else {
            $str .= $formats['Prefix'].$start.'-'.$end.' of '.$totalrecords;
        }

        $str .= $formats['FullTagClose'];

        if ($totalrecords > 1)
            return $str;
    }

    /**
     * Creates a block of HTML with links to all the pages.
     * Includes a Next and Previous link.
     *
     * Expected Params (These will be looked for in Locals if the Param doesn't exist):
     *  RowsPerPage  integer
     *  Page         integer
     *  TotalRecords integer
     *  PagingString string (optional) A string like 'news/page' that will be converted into something like '/news/page/2' for page 2
     *
     * @return string An HTML block with links to all pages
     */
    public function pagination()
    {
        $maxrows      = (($m = $this->getParameter('RowsPerPage')) !== null)?$m:$this->getLocal('MaxRows');
        $page         = (($m = $this->getParameter('Page')) !== null)?$m:$this->getLocal('Page');
        $totalrecords = (($m = $this->getParameter('TotalRecords')) !== null)?$m:$this->getLocal('TotalRecords');
        $pagingString = (($m = $this->getParameter('PagingString')) !== null)?$m:$this->RequestContext->getControls()->getControl('view_paging');

        if (empty($totalrecords))
            return;
        if(empty($maxrows))
            throw new FiltererException('filter \'paging\' requires the MaxRows template variable to be set');

        $totalpages = intval(($totalrecords-1)/$maxrows)+1;

        $formats = array_merge(array(
            'PageLinkCount' => 7,
            'FullTagOpen' => '<div class="pagination-links">',
            'FullTagClose' => '</div>',
            'NextLink' => 'Next',
            'NextTagOpen' => '<span class="next">',
            'NextTagClose' => '</span>',
            'LastLink' => '',
            'LastTagOpen' => '<span class="last">',
            'LastTagClose' => '</span>',
            'PrevLink' => 'Previous',
            'PrevTagOpen' => '<span class="previous">',
            'PrevTagClose' => '</span>',
            'NumTagOpen' => '<span>',
            'NumTagClose' => '</span>',
            'CurTagOpen' => '<span><strong>',
            'CurTagClose' => '</strong></span>',
            'LinkAnchor' => ''
        ), $this->params);


        $str = $this->fullTagOpen($formats['FullTagOpen']);

        if ($page  > 1)
            $str .= $this->previousTag($formats['PrevTagOpen'],'<a href="'.self::_createLink($page-1, $pagingString).$formats['LinkAnchor'].'">',$formats['PrevLink'],'</a>',$formats['PrevTagClose'], $page-1, $pagingString);

        $linkcount = intVal($formats['PageLinkCount']);
        if ($linkcount % 2 == 0 && $linkcount != 0) $linkcount++;
        if ($linkcount == 1) $linkcount = 3;

        if ($page > (($linkcount-1) / 2)) {
            $start = $page - (($linkcount-1) / 2);
            if ($start > ($totalpages - (($totalpages<$linkcount?$totalpages:$linkcount) - 1))) {
                $start = ($totalpages - (($totalpages<$linkcount?$totalpages:$linkcount) - 1));
            }
        } else {
            $start = 1;
        }
        if ($start+$linkcount > $totalpages) {
            $end = $totalpages;
        } else {
            $end = $start+($linkcount - 1);
        }

        for ($i=$start; $i <= $end; $i++) {
            if ($i == $page) {
                $str .= $this->currentTag($formats['CurTagOpen'],'',$i,'',$formats['CurTagClose'], $i, $pagingString);
            } else {
                $str .= $this->numberTag($formats['NumTagOpen'],'<a href="'.self::_createLink($i, $pagingString).$formats['LinkAnchor'].'">',$i,'</a>',$formats['NumTagClose'], $i, $pagingString);
            }
        }

        if (!empty($formats['LastLink']) && $page != $totalpages)
            $str .= $this->lastTag($formats['LastTagOpen'],'<a href="'.self::_createLink($totalpages, $pagingString).$formats['LinkAnchor'].'">',$formats['LastLink'],'</a>',$formats['LastTagOpen'], $totalpages, $pagingString);

        if ($page != $totalpages)
            $str .= $this->nextTag($formats['NextTagOpen'],'<a href="'.self::_createLink($page+1, $pagingString).$formats['LinkAnchor'].'">',$formats['NextLink'],'</a>',$formats['NextTagClose'], $page+1, $pagingString);

        $str .= $this->fullTagClose($formats['FullTagClose']);

        if ($totalpages > 1)
            return $str;

    }

    protected function fullTagOpen($text)
    {
        return $text;
    }


    protected function currentTag($open, $aOpen, $text, $aClose, $close, $i, $pagingString)
    {
        return $open.$aOpen.$text.$aClose.$close;
    }

    protected function numberTag($open, $aOpen, $text, $aClose, $close, $i, $pagingString)
    {
        return $open.$aOpen.$text.$aClose.$close;
    }

    protected function lastTag($open, $aOpen, $text, $aClose, $close, $i, $pagingString)
    {
        return $open.$aOpen.$text.$aClose.$close;
    }

    protected function nextTag($open, $aOpen, $text, $aClose, $close, $i, $pagingString)
    {
        return $open.$aOpen.$text.$aClose.$close;
    }

    protected function previousTag($open, $aOpen, $text, $aClose, $close, $i, $pagingString)
    {
        return $open.$aOpen.$text.$aClose.$close;
    }

    protected function fullTagClose($text)
    {
        return $text;
    }


    /**
     * Private internal function. Returns a URL that will link to the page specified in {@link $value}
     *
     * @param string $value        The page number
     * @param string $pagingString The string for paging
     *
     * @return string URL
     */
    protected function _createLink($value, $pagingString = null)
    {
        if ($pagingString !=null ) {
            $link = $this->RequestContext->getSite()->getBaseURL().preg_replace("/\/[\/]+/", "/", ltrim($pagingString.'/'.$value.'/'));
            if($this->getLocal('UseQueryStringInCacheKey') != null)
                $link .= ($this->Request->getQueryString() != ""?"?".$this->Request->getQueryString():"");
            return $link;
        } else {
            return URLUtils::appendQueryString($this->Request->getFullURL(), array('view_page'=> $value));
        }
    }



    /**
     * Returns the total number of pages
     *
     * Expected Params (These will be looked for in Locals if the Param doesn't exist):
     *  RowsPerPage  integer
     *  TotalRecords integer
     *
     * @throws FiltererException
     * @return int
     */
    public function totalPages()
    {
        $maxrows      = (($m = $this->getParameter('RowsPerPage')) !== null)?$m:$this->getLocal('MaxRows');
        $totalrecords = (($m = $this->getParameter('TotalRecords')) !== null)?$m:$this->getLocal('TotalRecords');

        if (empty($totalrecords))
            return 0;
        if(empty($maxrows))
            throw new FiltererException('filter \'paging-total-pages\' requires the MaxRows template variable to be set');

        $totalpages = intval(($totalrecords-1)/$maxrows)+1;

        return $totalpages;
    }

    /**
     * This function looks at the Params for the $param and returns the
     * Locals value for $param if it's not found
     *
     * @param string $param The parameter to lookup
     *
     * @return string The value in Params or Locals
     */
    protected function getParamDefaultLocal($param)
    {
        $param = $this->getParameter($param);
        if (!is_null($param))
            return $param;

        return $this->getLocal($param);
    }

    /**
     * Return a sanitized page number.  Is guaranteed to be
     * between 1 and 1000 (unless a different min/max is supplied).
     *
     * @return integer
     */
    protected function sanitizePage()
    {
        $page = $this->getParameter('page');
        $min = $this->getParameter('min');
        $max = $this->getParameter('max');

        if (null === $min) {
            $min = 1;
        }

        if (null === $max) {
            $max = 1000;
        }

        return NumberUtils::bound($page, $min, $max);
    }

    /**
     * Return a sanitized per page number.  Is guaranteed to be
     * between 25 and 1000 (unless a different min/max is supplied).
     *
     * @return integer
     */
    protected function sanitizePerPage()
    {
        $perPage = $this->getParameter('perPage');
        $min = $this->getParameter('min');
        $max = $this->getParameter('max');

        if (null === $min) {
            $min = 25;
        }

        if (null === $max) {
            $max = 1000;
        }

        return NumberUtils::bound($perPage, $min, $max);
    }
}
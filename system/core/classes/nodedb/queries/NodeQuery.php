<?php
/**
 * NodeQuery
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
 * @version     $Id: NodeQuery.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeQuery
 *
 * @package     CrowdFusion
 */
class NodeQuery extends DTO
{

	/**
	 * Returns the query results as an array
	 *
	 * @return array Query results
	 */	
    public function getResultsAsArray()
    {
        return parent::getResults();
    }

	/**
	 * Retrieve only the count for the query
	 *
	 * @param  $flag boolean (Optional) When set to true returns no results,
	 *                                  but sets the TotalRecords value
	 * @return NodeQuery The current object
	 */	
	public function setCountOnly($flag = true)
	{
		return $this->setParameter('Count.only', $flag);
	}

	/**
	 * Retrieve only the NodeRefs for the query results
	 *
	 * @param  $flag boolean (Optional) When set to true only returns NodeRefs as the result,
	 * @return NodeQuery The current object
	 */	
	public function setNodeRefsOnly($flag = true)
	{
		return $this->setParameter('NodeRefs.only', $flag);
	}

	/**
	 * Merges in the specified meta for the results
	 *
	 * @param  $value string Comma-separated list of meta partials in the string
	 *                       form meta#name or #name, also accepts fields in the
	 *                       list to return all field-like meta
	 * @return NodeQuery The current object
	 */	
	public function setMetaSelect($value)
	{
		return $this->setParameter('Meta.select', $value);
	}

	/**
	 * Merges in the specified outbound tags for the results
	 *
	 * @param  $value string Comma-separated list of tag partials in string form, must include
	 *                       #role, also accepts fields to return all field-like tags
	 * @return NodeQuery The current object
	 */	
	public function setOutTagsSelect($value)
	{
		return $this->setParameter('OutTags.select', $value);
	}

	/**
	 * Merges in the specified inbound tags for the results
	 *
	 * @param  $value string Comma-separated list of tag partials in string form, must include
	 *                       #role, also accepts fields to return all field-like tags
	 * @return NodeQuery The current object
	 */	
	public function setInTagsSelect($value)
	{
		return $this->setParameter('InTags.select', $value);
	}

	/**
	 * Set Aspects or Elements to select from
	 *
	 * @param  $value string Comma-separated list of element slugs, or aspect slugs preceded by @
	 *                       for example, all elements containing the aspect members can be expressed
	 *                       as @members
	 * @return NodeQuery The current object
	 */	
	public function setElementsIn($value)
	{
		return $this->setParameter('Elements.in', $value);
	}

	/**
	 * Set which site you would like to query
	 *
	 * @param  $value string Comma-separated list of site slugs, for example www-example-com
	 * @return NodeQuery The current object
	 */	
	public function setSitesIn($value)
	{
		return $this->setParameter('Sites.in', $value);
	}

	/**
	 * Set Node Query parameter for matching against the node slug
	 *
	 * @param  $value string Single slug or a comma-separated list of slugs
	 *                       to match against the node Slug column
	 * @return NodeQuery The current object
	 */	
	public function setSlugsIn($value)
	{
		return $this->setParameter('Slugs.in', $value);
	}

	/**
	 * Case-sensitive match against the Title column
	 *
	 * @param  $value string Text to match node title against
	 * @return NodeQuery The current object
	 */	
	public function setTitleEq($value)
	{
		return $this->setParameter('Title.eq', $value);
	}

	/**
	 * Case-insensitive match against the Title column
	 *
	 * @param  $value string Text to match node title against
	 * @return NodeQuery The current object
	 */	
	public function setTitleIeq($value)
	{
		return $this->setParameter('Title.ieq', $value);
	}

	/**
	 * A case-insensitive LIKE against the Title column
	 *
	 * @param  $value string Text to match node title against
	 * @return NodeQuery The current object
	 */	
	public function setTitleLike($value)
	{
		return $this->setParameter('Title.like', $value);
	}

	/**
	 * Case-insensitive search for a first character in the title of the element
	 *
	 * @param  $character string Any alphabetical letter or #, case-insensitive
	 *                           matched against the first character of the Title
	 * @return NodeQuery The current object
	 */	
	public function setTitleFirstChar($character)
	{
		return $this->setParameter('Title.firstChar', $character);
	}

	/**
	 * ActiveDate is on or before the supplied date & time
	 *
	 * @param  $date string Date and time used in search
	 * @return NodeQuery The current object
	 */	
	public function setActiveDateBefore($date)
	{
		return $this->setParameter('ActiveDate.before', $date);
	}

	/**
	 * ActiveDate is after the supplied date & time
	 *
	 * @param  $date string Date and time used in search
	 * @return NodeQuery The current object
	 */	
	public function setActiveDateAfter($date)
	{
		return $this->setParameter('ActiveDate.after', $date);
	}

	/**
	 * ActiveDate is on or after the supplied day, starting at 12:00:00 AM
	 *
	 * @param  $date string Date used in search
	 * @return NodeQuery The current object
	 */	
	public function setActiveDateStart($date)
	{
		return $this->setParameter('ActiveDate.start', $date);
	}

	/**
	 * ActiveDate is on or before the supplied day, ending at 11:59:59 PM
	 *
	 * @param  $date string Date used in search
	 * @return NodeQuery The current object
	 */	
	public function setActiveDateEnd($date)
	{
		return $this->setParameter('ActiveDate.end', $date);
	}

	/**
	 * CreationDate is on or before the supplied date & time
	 *
	 * @param  $date string Date and time used in search
	 * @return NodeQuery The current object
	 */	
	public function setCreationDateBefore($date)
	{
		return $this->setParameter('CreationDate.before', $date);
	}

	/**
	 * CreationDate is after the supplied date & time
	 *
	 * @param  $date string Date and time used in search
	 * @return NodeQuery The current object
	 */	
	public function setCreationDateAfter($date)
	{
		return $this->setParameter('CreationDate.after', $date);
	}

	/**
	 * CreationDate is on or after the supplied day, starting at 12:00:00 AM
	 *
	 * @param  $date string Date used in search
	 * @return NodeQuery The current object
	 */	
	public function setCreationDateStart($date)
	{
		return $this->setParameter('CreationDate.start', $date);
	}

	/**
	 * CreationDate is on or before the supplied day, ending at 11:59:59 PM
	 *
	 * @param  $date string Date used in search
	 * @return NodeQuery The current object
	 */	
	public function setCreationDateEnd($date)
	{
		return $this->setParameter('CreationDate.end', $date);
	}

	/**
	 * Exact match against the TreeID column
	 *
	 * @param  $value string Tree ID to match against
	 * @return NodeQuery The current object
	 */	
	public function setTreeIDEq($value)
	{
		return $this->setParameter('TreeID.eq', $value);
	}

	/**
	 * All children of the node with the given TreeID
	 *
	 * @param  $value string Tree ID for parent node
	 * @return NodeQuery The current object
	 */	
	public function setTreeIDChildOf($value)
	{
		return $this->setParameter('TreeID.childOf', $value);
	}

	/**
	 * Match against a Status
	 *
	 * @param  $value string Possible values are: published, draft, or deleted
	 * @return NodeQuery The current object
	 */	
	public function setStatusEq($value)
	{
		return $this->setParameter('Status.eq', $value);
	}

	/**
	 * When set to true, returns all nodes where Status is published and
	 * ActiveDate is before now
	 *
	 * @param  $value boolean (Optional) Flag indicating all active and
	 *                                   published nodes should be returned
	 * @return NodeQuery The current object
	 */	
	public function setStatusIsActive($value = true)
	{
		return $this->setParameter('Status.isActive', $value);
	}

	/**
	 * When set to true, includes published, draft, and deleted nodes
	 * NOTE: node queries by default do not return deleted nodes
	 *
	 * @param  $value boolean (Optional) Flag indicating if nodes of all
	 *                                   statuses should be returned
	 * @return NodeQuery The current object
	 */	
	public function setStatusAll($value = true)
	{
		return $this->setParameter('Status.all', $value);
	}

	/**
	 * Matches nodes containing ALL of the supplied outbound tags
	 *
	 * @param  $value string Comma-separated list of tag partials
	 * @return NodeQuery The current object
	 */	
	public function setOutTagsExist($value)
	{
		return $this->setParameter('OutTags.exist', $value);
	}

	/**
	 * Matches nodes containing ALL of the supplied inbound tags
	 *
	 * @param  $value string Comma-separated list of tag partials
	 * @return NodeQuery The current object
	 */	
	public function setInTagsExist($value)
	{
		return $this->setParameter('InTags.exist', $value);
	}

	/**
	 * Matches nodes containing ALL of the supplied meta
	 *
	 * @param  $value string Comma-separated list of meta partials, in the form
	 *                       #name="value" or #name=value
	 * @return NodeQuery The current object
	 */	
	public function setMetaExist($value)
	{
		return $this->setParameter('Meta.exist', $value);
	}

}
<?php
/**
 * AbstractCmsBuilder
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
 * @version     $Id: AbstractCmsBuilder.php 2021 2010-02-18 00:57:55Z ruswerner $
 */

/**
 * AbstractCmsBuilder
 *
 * @package     CrowdFusion
 */
abstract class AbstractCmsBuilder extends AbstractXBuilder {

	protected $js, $xhtml = array();
	protected $schema = null;

    protected function xmodule() {
        //reset arrays for each xmodule
        $this->xhtml = array();
        $this->js = array();
    }

	protected function fieldset() {
		$id = $this->xml->getAttribute('id');
		if(empty($id))
			$id = 'fieldset_'.rand(0, 1000);

        $collapsable = $this->xml->getAttribute('collapsable');
        if(!empty($collapsable)) {
            $collapsable = 'class="collapsable '.($collapsable=="open"?"open":"closed").'"';
        }

		$this->xhtml[] = "\t<fieldset id=\"{$id}\" {$collapsable}>";
	}


	protected function _fieldset() {
		$this->xhtml[] = "\t</fieldset>";
	}



	protected function group() {
		$this->xhtml[] = "\t\t<ul>";
	}

	protected function _group() {
		$this->xhtml[] = "\t\t</ul>";
	}

	protected function help() {
		$this->_addHelpText($this->_text());
	}

	protected function _addHelpText($text) {
		$this->xhtml[] = "\t\t<div class=\"help-contents\"><p>".$text."</p></div>";
	}

	protected function _fieldClasses($schemafield) {
		$classes = array();
		if(!empty($schemafield->classes)) {
			foreach($schemafield->classes->children() as $class) {
				$classes[] = (string)$class;
			}
		}

		return implode(" ", $classes);

	}

	protected function _buildWidgetOptions($schemafield,$attributes) {


		$partial = $schemafield->Partial;
		$filter = $schemafield->Filter;

		$is_multiple = $schemafield->isMultiple();
		$is_quickadd = $schemafield->isQuickadd();
		$is_sortable = $schemafield->isSortable();
		$tag_direction = $schemafield->Direction;
		if(empty($tag_direction))
			$tag_direction = 'out';

        if(!empty($attributes['title'])) {
            $title = $attributes['title'];
        }
        else {
		    $title = $schemafield->Title;
        }

		$value_opts = $schemafield->ValueOptions;

		$value_is_multiple = false;
		$pre_values = array();
		$values_name = 'role';

		if(!empty($value_opts)) {
			$value_mode = $value_opts->Mode;
			$values_name = $value_opts->Name;
			$value_is_multiple = $value_opts->isMultiple();
			if($value_mode != 'none')
                $pre_values = $value_opts->Values;

		}else {
			$value_mode = 'none';
		}

		$title = StringUtils::jsEscape($title);
		$title_plural = $title; //StringUtils::pluralize($title);

		$opt[] = "			Label: '{$title}'";
		$opt[] = "			LabelPlural: '{$title_plural}'";
		$opt[] = "			ActivateButtonLabel: '{$title}'";

        $element = $partial->getTagElement();
        if(empty($element))
            $element = '@'.$partial->getTagAspect();

        $quickaddelement = $element;

        if(!empty($attributes['quick-add-element'])) {
            $quickaddelement = $attributes['quick-add-element'];
        }

        $quickaddaction = 'node-quick-add';

        if(!empty($attributes['quick-add-action'])) {
            $quickaddaction = $attributes['quick-add-action'];
        }


        if(!empty($attributes['search-parameters'])) {
            $str = array();
            $params = explode('&',urldecode($attributes['search-parameters']));
            foreach($params as $param) {
                list($k,$v) = explode('=',$param, 2);
                $str[] = "'".$k."' : '".$v."'";
            }
            $opt[] = "			SearchParameters: {".implode(',',$str)."}";
        } else {
            $opt[] = "			SearchParameters: {'Elements.in' : '{$element}'}"; //Title keyword is auto-added by widget
        }

        if(!empty($attributes['search-url'])) {
            $opt[] = "			SearchURL: '{$attributes['search-url']}'";
        }

        if(!empty($attributes['show-element'])) {
            $opt[] = "			ShowElementInChosenList: true";
            $opt[] = "			ShowElementInSearchResults: true";
        }

		if($tag_direction != 'out')
			$opt[] = "		TagDirection: '{$tag_direction}'";

		if(!empty($filter))
    		$opt[] = "		TagFilter: '".$filter."'";

		if($is_quickadd) {
			$opt[] = "			AllowQuickAdd: true";

			$opt[] = "			QuickAddNonce: '{% filter nonce?action={$quickaddaction} %}'";
			$opt[] = "			AddTagNonce: '{% filter nonce?action=node-add-tag %}'";
			$opt[] = "			ReplaceNonce: '{% filter nonce?action=node-replace %}'";
            $opt[] = "			QuickAddElement : '{$quickaddelement}'";
			$opt[] = "			EditNonce: '{% filter nonce?action=node-edit %}'";
		}

		if($is_multiple)
			$opt[] = "			AllowMultiple: ".($is_multiple?'true':'false')."";

		if($is_sortable)
			$opt[] = "			AllowReorderChosenList: ".($is_sortable?'true':'false')."";

		if($value_mode != 'none') {
			$opt[] = "		ValueMode: '{$value_mode}'";
			$opt[] = "		AllowMultipleValues: ".($value_is_multiple?'true':'false')."";

			if(!empty($pre_values)) {

				foreach((array)$pre_values as $dname => $dvalue) {
					$array[] = array('value'=>$dname, 'display'=>$dvalue);
				}

				$opt[] = "		Values: ".json_encode($array)."";
			}

			if(count($pre_values) == 1)
				$opt[] = "		ShowExistingValues: false";
			else if($value_mode == 'typein')
				$opt[] = "		ShowExistingValues: true";
		}
        if(!empty($attributes['hide-tag-values'])) {
            $opt[] = "			HideTagValues: true";
        }

        if(!empty($attributes['tag-prepend'])){

            if(StringUtils::strToBool($attributes['tag-prepend'])){
                $opt[] = "			TagPrepend: true";
            }else{
                $opt[] = "			TagPrepend: false";
            }
        }

		return $opt;
	}


}
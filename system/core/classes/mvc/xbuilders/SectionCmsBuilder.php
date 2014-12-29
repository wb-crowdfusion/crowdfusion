<?php
/**
 * SectionCmsBuilder
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
 * @version     $Id: SectionCmsBuilder.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SectionCmsBuilder
 *
 * @package     CrowdFusion
 */
class SectionCmsBuilder extends EditCmsBuilder
{
	protected $sectionType = null;

	protected function xmodule() {
		$this->parseChildren();

		$element = $this->globals['INPUT_ELEMENT'];
		$sectionType = $this->globals['INPUT_SECTIONTYPE'];

		if(empty($element))
			throw new Exception('XModule ['.$this->template->getName().'] is missing POST parameter [element]');

		$str = StringUtils::l("{% set DataSource %}node-sections{% end %}");
		$str .= StringUtils::l("{% begin contents %}");

		if(!empty($this->js)) {
			$str .= StringUtils::l('<script type="text/javascript">');
			$str .= StringUtils::l();
			foreach((array)$this->js as $line)
				$str .= StringUtils::l($line);
			$str .= StringUtils::l();
			$str .= StringUtils::l("document.sectionWidgets['{$sectionType}'].initializeSection(%TempSectionID%);");
			$str .= StringUtils::l('</script>');
		}

		if(!empty($this->xhtml)) {
			foreach((array)$this->xhtml as $line)
				$str .= StringUtils::l($line);
		}


		$str .= StringUtils::l("{% end %}");
		return $str;
	}

	protected function info() {
		$element = $this->globals['INPUT_ELEMENT'];
		if(empty($element))
			throw new Exception('XModule ['.$this->template->getName().'] is missing POST parameter [element]');

		$this->sectionType = $this->globals['INPUT_SECTIONTYPE'];

		$this->schema = $this->ElementService->getBySlug($element)->getSchema()->getSectionDef($this->sectionType);

	}

//	protected function wysiwyg() {
//		$id = '';
//		extract(array_merge(
//			array(
//				'id' => '',
//				'width' => 'full',
//				'height' => '150',
//				'toolbar' => 'minimal',
//				'resizable' => false,
//				'uploader' => true,
//				'upload_media_type' => 'web-image'
//				),
//				$this->_attributes()));
//
//
//		$resizable = json_decode($resizable);
//		$uploader = json_decode($uploader);
//
//		$schemafield = $this->schema->getByID($id);
//
//		if(!$schemafield->isField())
//			throw new Exception('Cannot create wysiwyg for tag or meta field');
//
//
//		$this->xhtml[] = "			<li class=\"input-{$width}-width field {$this->_fieldClasses($schemafield)}\">";
//		$this->xhtml[] = "				<label for=\"{$id}_%TempSectionID%\">{$schemafield->title}</label>";
//		$this->xhtml[] = "				<div><div id=\"editor_{$id}_%TempSectionID%\">";
//		$this->xhtml[] = "	<textarea id=\"{$id}_%TempSectionID%\" name=\"{$id}_%TempSectionID%\" cols=\"30\" rows=\"4\">%{$id}%</textarea>";
//		$this->xhtml[] = "				</div></div>";
//		$this->xhtml[] = "			</li>";
//
//		$this->js[] = "	document.sectionWidgets['{$this->sectionType}'].createEditor('%TempSectionID%', '{$id}', '#editor_{$id}_%TempSectionID%', '#{$id}_%TempSectionID%', { Toolbar: '{$toolbar}', Height: {$height}, AutoHeight: ".($resizable?"true":"false")."});";
//	}

	protected function tagwidget() {

		extract(array_merge(
			array(
				'id' => '',
				'width' => 'quarter',
                'class' => 'NodeTagWidget'
				),
				$this->_attributes()));

		if(empty($id))
			throw new Exception('Missing id attribute on tagwidget');

		$schemafield = $this->schema->getTagDef($id);

		$partial = $schemafield->Partial;

        $widgetOptions = "{\n".implode(",\n", $this->_buildWidgetOptions($schemafield))."\n
		}";
        
		$this->js[] = "	new {$class}(
			document.sectionWidgets['{$this->sectionType}']._getSectionByID('%TempSectionID%'),
			new TagPartial('{$partial->toString()}'),
			'#{$id}%TempSectionID%',
			{$widgetOptions}
		);";

		//$this->js[] = "	document.sectionWidgets['{$this->sectionType}'].createTagWidget('%TempSectionID%', '#{$id}%TempSectionID%', '{$element}-{$type}#{$role}', {$this->_buildWidgetOptions($schemafield)});";

		$this->xhtml[] = "			<li class=\"input-{$width}-width {$this->_fieldClasses($schemafield)}\">";
		$this->xhtml[] = "				<div id=\"{$id}%TempSectionID%\"></div>";
	}

//	protected function textbox() {
//
//		extract(array_merge(
//			array(
//				'id' => '',
//				'width' => 'quarter',
//				'maxlength' => '255'
//				),
//				$this->_attributes()));
//
//		if(empty($id))
//			throw new Exception('Missing id attribute on textbox');
//
//        if($id == 'Title') {
//            $fullid = 'SectionTitle';
//            $title = $id;
//            $fieldclasses = '';
//        } else {
//
//            $schemafield = $this->schema->getMetaDef($id);
//            $title = $schemafield->Title;
//            $fullid = "#{$id}";
//            $fieldclasses = $this->_fieldClasses($schemafield);
//        }
//
//
//		$this->xhtml[] = "			<li class=\"input-{$width}-width field {$fieldclasses}\">";
//		$this->xhtml[] = "				<label for=\"{$id}%TempSectionID%\">{$title}</label>";
//		$this->xhtml[] = "				<div>";
//		$this->xhtml[] = "					<input id=\"{$id}%TempSectionID%\" type=\"text\" maxlength=\"{$maxlength}\" value=\"%{$fullid}%\" name=\"{$fullid}\"/>";
//		$this->xhtml[] = "				</div>";
//	}

    protected function _buildID(array $attributes)
    {
        return $attributes['id']."%TempSectionID%";
    }
}
<?php
/**
 * EditCmsBuilder
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009-2011 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted.
 *
 * @package     CrowdFusion
 * @copyright   2009-2011 Crowd Fusion Inc.
 * @license     http://www.crowdfusion.com/licenses/enterprise CF Enterprise License
 * @version     $Id: EditCmsBuilder.php 850 2012-03-12 22:25:56Z eric.byers $
 */

class EditCmsBuilder extends AbstractCmsBuilder
{
    protected $Events;
    protected $ElementService;
    protected $Permissions;

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    public function setElementService($ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function setPermissions(PermissionsInterface $Permissions)
    {
        $this->Permissions = $Permissions;
    }

    public function setNodePermissions(NodePermissions $NodePermissions)
    {
        $this->NodePermissions = $NodePermissions;
    }

    private static $printedFieldClasses = false;

    protected $attributes = array();

    protected function _xmodule() {

        $str = StringUtils::l("{% begin contents %}");

        if(!empty($this->js)) {
            $str .= StringUtils::l('<script type="text/javascript">');
            $str .= StringUtils::l();

            if(!self::$printedFieldClasses) {
                $str .= StringUtils::l($this->_buildFieldClasses());
                self::$printedFieldClasses = true;
            }
            $str .= StringUtils::l();
            $str .= StringUtils::l('    $(document).ready(function() {');
            $str .= StringUtils::l();
            foreach((array)$this->js as $line)
                $str .= StringUtils::l($line);
            $str .= StringUtils::l();
            $str .= StringUtils::l('    });');
            $str .= StringUtils::l();
            $str .= StringUtils::l('</script>');
        }

        if (!empty($this->xhtml)) {
            foreach((array)$this->xhtml as $line)
                $str .= StringUtils::l($line);
        }

        $str .= StringUtils::l("{% end %}");

        return $str;
    }

    protected function info() {

        $element = $this->globals['INPUT_ELEMENT'];
        if(empty($element))
            throw new Exception('XModule ['.$this->module['name'].'] is missing parameter [element]');

        $this->schema = $this->ElementService->getBySlug($element)->getSchema();
        if(empty($this->schema))
            throw new Exception('XModule ['.$this->module['name'].'] was unable to load schema for element ['.$element.']');
    }


    protected function _buildFieldClasses() {

        if(!empty($this->schema->class_defs)) {

            $classes = array();
            foreach($this->schema->class_defs->children() as $class) {
                $classes[(string)$class->attribute('id')] = (string)$class;
            }

            return '    document.fieldclasses = '.json_encode($classes).';';
        }
    }

    protected $nestID;
    protected $savedJS = array();
    protected $savedXHTML = array();

    protected function permit() {
        $attributes = array_merge(
            array(
                'view_perms' => null,
                'node_view_perms' => null
                ),
                $this->_attributes());
        extract($attributes);

        $this->attributes = $attributes;

        ++$this->nestID;

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
        {
            $this->savedJS[$this->nestID] = array_merge(array(), $this->js);
            $this->savedXHTML[$this->nestID] = array_merge(array(), $this->xhtml);

            $this->js = array();
            $this->xhtml = array();
        }
    }

    protected function _permit() {

        if(isset($this->savedJS[$this->nestID]))
        {
            $this->js = $this->savedJS[$this->nestID];
            $this->xhtml = $this->savedXHTML[$this->nestID];
        }

        --$this->nestID;
    }

    protected function title() {
        $this->xhtml[] = "\t\t<h3>".$this->_text()."</h3>";
    }

    protected function wysiwyg() {

        $attributes = array_merge(
            array(
                'id' => '',
                'edit_perms' => null,
                'view_perms' => null,
                'node_edit_perms' => null,
                'node_view_perms' => null,
                ),
                $this->_attributes());
        extract($attributes);

        $this->attributes = $attributes;

        if(empty($id))
            throw new Exception('Missing id attribute on wysiwyg');

        if(substr($id,0,1) != '#')
            throw new Exception('The id attribute must be a valid role beginning with #');

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $id = ltrim($id, '#');

        $schemafield = $this->schema->getMetaDef($id);

        $uid = $this->_generateUniqueID($id);

        $fullid = "#{$id}";
        $fulluid = "#{$uid}";

        $this->xhtml[] = "          <li class=\"input-full-width field wysiwyg {$this->_fieldClasses($schemafield)}\">";
        $this->xhtml[] = "              <label for=\"{$uid}\">{$schemafield->Title}</label>";
        if($this->__hasPermission($edit_perms) && $this->__hasNodePermission($node_edit_perms)) {
            $this->xhtml[] = "              <div><div id=\"{$uid}-editor\">";
            $this->xhtml[] = "                  <textarea id=\"{$uid}\" name=\"{$fulluid}\">%{$fullid}%</textarea>";
            $this->xhtml[] = "              </div></div>";

            $t = new Transport();
            $t->String = '';
            $this->Events->trigger('render-wysiwyg-js', $t, $uid,$fulluid, $attributes);
            $this->js[] = $t->String;

            $t = new Transport();
            $t->String = '';
            $this->Events->trigger('render-wysiwyg-js-second-pass', $t, $uid,$fulluid, $attributes);
            $this->js[] = $t->String;

            $t = new Transport();
            $t->String = '';
            $this->Events->trigger('render-wysiwyg-xhtml', $t, $uid,$fulluid, $attributes);
            $this->xhtml[] = $t->String;

        } else
            $this->xhtml[] = "                  <div><p class=\"read-only\">%{$fulluid}%</p></div>";
    }

    protected function _wysiwyg() {
        $this->__closeLI();
    }

    protected function datewidget() {
        extract($attributes = array_merge(
            array(
                'id' => '',
                'width' => 'quarter',
                'edit_perms' => null,
                'view_perms' => null,
                'node_edit_perms' => null,
                'node_view_perms' => null,
                ),
                $this->_attributes()));

        $this->attributes = $attributes;

        if(empty($id))
            throw new Exception('Missing id attribute on datewidget');

        if(substr($id,0,1) != '#')
            throw new Exception('The id attribute must be a valid role beginning with #');

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $id = ltrim($id, '#');

        $schemafield = $this->schema->getMetaDef($id);

        $vArray = $schemafield->Validation->getValidationArray();
        if(!empty($vArray['dateonly']) && $vArray['dateonly'] == 'true')
            $attributes['dateOnly'] = true;

        $uid = $this->_generateUniqueID($id);

        $fullid = "#{$id}";
        $fulluid = "#{$uid}";

        $this->xhtml[] = "          <li class=\"input-{$width}-width field {$this->_fieldClasses($schemafield)}\">";
        $this->xhtml[] = "              <label for=\"{$uid}\">{$schemafield->Title}</label>";
        if($this->__hasPermission($edit_perms) && $this->__hasNodePermission($node_edit_perms)) {
            $this->xhtml[] = "              <div id=\"{$uid}-holder\">";
            $this->xhtml[] = "                  <input id=\"{$uid}\" type=\"text\" value=\"{% filter date?value=Data:{$fullid}&format=Y-m-d H:i:s %}\" name=\"{$fulluid}\" class=\"datewidget\"/>";
            $this->xhtml[] = "              </div>";
            $this->xhtml[] = "              <script type=\"text/javascript\">";
            $this->xhtml[] = "                  new DateWidget('{$uid}', '{% filter date?value=Data:{$fullid}&format=Y-m-d %}', '{% filter date?value=Data:{$fullid}&format=g:i A %}', ".JSONUtils::encode($attributes).");";
            $this->xhtml[] = "              </script>";
        } else
            $this->xhtml[] = "                  <div><p class=\"read-only\">{% filter date?value=Data:{$fullid}&format=Y-m-d %}</p></div>";

    }

    protected function _datewidget() {
        $this->__closeLI();
    }

    protected function tagwidget() {
        $attributes = array_merge(
            array(
                'id' => '',
                'width' => 'quarter',
                'class' => 'NodeTagWidget',
                'partial' => '',
                'edit_perms' => null,
                'view_perms' => null,
                'node_edit_perms' => null,
                'node_view_perms' => null,
                ),
                $this->_attributes());
        extract($attributes);

        $this->attributes = $attributes;

        if(empty($id))
            throw new Exception('Missing id attribute on tagwidget');

        if(substr($id,0,1) != '#')
            throw new Exception('The id attribute must be a valid role beginning with #');

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $id = ltrim($id, '#');

        $schemafield = $this->schema->getTagDef($id);

        if(empty($partial))
            $partial = $schemafield->Partial->toString();

        $widgetOptions = $this->_buildWidgetOptions($schemafield,$attributes);

        $widgetOptions[] = "ReadOnly: " . ($this->__hasPermission($edit_perms) && $this->__hasNodePermission($node_edit_perms) ? "false" : "true");

        $widgetOptions = "{\n".implode(",\n", $widgetOptions)."\n}";

        $uid = $this->_generateUniqueID($id);

        $taggableRecord = 'taggableRecord';
        if (array_key_exists('uid', $this->attributes))
            $taggableRecord = 'uniqueTaggableRecord'.$this->attributes['uid'];

        $this->js[] = " new {$class}(
            document.{$taggableRecord},
            new TagPartial('{$partial}'),
            '#{$uid}',
            {$widgetOptions}
        );";

        $this->xhtml[] = "          <li class=\"input-{$width}-width field {$this->_fieldClasses($schemafield)}\">";
        $this->xhtml[] = "              <div id=\"{$uid}\"></div>";
    }

    protected function _tagwidget() {
        $this->__closeLI();
    }

    protected function textarea() {

        extract($attributes = array_merge(
            array(
                'id' => '',
                'rows' => '4',
                'width' => 'quarter',
                'edit_perms' => null,
                'view_perms' => null,
                'node_edit_perms' => null,
                'node_view_perms' => null,
                ),
                $this->_attributes()));

        $this->attributes = $attributes;

        if(empty($id))
            throw new Exception('Missing id attribute on textbox');

        if(substr($id,0,1) != '#')
            throw new Exception('The id attribute must be a valid role beginning with #');

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $id = ltrim($id, '#');

        $schemafield = $this->schema->getMetaDef($id);

        $uid = $this->_generateUniqueID($id);

        $fullid = "#{$id}";
        $fulluid = "#{$uid}";

        $this->xhtml[] = "          <li class=\"input-{$width}-width field {$this->_fieldClasses($schemafield)}\">";
        $this->xhtml[] = "              <label for=\"{$uid}\">{$schemafield->Title}</label>";
        $this->xhtml[] = "              <div>";
        if($this->__hasPermission($edit_perms) && $this->__hasNodePermission($node_edit_perms))
            $this->xhtml[] = "                  <textarea id=\"{$uid}\" rows=\"{$rows}\" name=\"{$fulluid}\">%{$fullid}%</textarea>";
        else
            $this->xhtml[] = "                  <p class=\"read-only\">%{$fullid}%</p>";
        $this->xhtml[] = "              </div>";
    }

    protected function _textarea() {
        $this->__closeLI();
    }

    protected function textbox() {

        $attributes = array_merge(
            array(
                'id' => '',
                'width' => 'quarter',
                'maxlength' => '255',
                'amazonsearch' => false,
                'wordcount' => false,
                'charcount' => false,
                'newwindow' => false,
                'edit_perms' => null,
                'view_perms' => null,
                'node_edit_perms' => null,
                'node_view_perms' => null,
                ),
                $this->_attributes());
        extract($attributes);

        $this->attributes = $attributes;

        if(empty($id))
            throw new Exception('Missing id attribute on textbox');

        if(substr($id,0,1) != '#')
            throw new Exception('The id attribute must be a valid role beginning with #');

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $id = ltrim($id, '#');

        $schemafield = $this->schema->getMetaDef($id);

        $validation = $schemafield->getValidation()->getValidationArray();
        if (in_array($validation['datatype'], explode('|', 'string|slug|slugwithslash|url|html|email')) && in_array('max', $validation))
            $maxlength = $validation['max'];


        $uid = $this->_generateUniqueID($id);

        $fullid = "#{$id}";
        $fulluid = "#{$uid}";

        $this->xhtml[] = "          <li class=\"input-{$width}-width field {$this->_fieldClasses($schemafield)}\">";
        $this->xhtml[] = "              <label for=\"{$uid}\">{$schemafield->Title}</label>";
        $this->xhtml[] = "              <div>";
        if($this->__hasPermission($edit_perms) && $this->__hasNodePermission($node_edit_perms))
            $this->xhtml[] = "                  <input id=\"{$uid}\" type=\"text\" maxlength=\"{$maxlength}\" name=\"{$fulluid}\" value=\"%{$fullid}%\"/>";
        else
            $this->xhtml[] = "                  <p class=\"read-only\">%{$fullid}%</p>";
        $this->xhtml[] = "              </div>";

        if($amazonsearch && json_decode($amazonsearch) == true) {
            $this->js[] = "$('#{$uid}').addAmazonSearch();";
        }

        if($newwindow && json_decode($newwindow) == true) {
            $this->js[] = "$('#{$uid}').addNewWindow();";
        }

        if($wordcount && json_decode($wordcount) == true) {
            $this->js[] = "$('#{$uid}').addWordCount();";
        }

        if($charcount && json_decode($charcount) == true) {
            $this->js[] = "$('#{$uid}').addCharCount();";
        }
    }

    protected function _textbox() {
        $this->__closeLI();
    }

    protected function __hasPermission($perms) {
        if(!empty($perms)) {
            $perms = explode(',',$perms);
            foreach($perms as $perm) {
                if(!$this->Permissions->checkPermission($perm)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function __hasNodePermission($perms) {
        if(!empty($perms)) {
            $perms = explode(',',$perms);
            foreach($perms as $perm) {
                if(!$this->NodePermissions->check($perm, $this->getParameter('NodeRef'))) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function __closeLI() {
        $attributes = array_merge( array( 'view_perms' => null, 'node_view_perms' => null ), $this->_attributes());
        extract($attributes);

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $this->xhtml[] = "          </li>";
    }

    protected function checkbox() {

        extract($attributes = array_merge(
            array(
                'id' => '',
                'width' => 'quarter',
                'edit_perms' => null,
                'view_perms' => null,
                'node_edit_perms' => null,
                'node_view_perms' => null,
                ),
                $this->_attributes()));

        $this->attributes = $attributes;

        if(empty($id))
            throw new Exception('Missing id attribute on textbox');

        if(substr($id,0,1) != '#')
            throw new Exception('The id attribute must be a valid role beginning with #');

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $id = ltrim($id, '#');

        $schemafield = $this->schema->getMetaDef($id);

        $uid = $this->_generateUniqueID($id);

        $fullid = "#{$id}";
        $fulluid = "#{$uid}";

        $this->xhtml[] = "          <li class=\"input-{$width}-width checkbox field {$this->_fieldClasses($schemafield)}\">";
        $this->xhtml[] = "              <label>{$schemafield->Title}?</label>";
        $this->xhtml[] = "              <div>";
        if($this->__hasPermission($edit_perms) && $this->__hasNodePermission($node_edit_perms)) {
            $this->xhtml[] = "                  <input id=\"{$uid}\" type=\"checkbox\" name=\"{$fulluid}\" {% if Data:{$fullid} %}checked=\"checked\"{% endif %} value=\"1\" /> <label for=\"{$uid}\">Yes</label>";
            $this->xhtml[] = "                  <input type=\"hidden\" name=\"_{$fulluid}\" value=\"0\" />";
        } else
            $this->xhtml[] = "                  <p class=\"read-only\">{% if Data:{$fullid} %}Yes{% else %}No{% endif %}</p>";
        $this->xhtml[] = "              </div>";
    }

    protected function _checkbox() {
        $this->__closeLI();
    }

    protected function display() {

        extract($attributes = array_merge(
            array(
                'id' => '',
                'width' => 'quarter',
                'view_perms' => null,
                'node_view_perms' => null,
                'preformatted' => null,
                ),
                $this->_attributes()));

        $this->attributes = $attributes;

        if(empty($id))
            throw new Exception('Missing id attribute on display');

        if(substr($id,0,1) != '#')
            throw new Exception('The id attribute must be a valid role beginning with #');

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $id = ltrim($id, '#');

        $isTag = false;
        if($this->schema->hasMetaDef($id)) {
            $schemafield = $this->schema->getMetaDef($id);
        } else {
            $schemafield = $this->schema->getTagDef($id);
            $isTag = true;
        }

        $validationArray = $schemafield->Validation->getValidationArray();

        $fullid = "#{$id}";

        $this->xhtml[] = "          <li class=\"input-{$width}-width field {$this->_fieldClasses($schemafield)} display\">";
        $this->xhtml[] = "              <label for=\"{$id}\">{$schemafield->Title}</label>";
        $this->xhtml[] = "              <div>";
        if($validationArray['datatype'] == 'url') {
            $this->xhtml[] = "                  <a href=\"%{$fullid}%\" target=\"new\">%{$fullid}%</a>";
        } elseif($validationArray['datatype'] == 'date') {
            $this->xhtml[] = "                  {% filter date?value=Data:{$fullid}&format=M j, Y g:i A T&nonbreaking=true %}";
        } elseif($validationArray['datatype'] == 'boolean') {
            $this->xhtml[] = "                  {% if Data:{$fullid} %}YES{% else %}NO{% endif %}";
        } else {
            // If this is a tag, it will be an array, so we use implode to convert it to a string.
            if ($isTag) {
                $this->xhtml[] = "                  {% filter display-implode?value=Data:{$fullid}&glue=<br> %}";
            } else {
                if ($attributes['preformatted']) {
                    $this->xhtml[] = "                  <pre>%{$fullid}%</pre>";
                } else {
                    $this->xhtml[] = "                  %{$fullid}%";
                }
            }
        }
        $this->xhtml[] = "              </div>";
    }

    protected function _display() {
        $this->__closeLI();
    }

    protected function image() {
        extract($attributes = array_merge(
            array(
                'id' => '',
                'width' => 'quarter',
                'view_perms' => null,
                'node_view_perms' => null
                ),
                $this->_attributes()));

        $this->attributes = $attributes;

        if(empty($id))
            throw new Exception('Missing id attribute on image');

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $fullid = $id;

        $this->xhtml[] = "          <li class=\"input-{$width}-width field \">";
        $this->xhtml[] = "              <div>";
        $this->xhtml[] = "                  <a href=\"%{$fullid}%\" title=\"Open image in new window\" target=\"_blank\"><image border=\"0\" src=\"%{$fullid}%\"/></a>";
        $this->xhtml[] = "              </div>";
    }

    protected function _image() {
        $this->__closeLI();
    }


    protected function link() {
        extract($attributes = array_merge(
            array(
                'id' => '',
                'width' => 'quarter',
                'view_perms' => null,
                'node_view_perms' => null
                ),
                $this->_attributes()));

        $this->attributes = $attributes;

        if(empty($id))
            throw new Exception('Missing id attribute on image');

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $fullid = $id;

        $this->xhtml[] = "          <li class=\"input-{$width}-width field \">";
        $this->xhtml[] = "              <div>";
        $this->xhtml[] = "                  <a href=\"%{$fullid}%\" title=\"Open in new window\" target=\"_blank\">%{$fullid}%</a>";
        $this->xhtml[] = "              </div>";
    }

    protected function _link() {
        $this->__closeLI();
    }

    protected function dropdown() {

        $attributes = array_merge(
            array(
                'id' => '',
                'width' => 'quarter',
                'edit_perms' => null,
                'view_perms' => null,
                'node_edit_perms' => null,
                'node_view_perms' => null,
                ),
                $this->_attributes());
        extract($attributes);

        if(empty($id))
            throw new Exception('Missing id attribute on dropdown');

        if(substr($id,0,1) != '#')
            throw new Exception('The id attribute must be a valid role beginning with #');

        if(!$this->__hasPermission($view_perms) || !$this->__hasNodePermission($node_view_perms))
            return;

        $id = ltrim($id, '#');

        $schemafield = $this->schema->getMetaDef($id);

        if(!$this->xml->isEmptyElement){
            require_once PATH_SYSTEM .'/vendors/Xml2Assoc.php';
            $children = (array)Xml2Assoc::parseXml($this->xml, true);
        }else
            $children = array();

        $uid = $this->_generateUniqueID($id);

        $fullid = "#{$id}";
        $fulluid = "#{$uid}";

        $this->xhtml[] = "          <li class=\"input-{$width}-width field {$this->_fieldClasses($schemafield)}\">";
        $this->xhtml[] = "              <label for=\"{$uid}\">{$schemafield->Title}</label>";
        $this->xhtml[] = "              <div>";
        if($this->__hasPermission($edit_perms) && $this->__hasNodePermission($node_edit_perms)) {
            $this->xhtml[] = "                  <select name=\"{$fulluid}\" id=\"{$uid}\">";

            if (!empty($children) && array_key_exists('option', $children)) {
                foreach( $children['option'] as $raw_option ) {
                    $selected = '';

                    if (is_array($raw_option)) {
                        if (array_key_exists('selected', $raw_option) &&
                        ($raw_option['selected'] == 'true' || $raw_option['selected'] == 'yes')) {
                            $selected = 'selected';
                        }

                        $option = array_shift($raw_option);
                        $value  = $raw_option['value'];
                    } else {
                        $option = $value = $raw_option;
                    }

                    $this->xhtml[] = "                          <option value='{$value}' {% if Data:{$fullid} eq '{$value}' %}selected{% else %}{% if !Data:{$fullid} %}{$selected}{% endif %}{% endif %}>{$option}</option>";
                }
            }

            $this->xhtml[] = "                  </select>";
        } else
            $this->xhtml[] = "                  <p class=\"read-only\">%{$fullid}%</p>";


        $this->xhtml[] = "              </div>";
        $this->__closeLI();
    }

    protected function _generateUniqueID($id) {
        if (array_key_exists('uid', $this->attributes))
            return $id.'-uid'.$this->attributes['uid'];
        else
            return $id;
    }
}

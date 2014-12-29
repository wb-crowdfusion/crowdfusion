<?php
/**
 * ListfilterFilterer
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
 * @version     $Id: ListfilterFilterer.php 805 2012-02-10 15:51:19Z ryans $
 */

/**
 * ListfilterFilterer
 *
 * @package     CrowdFusion
 */
class ListfilterFilterer extends AbstractFilterer
{
    /*
     * Tag widget filter generation methods.
     *
     * TODO: * Support multiple choices.
     *       * Accept custom search parameters
     *       * Test with mixed in/out tags
     */
    private $tagWidgets = array();
    private $directions = array();

    /*
     *
     */
    public function tagWidgetInit()
    {
        $this->tagWidgets = array();

        $direction = $this->getParameter('Direction');
        if ($direction) {
            if ($direction != 'In' && $direction != 'Out') {
                throw new FiltererException(
                    'Tag direction must be either "Out" or "In"'
                );
            }
            $this->directions = array($direction);
        } else {
            $this->directions = array('Out', 'In');
        }

        $output = '';
        foreach ($this->directions as $dir) {
            $output .= '<input type="hidden"'
                     .       ' id="' . $dir . 'TagsFilter"'
                     .       ' name="filter[' . $dir . 'Tags.exist]"'
                     .       ' value="" />';
        }

        return $output;
    }

    /*
     *
     */
    public function tagWidget()
    {
        $id        = $this->getRequiredParameter('Id');
        $partial   = $this->getRequiredParameter('Partial');
        $direction = $this->getRequiredParameter('Direction');

        list($searchElement, $role) = explode('#', $partial);

        $options = array_merge(
            array(
                'AllowMultiple'        => false,
                'Label'                => StringUtils::humanize($id),
                'ActivateButtonLabel'  => StringUtils::humanize($id),
                'AllowClearChosenList' => false,
                'AllowRemoveUndo'      => false,
                'TagDirection'         => strtolower($direction),
                'SearchParameters'     => array(
                    'Elements.in' => $searchElement
                )
            ),
            $this->params
        );

        unset($options['Id']);
        unset($options['Partial']);
        unset($options['Direction']);

        $this->tagWidgets[] = compact('id',
                                      'partial',
                                      'direction',
                                      'options',
                                      'role');

        return '<div id="' . $id . '-filter"></div>';
    }

    /*
     *
     */
    public function tagWidgetScript()
    {
        /*
         * Assemble chunks of js for each tag
         */
        $widgetInstantiations = '';
        $tagUpdateEvents      = '';
        $domChangeEvents      = array('Out' => '', 'In' => '');

        foreach ($this->tagWidgets as $tagArr) {
            extract($tagArr);
            $optionsJson = JSONUtils::encode($options);
            $directionLc = strtolower($direction);

            //
            //
            $widgetInstantiations .= <<<EOT

    document.${id}FilterPartial = new TagPartial('$partial');
    new NodeTagWidget(
        document.tempRecord,
        document.${id}FilterPartial,
        '#$id-filter',
        $optionsJson
    );
EOT;
            //
            //
            $tagUpdateEvents .= <<<EOT

        if (
            typeof this.get${direction}Tags(
                document.${id}FilterPartial
            )[0] != 'undefined')
        {
            tags.$direction.push(this.get${direction}Tags(
                                document.${id}FilterPartial)[0].toString());
        }
EOT;
            //
            //
            $domChangeEvents[$direction] .= <<<EOT

        document.tempRecord.removeTags('$directionLc',
                                       document.${id}FilterPartial);
EOT;
        }


        /*
         * Assemble chunks of js for each tag direction
         */
        $tagsInitializeArray = array();
        $fireFilter          = '';
        $domChangeFunctions  = '';
        $onloadTriggers      = '';

        foreach ($this->directions as $direction) {
            $tagsInitializeArray[] = "'$direction' : []";
            $directionLc = strtolower($direction);

            //
            //
            $fireFilter .= <<<EOT

        List.filter($('#${direction}TagsFilter'),
                    '${direction}Tags.exist',
                    tags.$direction.join(','));
EOT;
            //
            //
            $domChangeFunctions .= <<<EOT

    $('#${direction}TagsFilter').bind('init', function() {

        document.tempRecord.init${direction}Mode = true;

        var tags = $(this).val();

        {$domChangeEvents[$direction]}

        if(tags != '') {
            tags = tags.split(',');
            $(tags).each(function(i,tag){
                document.tempRecord.add${direction}Tag(
                    new Tag(new TagPartial(tag))
                );
            });
        }

        document.tempRecord.init${direction}Mode = false;
    });
EOT;
            //
            //
            $onloadTriggers .= <<<EOT

        $('#${direction}TagsFilter').trigger('init');
EOT;

        } // each direction
        $tagsInitialize = implode(',', $tagsInitializeArray);





        /*
         * Return script tag
         */
        return <<<EOT

    <script language="JavaScript" type="text/javascript">

    document.tempRecord = new NodeObject({});
$widgetInstantiations

    document.tempRecord.bind(Taggable.EVENTS.TAGS_UPDATED,function(){

        if(document.tempRecord.init${direction}Mode)
            return;

        var tags = { $tagsInitialize };

$tagUpdateEvents

$fireFilter
    });

$domChangeFunctions

    $(document).ready(function() {
        document.tempRecord.init${direction}Mode = true; // this need to be tur to persist intags
        document.tempRecord.init();
$onloadTriggers
    });

    </script>
EOT;

    }

    /*
     *
     */
    public function clearTagWidgetJs()
    {
        //IMPORTANT: THIS MUST BE CALLED BEFORE List.clearFilters();
        return "$('#OutTagsFilter').val('').trigger('init');$('#InTagsFilter').val('').trigger('init');";
    }
}

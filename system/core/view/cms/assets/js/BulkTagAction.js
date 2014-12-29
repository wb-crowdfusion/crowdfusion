var BulkTagAction = function(tagPartial,tagWidgetOptions){

    var tagPartialEscaped = tagPartial.toString().replace('@','_').replace('#','_');
    this.DOM = {};
    this.DOM.tagWidgetPanel = $('<div class="bulk-action-add-tag-panel"><div id="bulk-action-add-tag-widget-' + tagPartialEscaped + '"></div></div>');
    $('body').append(this.DOM.tagWidgetPanel);

    this.node = new NodeObject({});
    this.tagPartial = tagPartial;
    this.tagWidget = new NodeTagWidget(
        this.node,
        tagPartial,
        '#bulk-action-add-tag-widget-' + tagPartialEscaped,
        typeof tagWidgetOptions == 'object' ? tagWidgetOptions : {}
    );
};

BulkTagAction.prototype = {
    init : function() {
        this.node.init();
    },
    choose : function() {
        this.DOM.tagWidgetPanel.fadeIn();
        this.tagWidget.clearChosenItems();
    },
    clear : function() {
        this.DOM.tagWidgetPanel.fadeOut('fast');
    },
    confirm : function() {
        var tags = this.node.getOutTags(this.tagPartial);
        var tagStrings = [];
        $.each(tags,function(i,e){
            tagStrings.push(e.toString());
        });
        return {
            Tags : tagStrings.join(',')
        }
    }
};
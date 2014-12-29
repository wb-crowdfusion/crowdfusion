var FieldsetExpander = (function() {

    var init = function() {

        $('fieldset.collapsable').each(function(i,e){
            var fieldset = $(e);

            var h3 = $('h3:eq(0)',fieldset);

            var h3Slug = SlugUtils.createSlug(h3.text(), false);

            var open = $('<a id="expand-'+h3Slug+'" href="#">[Expand]</a>');
            var close = $('<a id="collapse-'+h3Slug+'" href="#">[Collapse]</a>');

            //cookies
            var ElementSlug = null;
            if(document.taggableRecord) {
                ElementSlug = document.taggableRecord.Element.Slug;
            }

            open.click(function(event){
                event.preventDefault();
                fieldset.children().not('h3').show();
                close.show();
                open.hide();

                if(ElementSlug != null)
                    $.cookie('FieldsetExpander.'+ElementSlug+'.'+h3.text(),'open',{path:'/'});
            });

            close.click(function(event){
                event.preventDefault();
                fieldset.children().not('h3').hide();
                close.hide();
                open.show();

                if(ElementSlug != null)
                    $.cookie('FieldsetExpander.'+ElementSlug+'.'+h3.text(),'closed',{path:'/'});
            });

            h3.append(open).append(close);

            if(fieldset.hasClass('closed')) {
                fieldset.children().not('h3').hide();
                close.hide();
            }
            else if(fieldset.hasClass('open')) {
                open.hide();
            }

            if(ElementSlug != null) {
                var viewState = $.cookie('FieldsetExpander.'+ElementSlug+'.'+h3.text());

                if(viewState != null) {
                    if(viewState == 'open') {
                        fieldset.children().not('h3').show();
                        open.hide();
                        close.show();
                    } else {
                        fieldset.children().not('h3').hide();
                        open.show();
                        close.hide();
                    }
                }
            }
        });
    };

    return {
        init : init
    };

})();
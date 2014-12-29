var SimpleExpander = function(link_selector,options) {

    var opt = jQuery.extend({

        scroll_to : false

    }, options || {});


    $(link_selector).each(function(i,link){
        link = $(link);
        var div = link.next();

        link.click(function(){
            div.toggle();
            List.adjustExpandedHeight();
            if(link.hasClass('expanded')) {
                div.removeClass('expanded');
                link.removeClass('expanded');
            } else {
                div.addClass('expanded');
                link.addClass('expanded');
                if(opt.scroll_to)
                    $('#app-content').scrollTo($(link));
            }
        });
    });
};

SimpleExpander.prototype = {


};
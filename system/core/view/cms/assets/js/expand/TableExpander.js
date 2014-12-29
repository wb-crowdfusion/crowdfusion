var TableExpander = function(table_id,options) {

    var opt = jQuery.extend({

        type : 'iframe',
        scroll_to : true

    }, options || {});


    var table = $(table_id);

    $('tbody > tr',table).each(function(i,tr){
        tr = $(tr);

        var id = tr.attr('id').match(/expand-row-(\d+)/)[1];

        $('a',tr).click(function(event){
            event.stopPropagation();
        });

        if(opt.type === 'iframe') {
            tr.after(
                $('<tr class="iframe" style="display:none" id="expand-contents-'+id+'"><td colspan="4"><div class="iframe"><iframe src="/webnews-iframe/'+id+'/" id="expand-iframe-'+id+'"/></div></td></tr>')
            );
        }

        tr.click(function(event){
            var contents = $('#expand-contents-'+id);

            if(contents.css('display') === 'none') {
                $('#expand-row-'+id).addClass('expanded');
                if(opt.scroll_to)
                    $('#app-content').scrollTo($('#expand-row-'+id));
            } else {
                $('#expand-row-'+id).removeClass('expanded');
            }
            contents.toggle();
            List.adjustExpandedHeight();
        });
    });
};

TableExpander.prototype = {


};
if($.browser.msie) {

	var resizeDelay;

	$(document).ready(function() {

		$(window).bind('resize', function(){
			clearTimeout(resizeDelay);
			resizeDelay=setTimeout(checkDims, 50);
		});

		// Fix element dimensions
		checkDims();

	});

	function checkDims(){

	 	$('#app-main').css('width', (document.documentElement.clientWidth - 200 - 11) + 'px');
	 	$('#app-main').css('height', (document.documentElement.clientHeight - 95 - 5) + 'px');

	 	$('#app-content').css('width', (document.documentElement.clientWidth - 200 - 18) + 'px');

        var delta = 0;

        if($('#bulk-action-toolbar').length)
            delta = 30;

	 	$('#app-content').css('height', (document.documentElement.clientHeight - 175 - 15 - delta) + 'px');

	 	//$('#app-footer ').css('width', (document.body.clientWidth - 30) + 'px');

		var appFooter = $('#app-main-footer');
		if(appFooter.length > 0) {
			var appContent = $('#app-content');
			var rowHeight = appFooter.outerHeight();
			var contentHeight = appContent.outerHeight();
			appContent.css({height: (contentHeight - rowHeight)+'px'});
			appFooter.css({width: parseInt(appContent.css('width'))+'px'});
		}

        //IE likes to mangle tables
        setTimeout(function(){
            if(!$('body').hasClass('list')) return;
            List.redrawHeadings();
        },2000);

        if ($.browser.version < 8) {
            $('a.dp-now').html('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
        }
	}

    //IE doesn't support the latest JS functions
    Array.prototype.some = function(e) {

        for(var i=0;i<this.length;i++) {
            if(this[i] == e)
                return true;
        }

        return false;
    };
};
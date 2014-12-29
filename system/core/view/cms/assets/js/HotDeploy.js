var HotDeploy = function() {

	/*
	 * VARIABLES
	 */
    var div = null;
    var a = null;
    var span = null;
    var currentRequest = null;

	/*
	 * INITIALIZATION
	 */
	var init = function () {

        div = $('#hot-deploy');
        span = $('span',div);
        a = $('a',div);

        a.click(function(event){
            event.preventDefault();
            refresh();
        });

	};

	var refresh = function() {
        if(currentRequest != null) return;

        div.addClass('wait');
        span.text('Please Wait...');
        a.hide();

        if(div.hasClass('off')) {

            div.removeClass('off');

            currentRequest = $.ajax({
                type: 'GET',
                url: 'hotdeploy/refresh/',
                complete: function(res, status) {
                    if (status == "success" || status == "notmodified" ) {
                        div.removeClass('wait').addClass('off');
                        span.text('Pre-Compiled Mode');
                        a.show();
                    }
                    currentRequest = null;
                },
                error: function(req, err) {
                    console_log(req.responseText.stripHtml());
                    $(document).trigger('show_error', 'Error refreshing context');
                }
            });

        }
	};

	$(document).ready( function() {
		init();
	});

}();

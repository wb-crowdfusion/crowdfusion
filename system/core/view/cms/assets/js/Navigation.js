var Navigation = function() {

    $(document).ready(function() {

        // app-content
        var appFooter = $('#app-main-footer');
        if(appFooter.length > 0 && !$.browser.msie) {
            var appContent = $('#app-content');

            var rowHeight = appFooter.outerHeight();
            var bottom = parseInt(appContent.css('bottom'));
            appContent.css({bottom: (bottom + rowHeight)+'px'});
        }

        $(document).bind('show_loading', function() {
            $('#loading').show();
        }).bind('hide_loading', function() {
            $('#loading').hide();
        });

        document.errorWasSet = false;

        $(document).bind('show_error', function(ev, param) {
            document.errorWasSet = true;
            if(param != '')
                $('#cms-error').text(param);
            else
                $('#cms-error').text('Unknown Error Occurred');
            $('#cms-error').show();
        }).bind('hide_error', function() {
            document.errorWasSet = false;
            $('#cms-error').hide();
        }).bind('hide_initial_error', function() {
            if(!document.errorWasSet)
                $('#cms-error').hide();
        });

        window.inputHasFocus           = false;
        document.madeChanges           = false;
        document.formSubmitting        = false;
        document.clickedCancelOnUnload = false;

        $(document).bind('form_changed', function() {
            document.madeChanges = true;
        });

        $(document).bind('widget_updated', function() {
            $(document).trigger('form_changed');
        });

        window.onbeforeunload = function() {
                $(document).trigger('alwaysbeforeunload');
                if(document.formSubmitting) return;
                $(document).trigger('beforeunload');
                if(document.madeChanges) {
                    document.clickedCancelOnUnload = true;
                    return "IMPORTANT: If you leave this page, you will lose your changes.";
                }
            };




        // navigation
        var navItems = [];
        $.each($('#primarynav li'),function(i, n) {
                n = $(n);
                n.hover( function() {
                        $.each(navItems,function(i, e) { e.removeClass('nav-active'); clearTimeout(e.navTimeout); });
                        navItems = [];
                        n.addClass('nav-active');
                },
                function() {
                        if(n.hasClass('parent')) {
                                n.navTimeout = setTimeout(function() { n.removeClass('nav-active'); }, 500);
                                navItems[navItems.length++] = n;
                        } else {
                                n.removeClass('nav-active');
                        }
                });
        });

        if($.browser.msie && $.browser.version < 7) {
            $('#primarynav ul').bgiframe();
        }

        // debug
        var debug = $('#debug');
        if(debug.length > 0) {
            var toggleDebug = $('<a id="debuglink" href="">debug</a>').click(function() {
                debug.toggle();
                return false;
            });
            $('#utilities p:eq(0)').append(' [').append(toggleDebug).append(']');
        }

    });

}();


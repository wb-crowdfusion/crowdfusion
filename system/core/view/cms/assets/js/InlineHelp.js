var InlineHelp = (function() {

    /* private attributes */
    var helpLink;
    var helpEnabled;
    var inlineHelp;
    var resizeDelay;
    var currentField;

    /* private methods */
    var init = function() {

        if(!$('body').hasClass('edit') || $('body').hasClass('signin')) return;

        //INITIALIZE HELP TOGGLE LINK & COOKIE

        helpLink = $('<a href="#" class="help-toggle"></a>');
        helpLink.click(function(event){
            _toggleHelp();
            event.preventDefault();
        });

        var enabledCookie = $.cookie('inline-help');
        if(enabledCookie !== 'true') {
            helpEnabled = false;
            helpLink.html('Off').addClass('help-off');
            $.cookie('inline-help','false',{path:'/'});
        } else {
            helpEnabled = true;
            helpLink.html('On');
        }

        $('a.help').after(helpLink);

        //CREATE MARKUP FOR HELP WIDGET
        inlineHelp =
            $('<div id="inline-help" style="display:none"></div>')
                .append($('<h1></h1>'))
                .append($('<a href="#" class="close">&times;</a>'))
                .append($('<div class="contents"></div>'))
                .append($('<div class="callout"></div>'));
        $('body').append(inlineHelp);

        //BIND EVENT HANDLER FOR HELP WIDGET CLOSE BUTTON
        $('a.close',inlineHelp).click(function(e){
            _hideHelp();
           e.preventDefault();
        });


        //BIND EVENT HANDLER TO SHOW HELP WIDGET TO ALL CHECKBOXES
        $('#app-content li > div > :checkbox').each(function(i,e){
            var field = $(e).parent().parent();
            field.hover(
                function(){
                    _showHelp(field);
                },
                function(){
                    _hideHelp();
                }
            );
        });

        //BIND EVENT HANDLER TO SHOW HELP WIDGET TO ALL WYSIWYG EDITORS
        $('#app-content li.wysiwyg').each(function(i,e){
            var field = $(e);
            field.hover(
                function(){
                    _showHelp(field);
                },
                function(){
                    _hideHelp();
                }
            );
        });

        //BIND EVENT HANDLER TO SHOW HELP WIDGET TO ALL INPUT BOXES
        $('#app-content li > div > :text').each(function(i,e){
            var field = $(e);
            field.focus(function(){
                _showHelp(field.parent().parent(),field.attr('name'));
            });
            field.blur(function(){
                _hideHelp();
            });
        });

        //BIND EVENT HANDLER TO SHOW HELP WIDGET TO ALL TEXTAREAS
        $('#app-content li > div > textarea').each(function(i,e){
            var field = $(e);
            field.focus(function(){
                _showHelp(field.parent().parent(),field.attr('name'));
            });
            field.blur(function(){
                _hideHelp();
            });
        });

        //BIND EVENT HANDLER TO SHOW HELP WIDGET TO ALL SELECTs
        $('#app-content li > div > select').each(function(i,e){
            var field = $(e);
            field.focus(function(){
                _showHelp(field.parent().parent());
            });
            field.blur(function(){
                _hideHelp();
            });
        });

        //BIND EVENT HANDLER TO SHOW HELP WIDGET TO ALL TAG WIDGETS
        $(document).bind(AbstractTagWidget.EVENTS.WIDGET_ACTIVATED,function(o,UUID,widget){
            _showHelp(widget.DOM.container.parent(),widget.tagPartial.TagRole);
        });

        //BIND EVENT HANDLER TO SHOW HELP WIDGET TO ALL TAG WIDGETS
        $(document).bind(AbstractTagWidget.EVENTS.WIDGET_DEACTIVATED,function(){
            _hideHelp();
        });

        $(document).bind(SectionWidget.EVENTS.SECTION_LOADED,function(t,secDiv){
            _bindSectionInputs(secDiv);
        });

        $(window).bind('resize', function() {
            clearTimeout(resizeDelay);
            resizeDelay = setTimeout(_resize, 100);
        });

        $('#app-content').scroll(function(){
            //keep the popup positioned over the input
            _positionHelp();
        });
    };

    var _resize = function() {

        if(inlineHelp.css('display') === 'none') return;

        _positionHelp();
    };

    var _bindSectionInputs = function(section) {

        $('li > div > :text',section).each(function(i,e){
            var field = $(e);
            field.focus(function(){
                _showHelp(field.parent().parent());
            });
            field.blur(function(){
                _hideHelp();
            });
        });

        //BIND EVENT HANDLER TO SHOW HELP WIDGET TO ALL SELECTs
        $('li > div > select',section).each(function(i,e){
            var field = $(e);
            field.focus(function(){
                _showHelp(field.parent().parent());
            });
            field.blur(function(){
                _hideHelp();
            });
        });

        //BIND EVENT HANDLER TO SHOW HELP WIDGET TO ALL WYSIWYG EDITORS
        $('li.wysiwyg',section).each(function(i,e){
			var field = $(e);
            field.hover(
                function(){
                    _showHelp(field);
                },
                function(){
                    _hideHelp();
                }
            );
        });

        //BIND EVENT HANDLER TO SHOW HELP WIDGET TO ALL CHECKBOXES
        $('li > div > :checkbox',section).each(function(i,e){
            var field = $(e).parent().parent();
            field.hover(
                function(){
                    _showHelp(field);
                },
                function(){
                    _hideHelp();
                }
            );
        });
    };

    var _showHelp = function(field,role) {

        if(!helpEnabled) return;

        inlineHelp.hide();
        currentField = field;

        if($('label',currentField).length == 0) return;

        var helpContents = $('div.help-contents',currentField);

        if(helpContents.text().length == 0 && typeof role == 'undefined') return;


        var contents = $('div.contents',inlineHelp);
        contents.empty();

        if(typeof role != 'undefined')
            contents.append($('<span class="role">'+(role.charAt(0)=='#'?role:'#'+role)+'</span>'));

        if(document.fieldclasses) {
            var classes = $('<ol></ol>');
            for(var cls in document.fieldclasses) {
                if(currentField.hasClass(cls))
                    classes.append($('<li>'+document.fieldclasses[cls]+'</li>'));
            }
            contents.append(classes);
        }
        contents.append(helpContents.html());
        $('h1',inlineHelp).html($('label',currentField)[0].firstChild.nodeValue+' Help');

        setTimeout(function(){
            _positionHelp();
            inlineHelp.fadeIn("fast");
        },500);

    };

    var _positionHelp = function() {

        if(typeof currentField == 'undefined')
            return;

        var offset = currentField.offset();

        var width = 293;
        var viewport = $(window).width();

        var adjustment = 0;
        if((width + offset.left) > viewport)
            adjustment = (width + offset.left) - viewport;

        $('div.callout',inlineHelp).css({
            left: 20 + adjustment
        });

        var appMain = $('#app-main').position();
        var appContent = $('#app-content').position();

        inlineHelp.css({
            top:  offset.top /*+ $('#app-content').scrollTop()*/ - 287 + appMain.top + appContent.top,
            left: (6 + offset.left - 210) - adjustment + appMain.left + appContent.left
        });

        //to put inline help back inside app content, remove the appMain and appContent adjustments and
        //re-enable the srollTop adjustment
    };

    var _toggleHelp = function() {
        var expires = new Date();
        expires.setDate(expires.getDate() + 365*30);

        if(helpLink.hasClass('help-off')) {
            helpLink.html('On').removeClass('help-off');
            helpEnabled = true;
            $.cookie('inline-help','true',{path:'/',expires:expires});
        } else {
            helpLink.html('Off').addClass('help-off');
            helpEnabled = false;
            $.cookie('inline-help','false',{path:'/',expires:expires});
        }
    };

    var _hideHelp = function() {
        inlineHelp.fadeOut("fast");
    };


    /* initialize singleton */
    $(document).ready( function() {
        init();
    });


    /* public methods */
    return {
        showHelp : _showHelp,
        hideHelp : _hideHelp
    };

})();

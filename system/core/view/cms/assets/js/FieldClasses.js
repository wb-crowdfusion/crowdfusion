var FieldClasses = (function() {

    /* private attributes */
    var t = this;
    var showSelectedClassesLink;
    var showAllFieldsLink;
    var fieldClassMenu;
    var fieldSet = null;
    var fieldsets = null;
    var callbacks = ['on','off'];


    /* private methods */
    var init = function() {
        if(!$('body').hasClass('edit') || $('body').hasClass('signin')) return;

        if(!document.fieldclasses) return;
        var numClasses = 0;
        for(var cls in document.fieldclasses) numClasses++;
        if(numClasses == 0) return;


        //CREATE MARKUP FOR FIELD CLASS FILTER
        showSelectedClassesLink = $('<a href="#">Only Selected</a>')
                        .click(function(event){
                            _showSelectedClasses();
                            event.preventDefault();
                        });

        showAllFieldsLink = $('<a href="#" class="selected">All</a>')
                        .click(function(event){
                            _showAllFields();
                            event.preventDefault();
                        });

        fieldClassMenu =
            $('<div id="field-class-menu"></div>')
                .append($('<h3></h3>')
                    .append($('<a href="#" title="Show/Hide">Completion Stages</a>')
                        .click(function(event){
                            _toggleMenu();
                            event.preventDefault();
                        })
                    )
                )
                .append($('<ul></ul>'))
                .append($('<div class="actions"><span>Show:</span>&nbsp;</div>')
                    .append(showSelectedClassesLink)
                    .append(', ')
                    .append(showAllFieldsLink)
                );

        callbacks['on'] = new Array();
        callbacks['off'] = new Array();

        var ul = $('ul',fieldClassMenu);
        for(var cls in document.fieldclasses) {
            var title = document.fieldclasses[cls];
            ul.append($('<li><input type="checkbox" class="non-persistent" id="toggle-class-'+cls+'" value="'+cls+'"/><label for="toggle-class-'+cls+'">&nbsp;'+title+'</label></li>'));

            callbacks['on'][cls] = new Array();
            callbacks['off'][cls] = new Array();
        }

        $(":checkbox",fieldClassMenu).change(function(event){
            _toggleField($(event.target));
        });
        
        fieldClassMenu.insertBefore($('#app-sub-menu'));

        _updateFieldSet();

        $(document).bind(SectionWidget.EVENTS.SECTION_LOADED,function(t,section){
            _loadSection(section);
        });

        $(document).bind(SectionWidget.EVENTS.SECTIONS_BUILT,function(){
            _updateFieldSet();
            _updateShownClasses();
        });

        _loadCookie();
    };

    var _saveCookie = function() {

        var cookie = {
            visible: false,
            classes: []
        };

        cookie.visible = (fieldClassMenu.children('ul').css('display') !== 'none');

        $(':input:checked',fieldClassMenu).each(function(i,e){
            var $checkbox = $(e);
            cookie.classes.push($checkbox.val());
        });

        cookie.show_selected = showSelectedClassesLink.hasClass('selected');

        var path = '/'+document.pageinfo.Element;
        if(document.pageinfo.Type !== '')
            path += '/' + document.pageinfo.Type;

        var expires = new Date();
        expires.setDate(expires.getDate() + 365*30);

        $.cookie('field-class-menu',JSON.stringify(cookie),{path:path,expires:expires});
    };

    var _loadCookie = function() {

        var cookie = $.cookie('field-class-menu');

        if(cookie) {
            cookie = JSON.parse(cookie);

            if(cookie.visible === false)
                _toggleMenu();

            if(cookie.classes) {
                for(var i=0; i<cookie.classes.length; i++) {
                    var $checkbox = $(':checkbox[value='+cookie.classes[i]+']',fieldClassMenu);
                    $checkbox.attr('checked','checked');
                    _toggleField($checkbox);
                }
            }

            if(cookie.show_selected === true)
                _showSelectedClasses();
        }
    };
    
    var _toggleMenu = function() {
        fieldClassMenu.children().not("h3").toggle();
        _saveCookie();
    };

    var _toggleField = function($checkbox) {

        if($checkbox.is(":checked")) {
            $('li.'+$checkbox.val()).addClass('highlighted');

            for(var i=0; i<callbacks['on'][$checkbox.val()].length; i++){
                callbacks['on'][$checkbox.val()][i]();
            }
        } else {
            var fields = $('li.'+$checkbox.val());
            $(':input:checked',fieldClassMenu).each(function(i,e){
                fields = fields.not('.'+$(e).val());
            });
            fields.removeClass('highlighted');

            for(var i=0; i<callbacks['off'][$checkbox.val()].length; i++){
                callbacks['off'][$checkbox.val()][i]();
            }
        }

        _updateShownClasses();
        _saveCookie();
    };

    var _loadSection = function($section) {

        _updateFieldSet();

        $(':input:checked',fieldClassMenu).each(function(i,e){
            var $checkbox = $(e);
            $('li.'+$checkbox.val(),$section).addClass('highlighted');
        });

        _updateShownClasses();
    };

    var _updateShownClasses = function() {

        if(!showSelectedClassesLink.hasClass('selected')) return;

        fieldsets.show();
        console_log(fieldsets);

        var fields = fieldSet.show();

        $(':checkbox:checked',fieldClassMenu).each(function(i,e){
            fields = fields.not('.'+$(e).val());
        });

        fields.hide();

        fieldsets.filter(function(){
            var $this = $(this);
            if($this.find('li.field').length === 0) return true;
            return $this.find('li.field:visible').length === 0;
        }).hide();
    };

    var _showSelectedClasses = function() {

        showSelectedClassesLink.addClass('selected');
        showAllFieldsLink.removeClass('selected');

        _updateShownClasses();
        _saveCookie();
    };

    var _showAllFields = function() {

        showSelectedClassesLink.removeClass('selected');
        showAllFieldsLink.addClass('selected');

        fieldsets.show();

        fieldSet.show();

        _saveCookie();
    };

    var _updateFieldSet = function() {
        fieldSet = $('#app-content fieldset > ul > li.field');

        fieldsets = $('#app-content fieldset').filter(function(){
            return $(this).find('li.field').length !== 0;
        }).add('fieldset#sections').add('fieldset.section-list');
    };


    /* initialize singleton */
    $(document).ready( function() {
        init();
    });


    /* public methods */
    return {
        bind : function(event, fieldClass, callback) {
            if(callback === null) return;
            if(event !== 'on' && event !== 'off') return;

            for(var cls in document.fieldclasses) {
                if(fieldClass == cls) {
                    callbacks[event][fieldClass].push(callback);
                }
            }
        },

        turnOn : function(fieldClass) {
            var $checkbox = $(':checkbox[value='+fieldClass+']',fieldClassMenu);
            $checkbox.attr('checked','checked');
            _toggleField($checkbox);
        },

        turnOff : function(fieldClass) {
            var $checkbox = $(':checkbox[value='+fieldClass+']',fieldClassMenu);
            $checkbox.removeAttr('checked');
            _toggleField($checkbox);
        }
    };

})();

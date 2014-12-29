var Edit = function() {
	var t = this;

    var footer = null;
    var sidebar = null;

	var errorFields = false;

	var highlightErrorFields = function() {

		$.each(this.errorFields || [], function(i,errorField) {
			highlightErrorField(errorField);
		});

	};

	var highlightErrorField = function(errorField) {
		$('label[for='+errorField+']').add('#'+errorField).addClass('error');
		var byName = $('input[name='+errorField+']');
		byName.add('label[for='+byName.attr('id')+']').addClass('error');
	};

	var hasErrorField = function(errorField) {
		return $.inArray(errorField, this.errorFields || []) > -1;
	};

	var hideButtons = function() {
		$('.button-save').add('.button-save-new').css('display', 'none');
	};

	var showButtons = function() {
        if(!document.formSubmitting)
		    $('.button-save').add('.button-save-new').css('display', 'inline');
	};

    var revertFormSubmission = function(event) {

        if(typeof event != "undefined" && event != null)
            event.preventDefault();

        document.madeChanges = true;
        document.formSubmitting = false;
        $('.button-save').add('.button-save-new').add('.button-cancel').add('.button-delete').show();
    };

	$(document).ready( function() {
		if(!$('body').hasClass('edit') || $('body').hasClass('signin')) return;

		// all form submits
		$('form').bind('submit', function(e) {
			document.madeChanges = false;
			document.formSubmitting = true;
            $('.button-save').add('.button-save-new').add('.button-cancel').add('.button-delete').hide();
		} ).keypress(function(e) {
			if(e.which == 13 && !$(e.target).is('textarea')) return false;
		});

		$(':input').not(".non-persistent").change( function() {
			$(document).trigger('form_changed');
		}).keypress( function() {
			$(document).trigger('form_changed');
		});

		hideButtons();

		$(document).bind('form_changed', function() {
			showButtons();
		});

		refreshSubMenu();

        footer = $('#app-footer');
        sidebar = $('#app-sidebar');

        $(window).resize(function(){
            resizeSideBar();
        });
        resizeSideBar();

	});

    var resizeSideBar = function() {
        sidebar.height((footer.position().top-sidebar.position().top-10)+'px');
        $('#app-sidebar').trigger('RESIZE');
    };

	var refreshSubMenu = function() {
		$('#app-sub-menu').empty();
		var hash = window.location.hash;

        var location = window.location.href;
        if(hash.length > 0 && location.endsWith(hash)) {
            location = location.substr(0,location.length-hash.length);
        }

        $('fieldset').each(function(i, e) {
			var h3 = $('>h3', e);
			if(h3.length > 0 && $(e).attr('id') != '') {
				if(hash == '' && i == 0) hash = '#'+$(e).attr('id');
				var newLI = $('<li '+(hash=='#'+$(e).attr('id')?'class="selected"':'')+'><a href="'+location+'#'+$(e).attr('id')+'">'+h3.text()+'</a></li>').click(function() {
					$this = $(this);
					$('#app-sub-menu li').removeClass('selected');
					$this.addClass('selected');
				});


				var newUL = $('<ul></ul>');
				var addedSub = false;

				$('fieldset', $(e)).each(function(i, sub) {
					var h4 = $('>h4', sub);
					if(h4.length > 0 && $(sub).attr('id') != '') {
						if(hash=='#'+$(sub).attr('id'))
							newLI.addClass('selected');
						var newLI2 = $('<li><a href="#'+$(sub).attr('id')+'">'+h4.text()+'</a></li>').click(function() {
							$this = $(this);
							$('#app-sub-menu li').removeClass('selected');
							$this.parent().parent().addClass('selected');
						});

						newUL.append(newLI2);
						addedSub = true;
					}
				});

				if(addedSub)
					newLI.append(newUL);

				$('#app-sub-menu').append(newLI);
			}
		});
	};

	return {
		highlightErrorFields: highlightErrorFields,
		highlightErrorField: highlightErrorField,
		hasErrorField: hasErrorField,
		refreshSubMenu: refreshSubMenu,
        revertFormSubmission : revertFormSubmission,

		setErrorFields: function(errors) {
			this.errorFields = errors;
		},

		getErrorFields: function() {
			return this.errorFields;
		}

	};

}();


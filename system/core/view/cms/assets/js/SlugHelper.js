var SlugHelper = {

	create: function(newValue, allowSlashes) {

		if(typeof allowSlashes == 'undefined') {
		    var allowSlashes = false;
		    if(typeof document.taggableRecord != 'undefined') {
		        allowSlashes = SystemService.getElementAllowSlugSlashes(document.taggableRecord.Element.Slug);
		    }
		}

		return SlugUtils.createSlug(newValue, allowSlashes);

	},

	update: function(newValue, allowSlashes) {

		var newSlug = SlugHelper.create(newValue, allowSlashes);
		$('#Slug').val(newSlug);
        var slugEditor = $('#slug-editor');
        if(slugEditor.length > 0)
            slugEditor.val(newSlug);
        else
		    $('#slug-container div span').text(newSlug);

		$(document).trigger('form_changed');

	},

	toggleField: function(event) {
		$this = $(event.target);
		if($this.text() == 'edit') {
			var currentSlug = $('#Slug').val();
			var newInput = $('<input type="text" id="slug-editor" maxlength="255" />').val(currentSlug);
			var cancelButton = $('<a id="slug-editor-cancel" href="#" style="margin-left:10px">cancel</a>').click(function() {
				$('#slug-container div span').empty().append(currentSlug);
				$this.text('edit');
				$(this).remove();
				return false;
			});

			$('#slug-container div span').empty().append(newInput);
			$('#slug-container').append(cancelButton);
			$this.text('save');
		} else {

			var newSlug = SlugHelper.create($('#slug-editor').val());
			$('#Slug').val(newSlug);
			$(document).trigger('form_changed');
			$('#slug-container div span').empty().append(newSlug);
			$this.text('edit');
			$('#slug-editor-cancel').remove();
		}
		return false;
	}

};
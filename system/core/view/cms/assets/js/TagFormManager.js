var TagFormManager = function(taggableObject, form, input) {

	var me = this;

	this.TaggableObject = taggableObject;
	this.Form = (form == null ? null : $(form));
	//this.Input = (input == null ? 'Data' : input);


	//BIND EXTERNAL EVENT HANDLERS
	this.TaggableObject.bind(NodeObject.EVENTS.INITIALIZED,function(){
		me._handleInitialized.apply(me);
	});

	this.TaggableObject.bind(Taggable.EVENTS.TAGS_UPDATED,function(){
		$(document).trigger('form_changed');
	});
};


TagFormManager.prototype = {

	_handleInitialized : function() {

		var me = this;

		if(this.Form != null) {

			$('#debuglink').click(function(event){
				event.preventDefault();
				me._renderDebug.apply(me);
			});


			$(':input[name^=#]', this.Form).each(function(i, metafield) {

				metafield = $(metafield);

				var metarole = metafield.attr('name').substr(1);
				var tag = me.TaggableObject.getMetaValue(metarole);

				if(metafield.attr('type') === 'checkbox') {

					if(tag != null && tag != 0) {
						metafield.attr('checked','checked');
					} else {
						metafield.removeAttr('checked');
						me.TaggableObject.updateMeta(metarole, null, true);
					}

					metafield.change(function(){
						var $this = $(this);
						if(!$this.is(":checked")) {
							me.TaggableObject.updateMeta($this.attr('name').substr(1), null);
						} else {
							me.TaggableObject.updateMeta($this.attr('name').substr(1), $this.val());
						}
						//me._rebuildInputs.apply(me);
					});

				} else {

					if(tag != null) {
						metafield.val(tag.MetaValue);
					} else {
						me.TaggableObject.updateMeta(metarole, metafield.val(), true);
					}

					metafield.blur(function() {
						var $this = $(this);
						me.TaggableObject.updateMeta($this.attr('name').substr(1), $this.val());
						//me._rebuildInputs.apply(me);
					});
				}
			});

			this._rebuildInputs();

			//REBUILD INPUT FROM TAGS BEFORE FORM IS SUBMITTED
			this.Form.submit(function(){
				me._rebuildInputs.apply(me);
			});
		}
	},

	_rebuildInputs : function() {
		if (this.Form == null) return;

		if (this.FormTags == null || $('div.tags-', this.Form).length == 0) {
			this.FormTags = $('<div class="tags-"></div>').css({display:'none'});
			this.Form.prepend(this.FormTags);
		}

		this.FormTags.empty();
//		this.FormTags.append('<input type="hidden" name="_'+this.Input+'" value="1"/>');
//      this.FormTags.append('<input type="hidden" name="Meta.select" value="'+this.TaggableObject.getMetaPartials()+'"/>');
        this.FormTags.append('<input type="hidden" name="OutTags.partials" value="'+this.TaggableObject.getOutPartials()+'"/>');
        this.FormTags.append('<input type="hidden" name="InTags.partials" value="'+this.TaggableObject.getInPartials()+'"/>');

        var values = this._getInputsArray();

        var me = this;
        $.each(values,function(name,value){
            me.FormTags.append($('<input type="hidden" name="'+name+'" />').val(value));
        });
	},

	_getInputsArray : function() {

		var me = this;

		var formtags = {};

		$.each(this.TaggableObject.getTags(), function(n, tags) {
			var dir = (n=='in')?'InTags':'OutTags';
			$.each(tags, function(i, tag) {
				formtags[dir+'[#'+tag.TagRole+']['+i+'][TagElement]'] = tag.TagElement;
				formtags[dir+'[#'+tag.TagRole+']['+i+'][TagSlug]'] = tag.TagSlug;
				formtags[dir+'[#'+tag.TagRole+']['+i+'][TagRole]'] = tag.TagRole;
				formtags[dir+'[#'+tag.TagRole+']['+i+'][TagValue]'] = tag.TagValue;
				formtags[dir+'[#'+tag.TagRole+']['+i+'][TagValueDisplay]'] = tag.TagValueDisplay;
				formtags[dir+'[#'+tag.TagRole+']['+i+'][TagSortOrder]'] = tag.TagSortOrder;
				formtags[dir+'[#'+tag.TagRole+']['+i+'][TagLinkTitle]'] = tag.TagLinkTitle;
			});
		});

//        $.each(this.TaggableObject.getMetas(), function(i, meta) {
//            formtags[me.Input+'[#'+meta.MetaName+']'] = meta.MetaValue;
//        });

		return formtags;
	},

    debug: function() {

        var tags = this._getInputsArray();

        $.each(tags, function(k, s){
            console_log(k+' = '+s);
        });

    },

	_renderDebug : function() {

		var tags = this._getDebugTags();

		if($('#tags_debug').length == 0) {

			var tagsDisplay = $('<h2 id="tags_debug_title">Tags</h2><div id="tags_debug" style="background: rgb(247, 247, 247); border: 1px solid rgb(215, 215, 215); margin: 1em 1.75em; overflow: auto auto; padding: 0.25em; display: block; white-space: pre"></div>');
			$('#debug h2:first').before(tagsDisplay);
		}

		$('#tags_debug_title').empty().text('Tags (Meta: '+this.TaggableObject.getMetaPartials()+', Out: '+this.TaggableObject.getOutPartials()+', In: '+this.TaggableObject.getInPartials()+')');
		$('#tags_debug').empty().html(tags);
	},

	_getDebugTags: function() {

        var me = this;

		var temptags = new Array();
		var temptypes = new Array();
		var i = 0;

		var tagtypes = this.TaggableObject.getTags();
		var str = $('<div></div>');
		for(var tagtype in tagtypes) {
			var tags = tagtypes[tagtype];
			str.append('<br/>['+tagtype+']<br/>');
			for(var index in tags){
				str.append(tags[index].toString());

				temptypes[++i] = tagtype;
				temptags[i] = tags[index];

				var rLink = $('<a href="#" id="tagdebug-'+i+'">delete</a>').click(function(event) {
					event.preventDefault();
					var i2 = $(this).attr('id').split('-')[1];
					me.TaggableObject.removeTag(temptypes[i2],temptags[i2].toPartial());
					me._rebuildInputs.apply(me);
					$('#debuglink').click().click();
				});
				str.append(' ').append(rLink);

				if(tags[index].MatchPartial)
					str.append('<pre>  ('+tags[index].MatchPartial+')</pre>');

				str.append('<br/>');
			}
		}
		return str;
	}

};

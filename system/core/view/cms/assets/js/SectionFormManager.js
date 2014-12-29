var SectionFormManager = function(sectionableObject, form) {

	var me = this;

	this.SectionableObject = sectionableObject;
	this.Form = (form == null ? null : $(form));
	this.InputsParent = null;


	//BIND EXTERNAL EVENT HANDLERS
	$(this.SectionableObject).bind(NodeObject.EVENTS.INITIALIZED,function(){
		me._handleInitialized.apply(me);
	});

	this.SectionableObject.bind(NodeObject.EVENTS.SECTIONS_UPDATED,function(){
		$(document).trigger('form_changed');
	});
};


SectionFormManager.prototype = {

	_handleInitialized : function() {
		var me = this;

		//SETUP DEBUG BEHAVIOR
		$('#debuglink').click(function(event) {
			event.preventDefault();

			if($('#sections_debug').length == 0) {

				var sectionsDisplay = $('<h2>Sections</h2><div id="sections_debug" style="background: rgb(247, 247, 247); border: 1px solid rgb(215, 215, 215); margin: 1em 1.75em; overflow: auto auto; padding: 0.25em; display: block; white-space: pre"></div>');
				$('#debug h2:first').before(sectionsDisplay);
			}

			$('#sections_debug').empty().html(me._debug());
		});


		//REBUILD INPUT FROM TAGS BEFORE FORM IS SUBMITTED
		this.Form.submit(function(){
			me._rebuildInputs.apply(me);
		});
	},

	_debug: function() {

		var me = this;
		var section_count = 0;

		var str = '';
		$.each(this.SectionableObject.getSectionTypes(), function(n, sectionType) {
			var secs = me.SectionableObject.getSections(sectionType);
			str += '['+sectionType+']\n';
			//Section.sort(secs);
			$.each(secs, function(p, section) {
				str += '\t['+section.Title+']\n';
				$.each(me._getInputsArray('Sections',section_count++, section), function(m,v) {
					str += '\t\t'+m + ' = ' +v+'\n';
				});
			});
		});
		return str;

	},

	_getInputsArray: function(param, count, section) {

		var result = {};

		result[param+'[#'+section.SectionType+']['+count+'][SectionSortOrder]'] = section.SectionSortOrder;
		result[param+'[#'+section.SectionType+']['+count+'][SectionID]'] = section.SectionID;
		result[param+'[#'+section.SectionType+']['+count+'][SectionType]'] = section.SectionType;
		result[param+'[#'+section.SectionType+']['+count+'][SectionTitle]'] = section.SectionTitle;

		$.each(this._getTagInputsArray(param+'[#'+section.SectionType+']['+count+']',section), function(n, v) {
			result[n] = v || '';
		});

		return result;
	},

	_getTagInputsArray : function(input,section) {

        var formtags = {};

        $.each(section.getTags(), function(n, tags) {
        	var dir = (n=='in')?'InTags':'OutTags';
			$.each(tags, function(i, tag) {
                formtags[input+'['+dir+'][#'+tag.TagRole+']['+i+'][TagElement]'] = tag.TagElement;
				formtags[input+'['+dir+'][#'+tag.TagRole+']['+i+'][TagSlug]'] = tag.TagSlug;
				formtags[input+'['+dir+'][#'+tag.TagRole+']['+i+'][TagRole]'] = tag.TagRole;
                formtags[input+'['+dir+'][#'+tag.TagRole+']['+i+'][TagValue]'] = tag.TagValue;
                formtags[input+'['+dir+'][#'+tag.TagRole+']['+i+'][TagValueDisplay]'] = tag.TagValueDisplay;
				formtags[input+'['+dir+'][#'+tag.TagRole+']['+i+'][TagSortOrder]'] = tag.TagSortOrder;
				formtags[input+'['+dir+'][#'+tag.TagRole+']['+i+'][TagLinkTitle]'] = tag.TagLinkTitle;
                if(tag.MatchPartial)
                    formtags[input+'['+dir+'][#'+tag.TagRole+']['+i+'][MatchPartial]'] = tag.MatchPartial;
			});
        });

        $.each(section.getMetas(), function(i, meta) {
            formtags[input+'[#'+meta.MetaName+']'] = meta.MetaValue;
        });

		return formtags;
	},

	_rebuildInputs : function() {
		var me = this;

		if (this.InputsParent == null) {
			this.InputsParent = $('<div id="sections-input"></div>').css('display', 'none');
			$('#sections').prepend(this.InputsParent);
		}

		this.InputsParent.empty();

		var section_count = 0;

		$.each(this.SectionableObject.getSectionTypes(), function(n, sectionType) {
			var secs = me.SectionableObject.getSections(sectionType);
			Section.sort(secs);
			$.each(secs, function(p, section) {
				$.each(me._getInputsArray('Sections',section_count++,section), function(m,v) {
					me.InputsParent.append($('<input type="hidden">').attr('name',m).attr('value',v));
				});
			});
		});
	
	}
};

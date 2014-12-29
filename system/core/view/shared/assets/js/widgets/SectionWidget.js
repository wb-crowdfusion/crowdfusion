var SectionWidget = function(sectionableObject,domID,options) {

	var me = this;

	this.SectionableObject = sectionableObject;
	this.DefaultService = SectionsService;
    var site = SystemService.getSiteBySlug(sectionableObject.Site.Slug);
	this.SiteID = site.SiteID;
	this.Element = sectionableObject.Element.Slug;
	this.Reordering = false;//TODO: get rid of this

	//USER INTERFACE HANDLES ('DOM' OBJECT WILL CONTAIN REFERENCES TO ALL UI COMPONENTS IN THE WIDGET)
	this.DOM = {
		parent : $(domID)
		//TODO: update this list
	};

	//INITIALIZE DEFAULT OPTIONS
	this.Options = $.extend({
		Label : "Section",
		LabelPlural : null,
		Type : null,
		Template : null,
		Min : 0,
		Max : '*',
		Sortable : false,
		Required : false,
		CollapseCallback : null
	}, options || {});


	//PLURALIZE LABEL
	if(this.Options.LabelPlural == null) {
		//this.Options.LabelPlural = this.Options.Label.pluralize();
		this.Options.LabelPlural = this.Options.Label;
	}

	//BIND EXTERNAL EVENT HANDLERS
	this.SectionableObject.bind(NodeObject.EVENTS.INITIALIZED,function(){
		me._handleInitialized.apply(me);
	});

	this.SectionableObject.bind(NodeObject.EVENTS.SECTIONS_UPDATED_WITH_TYPE,function(){
		me._handleSectionsUpdated.apply(me);
	},this.Options.Type);
};

SectionWidget.EVENTS = {
	SECTIONS_BUILT : 'SectionsBuilt',
	SECTION_LOADED : 'SectionLoaded'
};

SectionWidget.prototype = {

	_handleInitialized : function() {

		var me = this;

		this.DOM.fieldSet = $('<fieldset class="section-list" id="'+this.Options.Type+'s"></fieldset>');
		this.DOM.label = $('<h4>'+(this.Options.Max == '1' ? this.Options.Label : this.Options.LabelPlural)+' </h4>');


		if(this.Options.Sortable) {
			this.DOM.reorderButton = $('<a href="#" class="section_reorderlink">[reorder]</a>')
				.click(function(event){
					event.preventDefault();
					me._activateReordering.apply(me);
				})
				.css('display', 'none');

			this.DOM.reorderFinishButton = $('<a href="#" class="section_reorderlink">[done]</a>')
				.click(function(event){
					event.preventDefault();
					me._deactivateReordering.apply(me);
				})
				.css('display', 'none');

			this.DOM.label.append(
				$('<span></span>')
					.append(this.DOM.reorderButton)
					.append(this.DOM.reorderFinishButton)
			);
		}

		if(this.Options.Max > 1 || this.Options.Max == '*') {
			this.DOM.addButton = $('<a href="#" class="section-addlink"><span>Add new '+this.Options.Label+'</span></a>').click(function(event){
				event.preventDefault();
				//me._buildSection.apply(me, [me._addNewSection.call(me)]);//TODO: could probably fix these methods up, should just add section to SectionableObject
				var section = new Section({
					SectionType : me.Options.Type,
					SectionSortOrder : me.DOM.sectionList.children().length + 1,
					SectionID: 0
				});
				
				me.SectionableObject.addSection(section);

			});

			this.DOM.fieldSet.append($('<p></p>').append(this.DOM.addButton));
		}


		this.DOM.sectionList = $('<ol class="sectiontypecontents"></ol>');

		//CREATE THE MINIMUM NUMBER OF SECTIONS
		this._addMinimumSections();

		//BUILD INITIAL SECTION LIST
		this._synchronizeSectionList();

		//ASSEMBLE THE WIDGET COMPONENTS
		this.DOM.fieldSet.prepend(this.DOM.sectionList).prepend(this.DOM.label);


		this.DOM.parent.append(this.DOM.fieldSet);


		//FIRE A GLOBAL TRIGGER TO UPDATE THE FIELD CLASSES WIDGET IN THE SIDE BAR (NEEDS TO KNOW ABOUT THE FIELDS IN THIS SECTION)
		//NOTE: OTHER CLASSES MAY SUBSCRIBE TO THIS EVENT
		//TODO: this may fire before all the ajax section loading is done, needs to fire after all are loaded; may not be needed if each section load fires an event
		//$(document).trigger(SectionWidget.EVENTS.SECTIONS_BUILT);
	},

	_handleSectionsUpdated : function() {
		this._synchronizeSectionList();
	},

	_addMinimumSections : function() {
		
		var sections = this.SectionableObject.getSections(this.Options.Type);

		//CREATE THE MINIMUM NUMBER OF SECTIONS
		if(sections.length == 0) {
			for(var i = 0; i < this.Options.Min; i++) {
				var section = new Section({
					SectionType : this.Options.Type,
					SectionSortOrder : this.SectionableObject.getSections().length + 1
				});

				this.SectionableObject.addSection(section,true);
			}
		}
	},

	_synchronizeSectionList : function() {
		var me = this;

		//this will add/delete/update the rendered sections (no rebuilding from scratch like tagwidget)

		var sections = this.SectionableObject.getSections(this.Options.Type);

		//DELETE SECTIONS
		$.each(this.DOM.sectionList.children(),function(i,li){
			var section = li.getSection();

			//IS THIS SECTION(LI) STILL IN THE SECTIONABLE OBJECT?
			var arr = $.grep(sections,function(sec){
				return sec.equals(section);
			});

			if(arr.length == 0) {
				$(li).remove();

				//TODO: this was only necessary for FCKEditor, review if needed now
				//li.css({position: 'absolute', left: '-1000px', top: '-1000px'});
			}
		});

		//ADD & UPDATE SECTIONS
		$.each(sections,function(i,section){
			var li = $('#section_'+section.ID,me.DOM.sectionList);

			if(li.length == 1) {
				//SECTION UPDATED
				//FOR NOW WE DON'T DO ANYTHING SINCE SECTION UPDATES ORIGINATE FROM THIS WIDGET
				//IN THE FUTURE OTHER WIDGET MAY UPDATE SECTION CONTENTS AND WE WOULD HAVE TO
				//REFLECT THOSE UPDATE IN THE SECTION HERE.

				//TODO: consider reordering; this would fall into the update scenario
			} else {
				//SECTION ADDED
				me._buildSection.apply(me,[section]);
			}

		});

		this._refreshLinks();
	},

	_buildSection : function(section) {
		var li = $('<li class="section" id="section_'+section.ID+'"></li>');

		li[0].getSection = function() {
			return section;
		};

		var me = this;

		//GET SECTION CONTENTS VIA AJAX
		$(document).trigger('show_loading');

        var formtags = {};
        
        // NOTE: POPULATING SECTIONS TAKES COMPLETELY DIFFERENT FORM PARAMS THAN SAVING

//        $.each(section.getTags(), function(n, tags) {
//            $.each(tags, function(i, tag) {
//                formtags['Tags['+n+']['+i+'][TagString]'] = tag.toString();
//                formtags['Tags['+n+']['+i+'][TagElement]'] = tag.TagElement;
//                formtags['Tags['+n+']['+i+'][TagSlug]'] = tag.TagSlug;
//                formtags['Tags['+n+']['+i+'][TagRole]'] = tag.TagRole;
//                formtags['Tags['+n+']['+i+'][TagValue]'] = tag.TagValue;
//                formtags['Tags['+n+']['+i+'][TagValueDisplay]'] = tag.TagValueDisplay;
//                formtags['Tags['+n+']['+i+'][TagSortOrder]'] = tag.TagSortOrder;
//                formtags['Tags['+n+']['+i+'][TagLinkTitle]'] = tag.TagLinkTitle;
//                formtags['Tags['+n+']['+i+'][TagSectionID]'] = tag.TagSectionID;
//                if(tag.MatchPartial)
//                    formtags['Tags['+n+']['+i+'][MatchPartial]'] = tag.MatchPartial;
//            });
//        });

        // pass meta cheaters
        $.each(section.getMetas(), function(i, meta) {
        	formtags['#'+meta.MetaName] = meta.MetaValue;
        });
		
		SectionsService.getSectionContents(
			section,
			this.SiteID,
			this.Element,
			this.Options.Template,
			this.Options.Min,
			this.Options.Max,
			formtags,
			function(contents){
				me._populateSection.apply(me,[li,contents]);

				$(document).trigger('hide_loading');

				//FIRE A GLOBAL TRIGGER TO UPDATE THE FIELD CLASSES WIDGET AND INLINE HELP WIDGET
				//NOTE: OTHER CLASSES MAY SUBSCRIBE TO THIS EVENT
				$(document).trigger(SectionWidget.EVENTS.SECTION_LOADED,[li]);
			}
		);

		this.DOM.sectionList.append(li);
	},

	_populateSection : function(li, contents) {

		var me = this;
		var section = li[0].getSection();

		if(this.Options.Max == '*' || this.Options.Max > 1) {
			var tools = $('<p></p>');

			var cloneLink = $('<a href="#" class="section-clonelink"><span>Clone</span></a>').click(function(event){
				event.preventDefault();

				var clone = section.clone();
				clone.SectionSortOrder = me.DOM.sectionList.children().length + 1;
				me.SectionableObject.addSection(clone);
			});

			var deleteLink = $('<a href="#" class="section-deletelink"><span>Delete</span></a>').click(function(event){
				event.preventDefault();
				me.SectionableObject.removeSection(section);
			});

			tools.append(cloneLink).append(' ').append(deleteLink);
			li.append(tools);
		}

		li.append(contents);

		$(':input',li).each(function(i,input) {
			input = $(input);
			var name = input.attr('name');

			if(!name) return;

			if(name.startsWith('#')) {
				var metarole = name.substr(1);
				var tag = section.getMetaValue(metarole);

				if(input.attr('type') === 'checkbox') {
					if(tag != null) input.attr('checked','checked');
					else {
						input.removeAttr('checked');
					}

					input.change(function() {
						var $this = $(this);
						if(!$this.is(":checked")) {
							section.updateMeta(metarole, null);
						} else {
							section.updateMeta(metarole, $this.val());
						}
						me.SectionableObject.updateSection(section);
					});
				} else {
					if(tag != null){
						input.val(tag.TagValueDisplay);
					}

					input.blur(function() {
						var $this = $(this);
						section.updateMeta(metarole, $this.val());
						me.SectionableObject.updateSection(section);
					});
				}

				if(input.attr('type') === 'checkbox' && !input.is(":checked")) {
					section.updateMeta(metarole, null,true);
				} else {
					section.updateMeta(metarole, input.val(),true);
				}
			} else {

				input.change(function() {
					section[name] = this.value;
					me.SectionableObject.updateSection(section);
				});
				section[name] = input.val();
			}

			var newname = 'temp_section_'+section.ID+'_'+name;
			input.attr('name', newname);

			if(Edit.hasErrorField('section['+section.SectionSortOrder+']['+name+']')) {
				Edit.highlightErrorField(newname);
			}

		});

		//TODO: TIGHT COUPLING HERE; NEED TO NOTIFY THESE CLASSES VIA EVENT
		Edit.highlightErrorFields();
		Edit.refreshSubMenu();
	},

	_refreshLinks : function() {

		if(this.Options.Max > 1 || this.Options.Max == '*') {

			if(this.Options.Sortable) {
				if(this.DOM.sectionList.children().length > 1)
					this.DOM.reorderButton.show();
				else
					this.DOM.reorderButton.hide();
			}

            if(this.Options.Min == this.DOM.sectionList.children().length) {
				$('.section-deletelink', this).hide();
            } else {
				$('.section-deletelink', this).show();
			}

			if(this.Options.Max != '*' && this.DOM.sectionList.children().length == this.Options.Max) {
				this.DOM.addButton.hide();
				$('.section-clonelink', this).hide();
			}else {
				this.DOM.addButton.show();
				$('.section-clonelink', this).show();
			}
		}
	},

	createEditor : function(sectionID, field, domID, inputID, options) {

		var section = this._getSectionByID(sectionID);

		if(section) {
			var editor = new EditorWidget(
				domID,
				inputID,
				$.extend({
					onkeyup : function(){
						section[field] = this.DOM.textArea.val();
					}
				},options)
			);

			editor.init();
		}
	},

//	createTagWidget : function(sectionID,domID,tagPartial,options) {
//
//		var section = this._getSectionByID(sectionID);
//
//		if(section) {
//			new PageTagWidget(
//				section,
//				new TagPartial(tagPartial),
//				domID,
//				this.SiteID,
//				options
//			);
//		}
//	},

	initializeSection : function(sectionID) {

		var section = this._getSectionByID(sectionID);

		if(section) {
			section.trigger(Taggable.EVENTS.INITIALIZED);
		}
	},

	_getSectionByID : function(sectionID) {

		var section = $.grep(this.DOM.sectionList.children(),function(li){
			//THIS CHECK IS HERE BECAUSE THE SORTABLE CREATES A TEMP CHILD IN THIS LIST WHICH DOESNT HAVE getSection()
			if('getSection' in li) { 
				var s = li.getSection();
				return s.ID == sectionID;
			} else return false;
		});

		if(section.length == 1) {
			return section[0].getSection();
		}

		return null;
	},

	_activateReordering : function() {

		this._updateEditors();

		var me = this;

		$.each(this.DOM.sectionList.children(), function(i,li){
			me._collapseSection.apply(me,[li]);
		});

		this.DOM.sectionList.sortable({
			revert: false,
			scroll: false,
			axis: 'y',
			update: function(e,ui) {
				var neworder = [];
				$.each(ui.instance.toArray(), function(i,o){
					neworder[i] = o.substr(o.lastIndexOf('_')+1);
				});
				me._reorder.apply(me,[neworder]);
			}
		}).sortable('enable');

		this.DOM.reorderButton.hide();
		this.DOM.reorderFinishButton.show();

		if(this.DOM.addButton)
			this.DOM.addButton.hide();
	},

	_deactivateReordering : function() {
		var me = this;

		$.each(this.DOM.sectionList.children(), function(i,li){
			me._expandSection.apply(me,[li]);
		});

		this.DOM.sectionList.sortable('destroy');
		this._refreshEditors();

		this.DOM.reorderButton.show();
		this.DOM.reorderFinishButton.hide();

		if(this.DOM.addButton)
			this.DOM.addButton.show();

		//FIRE SECTION EVENTS MANUALLY SINCE SUPPRESSED REMOVE/ADD CYCLES WERE HAPPENING DURING THE DRAG-AND-DROP
		this.SectionableObject.trigger(NodeObject.EVENTS.SECTIONS_UPDATED_WITH_TYPE,this.Options.Type);
		this.SectionableObject.trigger(NodeObject.EVENTS.SECTIONS_UPDATED);

	},

	_refreshEditors : function() {
//		$.each(this.Sections, function(i,section){
//			//section.refreshEditors();
//		});
	},

	_updateEditors : function() {
//		$.each(this.Sections, function(i,section){
//			section.updateEditors();
//		});

	},

	_reorder : function(neworder) {
		var me = this;

		var newsections = [];

		$.each(neworder, function(key,id){
			var section = me._getSectionByID.apply(me,[id]);
			section.SectionSortOrder = key;
			newsections.push(section);
		});

		this.SectionableObject.removeSectionsByType(this.Options.Type,true);
		Section.sort(newsections);
		this.SectionableObject.addSections(newsections);
	},

	_collapseSection : function(li) {

		var section = li.getSection();

		li = $(li);
		
		if($('.section-expand', li).length == 0) {
			li.wrapInner('<div class="section-expand"></div>');
		}

		var collapse = $('<p class="section-collapse"></p>').css({cursor: 'move'});

		collapse.html(section.Title || this.Options.Label+' '+section.SortOrder);
		li.prepend(collapse);

		$('.section-expand', li).css({position: 'absolute', left: '-1000px', top: '-1000px'});
	},

	_expandSection : function(li) {
		$('.section-expand', li).css({position: 'relative', left: 'auto', top: 'auto'});
		$('.section-collapse', li).remove();
	}

};


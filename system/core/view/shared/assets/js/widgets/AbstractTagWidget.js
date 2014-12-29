var AbstractTagWidget = function(taggableObject,chosenPartial,domID,options) {

	var me = this;

	this.TotalRecords = 0;
	this.SearchOffset = 1;
	this.UUID = 'tw-'+(new Date().getTime())+Math.floor(Math.random()*1024);
	this.DefaultService = null;

	//PARAMETER VALIDATION
	if(typeof taggableObject == 'undefined' || taggableObject == null || !instanceOf(taggableObject,Taggable))
		throw new Error("First constructor argument [taggableObject] must be instance of Taggable");
	this.taggableObject = taggableObject;

	if(typeof chosenPartial == 'undefined' || chosenPartial == null || !(chosenPartial instanceof TagPartial))
		throw new Error("Second constructor argument [chosenPartial] must be instance of TagPartial");
	this.tagPartial = chosenPartial;


	//USER INTERFACE HANDLES ('DOM' OBJECT WILL CONTAIN REFERENCES TO ALL UI COMPONENTS IN THE WIDGET)
	this.DOM = {
		container : $(domID),
		label : null,
		clearChosenListButton : null,
		reorderChosenListButton : null,
		reorderChosenListFinishButton : null,
		activateButton : null,
		searchInput : null,
		clearSearchButton : null,
		closeButton : null,
		chosenList : null,
		chosenReorderList : null,
		searchResultsScrollPane : null,
		searchResultsList : null,
		searchResultsTotalLabel : null,
		itemOptionsContainer : null
		//TODO: update this list
	};


	//TIMEOUT ID HANDLES USED TO CLEAR AND SET TIMEOUTS FOR WIDGET EVENTS
	this.Timers = {
		searchInput : null
	};


	//LOCK FLAGS USED TO PREVENT AJAX EVENT CHAINS FROM BUILDING UP A QUEUE
	this.Locks = {
		searchInput : false,
		scroll : false
	};


	//BIND EXTERNAL EVENT HANDLERS
	taggableObject.bind(Taggable.EVENTS.TAGS_UPDATED_WITH_PARTIAL,function(){
		me._handleTagsUpdated.apply(me);
	},this.tagPartial);

	taggableObject.bind(NodeObject.EVENTS.INITIALIZED,function(){
		me._handleInitialized.apply(me);
	});

    taggableObject.bind(NodeObject.EVENTS.POST_INIT,function(){
        me._handlePostInitialize.apply(me);
    });


	//INITIALIZE DEFAULT OPTIONS
	this.Options = $.extend({
		Label : "Element",
		LabelPlural : null,
		ActivateButtonLabel : "Element",
		ShowChosenLink : true,
		AllowMultiple : false,
		AllowQuickAdd : false,
		AllowReorderChosenList : false,
		AllowClearChosenList : true,
		AllowMultipleValues : false,
		AllowRemoveUndo : true,
		ValueMode : "none", /*PERMITTED: none, predefined, typein*/
		Values : [], /*ONLY APPLIES WHEN ValueMode = predefined*/
		ShowExistingValues : false, /*ONlY APPLIES WHEN ValueMode = typein*/
		ShowLineNumbers : true,
		SearchLimit : 25,
		TypeAheadSearchDelay : 500, /*MILLIS*/
		ScrollThreshold : 80, /*PERCENTAGE; LOADS MORE DATA WHEN SCROLLBAR REACHES % OF TOTAL IN LIST*/
		QuickAddNonce : null,
        ReplaceNonce : null,
        QuickAddElement : null,
        QuickAddURL: null,
        QuickAddLabel: "Element",
		ShowChosenList : true,
		ShowActivateButton : true,
		ShowRemoveButton : true,
		AllowEditChosen : true,
		ShowLabelWhenEmpty : true,
        ShowElementInChosenList: false,
        ShowElementInSearchResults: false,
		TagDirection : Tag.DIRECTION.OUT,
		TagFilter : null, //not used?
        TagPrepend : false,
		ShowSlugInSearchResults : false,
		Viewport : '#app-content',
        SearchParameters : {},
        SearchURL : null,
        ReadOnly : false,
        WarnOnClearChosenList : false,
        WarnOnRemove : false,
        AutoExpandParentHeight : false,
        ParentMinHeight : '220px',
        HideTagValues : false
	}, options || {});


    //console_log('CUSTOM AbstractTagWiget with hidden TagValues support: '+(this.Options.HideTagValues ? "enabled" : "disabled"));

    //INCREASE PARTIALS
    if(this.Options.TagDirection == Tag.DIRECTION.OUT) {
        taggableObject.increaseOutPartials(this.tagPartial.TagRole);
    } else {
        taggableObject.increaseInPartials(this.tagPartial.TagRole);
    }

    this.Options.QuickAddLabel = this.Options.Label;

    this.QuickAddElementCount = 0;

    if(!this.Options.QuickAddElement || this.Options.QuickAddElement == null)
        this.QuickAddElementCount = 0;
    else if(this.Options.QuickAddElement.charAt(0) == '@') {
        this.QuickAddElementCount = SystemService.getElementsByAspect(this.Options.QuickAddElement.substring(1)).length;
        if(this.QuickAddElementCount == 1) {
            var firstElement = SystemService.getElementsByAspect(this.Options.QuickAddElement.substring(1))[0];
            this.Options.QuickAddElement = firstElement.Slug;
            this.Options.QuickAddLabel = firstElement.Name;
        }
    }

	//PLURALIZE LABEL
	if(this.Options.LabelPlural == null) {
		//this.Options.LabelPlural = this.Options.Label.pluralize();
		this.Options.LabelPlural = this.Options.Label;
	}

	//DONT SUPPORT QUICKADD WITH A HIDDEN CHOSEN LIST
	if(!this.Options.ShowChosenList)
		this.Options.AllowQuickAdd = false;

};

AbstractTagWidget.EVENTS = {
	WIDGET_ACTIVATED : 'WIDGET_ACTIVATED',
	WIDGET_DEACTIVATED : 'WIDGET_DEACTIVATED'
};

AbstractTagWidget.CONSTANTS = {
	START_TYPING : 'Start typing to search...'
};

AbstractTagWidget.prototype = {

	//EXTERNAL EVENT HANDLERS
	_handleInitialized : function() {
		var me = this;

		var ESC = function(event){
			if(event.keyCode == 27/*ESC*/) {
				event.preventDefault();
				event.stopPropagation();
				me.closeWidget();
			}
		};

		//INITIALIZE MAIN DOM CONTAINER
		this.DOM.container.empty().addClass("tag-widget");

        this.DOM.container[0].getWidget = function(){
            return me;
        };


		//BUILD ALL WIDGET UI COMPONENTS
		this.DOM.label = $('<label>'+(this.Options.AllowMultiple?this.Options.LabelPlural:this.Options.Label)+'</label>');

		this.DOM.clearChosenListButton = $('<a href="#" title="Clear List">[Clear]</a>').css({display:'none'})
			.click(function(event){

                if(me.Options.WarnOnClearChosenList && !confirm("Clear all items? This action cannot be undone.")) {
                    event.preventDefault();
                    return;
                }

				me.clearChosenItems.apply(me,[event]);
			});

		this.DOM.reorderChosenListButton = $('<a href="#" title="Reorder List">[Reorder]</a>').css({display:'none'})
			.click(function(event){
				me.activateReordering.apply(me,[event]);
			});

		this.DOM.reorderChosenListFinishButton = $('<a href="#" title="Done Reordering">[Done Reordering]</a>').css({display:'none'})
			.click(function(event){
				me.deactivateReordering.apply(me,[event]);
			});

		this.DOM.undoRemoveButton = $('<a href="#" title="Undo Remove">[Undo]</a>').css({display:'none'});

		this.DOM.activateButton = $('<a class="activate-link" href="#"><span>'+(this.Options.AllowMultiple?"Add":"Choose")+' '+this.Options.ActivateButtonLabel+'</span></a>')
			.click(function(event){
				me.activateWidget.apply(me,[event]);
			});


		//CREATE THE DOM REQUIRED FOR THE SEARCH COMPONENT
		this.DOM.searchContainer = $('<div class="tag-widget-search-container"></div>').css({display:'none'}).keyup(ESC);
			this.DOM.searchClippingContainer = $('<div class="tag-widget-search-clipping-container"></div>');
				this.DOM.searchShadowTop = $('<div class="tag-widget-search-shadow-top"><div></div></div>');
				this.DOM.searchShadowContainer = $('<div class="tag-widget-search-shadow-container"></div>');
					this.DOM.searchContentContainer = $('<div class="tag-widget-search-content-container"></div>');
						this.DOM.searchInput = $('<input autocomplete="false" type="text" value="'+AbstractTagWidget.CONSTANTS.START_TYPING+'"/>')
							.keydown(function(event){
								me.processSearchInputKeyDown.apply(me,[event]);
							})
							.click(function(event){
								if(me.DOM.searchInput.val() == AbstractTagWidget.CONSTANTS.START_TYPING)
									me.DOM.searchInput[0].select();
							})
                            .inputFocus();
						this.DOM.clearSearchButton = $('<a class="clear-link" href="#" title="Clear Search">Clear</a>')
							.click(function(event){
								me.clearSearch.apply(me,[event]);
							});
						this.DOM.closeButton = $('<a class="close" href="#">Close</a>')
							.click(function(event){
								me.closeWidget.apply(me,[event]);
							});
						this.DOM.searchResultsContainer = $('<div class="tag-widget-search-results-container"></div>');
							this.DOM.searchResultsScrollPane = $('<div class="tag-widget-search-results-scrollpane"></div>')
								.scroll(function(event){
									me.scroll.apply(me,[event]);
								});
								this.DOM.searchResultsList = $('<ol></ol>');
							this.DOM.searchResultsToolbar = $('<div class="tag-widget-search-results-toolbar"></div>');
								this.DOM.searchResultsTotalLabel = $('<span>&nbsp;</span>');
								this.DOM.searchResultsQuickAddTypeSelect = $('<select><option value="-1"></option></select>');
								this.DOM.searchResultsQuickAddInput = $('<input type="text" value=""/>');
								this.DOM.searchResultsQuickAddButton = $('<a href="#" title="Quick Add '+this.Options.Label+'">Add '+this.Options.QuickAddLabel+'</a>')
									.click(function(event){
										me.quickAdd.apply(me,[event]);
									});
								this.DOM.searchResultsQuickAddMessage = $('<span class="quick-add-message loading">Adding. Please Wait...</span>').css({display:'none'});
					this.DOM.searchShadowRight = $('<div class="tag-widget-search-shadow-right"></div>');
			this.DOM.searchShadowBottom = $('<div class="tag-widget-search-shadow-bottom"><div></div></div>');
			this.DOM.searchIcon = $('<div class="tag-widget-search-icon"></div>');

		//ASSEMBLE THE SEARCH COMPONENT DOM
		this.DOM.searchContainer
			.append(this.DOM.searchClippingContainer)
			.append(this.DOM.searchShadowBottom)
			.append(this.DOM.searchIcon);

			this.DOM.searchClippingContainer
				.append(this.DOM.searchShadowTop)
				.append(this.DOM.searchShadowContainer);

				this.DOM.searchShadowContainer
					.append(this.DOM.searchContentContainer)
					.append(this.DOM.searchShadowRight);

					this.DOM.searchContentContainer
						.append(this.DOM.searchInput)
						.append(this.DOM.clearSearchButton)
						.append(this.DOM.closeButton)
						.append(this.DOM.searchResultsContainer);

						this.DOM.searchResultsContainer
							.append(this.DOM.searchResultsScrollPane)
							.append(this.DOM.searchResultsToolbar);

							this.DOM.searchResultsScrollPane
								.append(this.DOM.searchResultsList);

							this.DOM.searchResultsToolbar
								.append(this.DOM.searchResultsTotalLabel);
							if(this.Options.AllowQuickAdd && this._isMultiType()) {
							this.DOM.searchResultsToolbar
								.append(this.DOM.searchResultsQuickAddTypeSelect);
                                this.DOM.searchResultsQuickAddTypeSelect.empty();
								$.each(SystemService.getElementsByAspect(this.Options.QuickAddElement.substring(1)),function(i,element){
									me.DOM.searchResultsQuickAddTypeSelect.append($('<option value="'+element.Slug+'">'+element.Name+'</option>'));
								});
							}
							if(this.Options.AllowQuickAdd)
							this.DOM.searchResultsToolbar
								.append(this.DOM.searchResultsQuickAddInput)
								.append(this.DOM.searchResultsQuickAddButton)
								.append(this.DOM.searchResultsQuickAddMessage);


		this.DOM.chosenList = $('<ol></ol>');
		this._renderChosenList();

		this.DOM.chosenReorderList = $('<div class="sortable"></div>').css({display:'none'});


		//CREATE THE DOM REQUIRED FOR THE ITEM OPTIONS COMPONENT
		this.DOM.itemOptionsContainer = $('<div class="tag-widget-item-options-container"></div>').css({display:'none'});
			this.DOM.itemOptionsClippingContainer = $('<div class="tag-widget-item-options-clipping-container"></div>');
				this.DOM.itemOptionsShadowTop = $('<div class="tag-widget-item-options-shadow-top"><div></div></div>');
				this.DOM.itemOptionsShadowContainer = $('<div class="tag-widget-item-options-shadow-container"></div>');
					this.DOM.itemOptionsContentContainer = $('<div class="tag-widget-item-options-content-container"></div>');
						this.DOM.itemOptionsFormContainer = $('<div class="tag-widget-item-options-form-container"></div>');
							this.DOM.itemOptionsValuesContainer = $('<div class="tag-widget-item-options-values-container"></div>');
								this.DOM.itemOptionsCancelButton = $('<a class="cancel" href="#" title="Cancel">Cancel</a>');
								this.DOM.itemOptionsDoneButton = $('<a class="done" href="#" title="Done">Done</a>');
								this.DOM.itemOptionsLabel = $('<span></span>');
								this.DOM.itemOptionsList = $('<ul></ul>');
								this.DOM.itemOptionsTypeinInput = $('<input type="text"/>');
								this.DOM.itemOptionsAddButton = this.Options.AllowMultipleValues ? $('<a href="#" class="add" title="Add">Add</a>') : $('<a href="#" class="add" title="Save">Save</a>');
					this.DOM.itemOptionsShadowRight = $('<div class="tag-widget-item-options-shadow-right"></div>');
			this.DOM.itemOptionsShadowBottom = $('<div class="tag-widget-item-options-shadow-bottom"><div></div></div>');

		//ASSEMBLE THE ITEM OPTIONS COMPONENT DOM
		this.DOM.itemOptionsContainer
			.append(this.DOM.itemOptionsClippingContainer)
			.append(this.DOM.itemOptionsShadowBottom);

			this.DOM.itemOptionsClippingContainer
				.append(this.DOM.itemOptionsShadowTop)
				.append(this.DOM.itemOptionsShadowContainer);

				this.DOM.itemOptionsShadowContainer
					.append(this.DOM.itemOptionsContentContainer)
					.append(this.DOM.itemOptionsShadowRight);

					this.DOM.itemOptionsContentContainer
						.append(this.DOM.itemOptionsFormContainer);

						this.DOM.itemOptionsFormContainer
							.append(this.DOM.itemOptionsValuesContainer);

							this.DOM.itemOptionsValuesContainer
								.append(this.DOM.itemOptionsCancelButton)
								.append(this.DOM.itemOptionsDoneButton)
								.append(this.DOM.itemOptionsLabel)
								.append(this.DOM.itemOptionsList)
								.append($('<div style="clear:both;></div>'));
								if(this.Options.ValueMode == 'typein') {
									this.DOM.itemOptionsValuesContainer
										.append(this.DOM.itemOptionsTypeinInput)
										.append(this.DOM.itemOptionsAddButton);
								}




		//ASSEMBLE TOP-LEVEL WIDGET UI COMPONENTS
		this.DOM.container
			.append(this.DOM.label
				.append(this.Options.AllowClearChosenList && this.Options.ShowChosenList && !me.Options.ReadOnly ? this.DOM.clearChosenListButton : null)
				.append(this.Options.AllowReorderChosenList && this.Options.ShowChosenList && !me.Options.ReadOnly ? this.DOM.reorderChosenListButton : null)
				.append(this.Options.AllowReorderChosenList && this.Options.ShowChosenList ? this.DOM.reorderChosenListFinishButton : null)
				.append(this.Options.AllowRemoveUndo && this.Options.ShowChosenList ? this.DOM.undoRemoveButton : null)
				)
			.append(this.Options.ShowChosenList ? this.DOM.chosenList : null)
			.append(this.Options.ShowChosenList && !me.Options.ReadOnly ? this.DOM.chosenReorderList : null)
			.append(this.Options.ShowActivateButton && !me.Options.ReadOnly ? this.DOM.activateButton : null)
			.after($('<div style="clear:both;"></div>'))
			.after(this.Options.ShowChosenList && this.Options.ValueMode != 'none' ? this.DOM.itemOptionsContainer : null)
			.after(this.Options.ShowActivateButton ? this.DOM.searchContainer : null);


		//SET THE PARENT OF THE TAG WIDGET TO RELATIVE SO THE SEARCH AND ITEM OPTION PANELS CAN BE
		//POSITIONED CORRECTLY
		$(this.DOM.container.parent().get(0)).css({position:'relative'});


		//AUTOMATICALLY CLOSE WIDGET WHEN ANOTHER WIDGET ON THE PAGE IS ACTIVATED
		$(document).bind(AbstractTagWidget.EVENTS.WIDGET_ACTIVATED,function(event,widgetUUID){
            me._handleWidgetActivatedEvent.apply(me,[widgetUUID]);
		});

		//INITIALIZE QUICK ADD
		if(this.Options.AllowQuickAdd)
			this.DOM.searchContainer.addClass("quick-add");

		//INITIALIZE MULTI-TYPE
		if(this._isMultiType())
			this.DOM.searchContainer.addClass("multi-type");

		//ADD CLASS FOR ADD MULTIPLE/CHOOSE ONE
		this.DOM.searchContainer.addClass(this.Options.AllowMultiple?"multiple":"single");
		this.DOM.activateButton.addClass(this.Options.AllowMultiple?"multiple":"single");

        //If this is a prepend tag widget, move the activateButton to the top
        if(this.Options.TagPrepend){
            this.DOM.chosenList.before(this.DOM.activateButton);
            this.DOM.chosenList.css({float:'left'});
        }
	},

    _handleWidgetActivatedEvent : function(widgetUUID) {
        if(widgetUUID != this.UUID)
            this.closeWidget();
    },

    _handlePostInitialize : function() {
    },

	_handleTagsUpdated : function() {
		this._renderChosenList();
	},

	//USER INTERFACE ACTIONS
	activateWidget : function(event) {
		event.preventDefault();

		this.cancelItemOptions();

		//DISPATCH GLOBAL EVENT TO CLOSE OTHER ACTIVATED WIDGETS ON THE PAGE
		$(document).trigger(AbstractTagWidget.EVENTS.WIDGET_ACTIVATED,[this.UUID,this]);

		this._positionSearchContainer();


		//IE7 HACK
		//SET THE PARENT zIndex TO AN ABSOLUTE VALUE SO THE SEARCH CONTAINER SHOWS ON TOP
		$(this.DOM.container.parent().get(0)).css({zIndex:'2000'});


		this.DOM.searchContainer.show();
		this.DOM.searchResultsTotalLabel.text('Searching. Please Wait...').addClass('loading');

		this.DOM.searchInput.val(AbstractTagWidget.CONSTANTS.START_TYPING);
		this.DOM.searchResultsQuickAddInput.val('');
		this.DOM.searchInput[0].select();

		this.enterSearchKeyword('');

        if(this.Options.AutoExpandParentHeight)
            this.DOM.container.parent().css('minHeight',this.Options.ParentMinHeight);
	},

	enterSearchKeyword : function(keyword) {

		var me = this;

        if(keyword == AbstractTagWidget.CONSTANTS.START_TYPING)
            keyword = '';

		this.SearchOffset = 1;

		this.DOM.searchResultsTotalLabel.text('Searching. Please Wait...').addClass('loading');

        var nq = new NodeQuery(
            $.extend({
                //"Elements.in" --> comes from AbstractCmsBuilder::_buildWidgetOptions()
                "Title.like": keyword
            },this.Options.SearchParameters || {}),
            {
//                Title: 'ASC'
            },
            this.Options.SearchLimit,
            this.SearchOffset++
        );

        var successCallback = function(nodeQuery) {
            me._renderSearchResults.apply(me,[nodeQuery,false]);

            //RESET SCROLL PANE TO THE TOP, BROWSER WILL REMEMBER THE SCROLL POSITION BETWEEN LOADS WHICH
            //CAUSES THE AJAX SCROLL-AHEAD TO KEEP LOADING UP TO THE LAST SCROLL POSITION
            //FURTHER NOTE: jQuery scrollTo PLUGIN USES A TIMEOUT WHICH DOESN'T WORK HERE, NEEDS TO BE SEQUENTIAL
            me.DOM.searchResultsScrollPane[0].scrollTop = 0;
        };

        this._beforeFindAll(nq);

        this.DefaultService.findAll(nq,{
            success : successCallback,
            error: function(){
                me.closeWidget.apply(me);
            },
            url : this.Options.SearchURL == null ? undefined : this.Options.SearchURL
        });
	},

    _beforeFindAll : function(nq) {

    },

	clearSearch : function(event) {
		event.preventDefault();
		this.DOM.searchInput.val('')[0].focus();
		this.enterSearchKeyword('');
	},

	closeWidget : function(event) {
		if(typeof event != 'undefined')
			event.preventDefault();

		this.DOM.searchContainer.hide();

		this.DOM.searchInput.val('');
		this.DOM.searchResultsList.empty();
		this.DOM.searchResultsTotalLabel.empty().removeClass('loading');

		//IE7 HACK
		//CLEAR THE PARENT zIndex
		$(this.DOM.container.parent().get(0)).css({zIndex:''});

        $(document).trigger(AbstractTagWidget.EVENTS.WIDGET_DEACTIVATED,[this.UUID]);

        if(this.Options.AutoExpandParentHeight)
            this.DOM.container.parent().css('minHeight','');
	},

	scroll : function(event) {

		//SEARCH RESULTS FULLY LOADED, NO NEED TO CONTINUE
		if(this.TotalRecords == this.DOM.searchResultsList.children().length)
			return;

		var pane = $(event.target);
		var ratio = (pane[0].scrollTop + pane.height()) / this.DOM.searchResultsList.height();

		if(ratio >= (this.Options.ScrollThreshold/100.0)) { //WE HAVE HIT THE 80% MARK ON THE SCROLL PANE; LOAD MORE SEARCH RESULTS

			if(this.Locks.scroll) {
				event.preventDefault();
				return;
			}

			this.Locks.scroll = true;

			var me = this;

			var keyword = this.DOM.searchInput.val();
			if(keyword == AbstractTagWidget.CONSTANTS.START_TYPING)
				keyword = '';

            var nq = new NodeQuery(
                $.extend({
                    //"Elements.in" --> comes from AbstractCmsBuilder::_buildWidgetOptions()
                    "Title.like": keyword
                },this.Options.SearchParameters || {}),
                {
//                    Title: 'ASC'
                },
                this.Options.SearchLimit,
                this.SearchOffset++
            );

            var successCallback = function(nodeQuery) {
                me._renderSearchResults.apply(me,[nodeQuery,true]);
                me.Locks.scroll = false;
            };

            this._beforeFindAll(nq);

            this.DefaultService.findAll(nq,{
                success : successCallback,
                error : function(){
                    me.closeWidget.apply(me);
                },
                url : this.Options.SearchURL == null ? undefined : this.Options.SearchURL
            });
		}
	},

	chooseItem : function(node,choiceli) {
		var me = this;

		var newtag = this._buildTag();

        newtag.TagElement = node.Element.Slug;
		newtag.TagSlug = node.Slug;
		newtag.TagLinkTitle = node.Title;
		newtag.TagLinkURL = node.RecordLink;
        newtag.TagLinkNode = node instanceof NodeObject ? node : new NodeObject(node);

		//CHECK IF THIS TAG IS ALREADY IN THE CHOSEN LIST; IF SO, ADD A CSS CLASS AND CANCEL THIS EVENT
		var newPartial = newtag.toPartial();
		if($.grep(this.DOM.chosenList.children(),function(li){
			return newPartial.isMatch(li.getTag());
		}).length > 0) {
			choiceli && choiceli.addClass("chosen");
			return;
		}

		if(this.Options.ValueMode != 'none' && choiceli && !this.Options.HideTagValues) {

			//TODO: optimize this code, repeats a bit with showItemOptions()
			var valueslist = $('<ul></ul>');

			var buildOption = function(i,option){
				var optionli = $('<li></li>');

				var id = me.UUID+'-opt-'+(i+1);

				var optioninput = $('<input id="'+id+'" value="'+option.value+'" type="'+(me.Options.AllowMultipleValues?"checkbox":"radio")+'"/>');
				optioninput.attr('name',me.UUID+'-optval');

				//CREATE A HELPER METHOD TO RETRIEVE THE VALUE/DISPLAY FOR THIS OPTION INPUT
				optioninput[0].getOptionValue = function() {
					return option;
				};
				optionli.append(optioninput);
				optionli.append($('<label for="'+id+'">'+option.display+'</label>'));
				valueslist.append(optionli);
			};

			if(this.Options.ValueMode == 'predefined') {

				$(this.Options.Values).each(buildOption);

			} else if(this.Options.ValueMode == 'typein') {

				$(this.ExistingValues).each(buildOption);
			}

			choiceli.append($('<a class="cancel" href="#" title="Cancel">Cancel</a>').click(function(event){
				event.preventDefault();
				$('a.choice-link',choiceli).nextAll().remove();
				choiceli.removeClass("highlight");
				me._itemChoiceOptionsCancelled.apply(me,[choiceli]);
			}));
			var doneButton = $('<a class="done" href="#" title="Done">Done</a>').click(function(event){
				event.preventDefault();

				if(me.Options.AllowMultipleValues) {

					var newtags = new Array();

					//GET ALL THE INPUTS IN THE itemOptionsList TO DETERMINE WHICH ONES ARE CHECKED
					$('ul input',choiceli).each(function(i,input){
						if($(input).attr('checked')) {
							var tag = new Tag(newtag);

							tag.TagValue = input.getOptionValue().value;
							tag.TagValueDisplay = input.getOptionValue().display;

							newtags.push(tag);
						}
					});

					if(newtags.length > 0) {
						me.doTag(newtags);
						//cleanup
						$('a.choice-link',choiceli).nextAll().remove();
						choiceli.removeClass("highlight").addClass("chosen");
					}

				} else {

					var newvalue = null;

					//GET ALL THE INPUTS IN THE itemOptionsList TO DETERMINE WHICH ONE IS CHECKED
					$('ul input',choiceli).each(function(i,input){
						if($(input).attr('checked'))
							newvalue = input.getOptionValue();
					});

					if(newvalue) {
						var tag = new Tag(newtag);

						tag.TagValue = newvalue.value;
						tag.TagValueDisplay = newvalue.display;

                        me.doTag(tag);

						//cleanup
						$('a.choice-link',choiceli).nextAll().remove();
						choiceli.removeClass("highlight").addClass("chosen");
					}
				}
			});
			choiceli.append(doneButton);
			choiceli.append(valueslist);
			choiceli.append($('<div style="clear:both"><div></div></div>'));

			//BUILD TYPEIN CONTROLS
			if(this.Options.ValueMode == 'typein') {

				var input = $('<input type="text"/>');

				choiceli.append(input);

				var buildOptionLI = function() {
					var optionli = $('<li></li>');

					var id = me.UUID+'-opt-'+(valueslist.children().length+1);

					var value = SlugUtils.createSlug(input.val());
					var display = input.val();
					var optioninput = $('<input id="'+id+'" value="'+value+'" type="checkbox"/>');
					optioninput.attr('name',me.UUID+'-optval');
					optioninput.attr('checked','checked');

					//CREATE A HELPER METHOD TO RETRIEVE THE VALUE/DISPLAY FOR THIS OPTION INPUT
					optioninput[0].getOptionValue = function() {
						return {value:value,display:display};
					};
					optionli.append(optioninput);
					optionli.append($('<label for="'+id+'">'+display+'</label>'));
					valueslist.append(optionli);
				};

				if(this.Options.AllowMultipleValues) {
					choiceli.append($('<a href="#" class="add" title="Add">Add</a>').click(function(event){
						event.preventDefault();
						buildOptionLI();
						input.val('')[0].focus();
					}));
				} else {
					choiceli.append($('<a href="#" class="add" title="Save">Save</a>').click(function(event){
						event.preventDefault();
						buildOptionLI();
						doneButton.click();
					}));
				}
			}
			////

			choiceli.addClass("highlight");

		} else {
            if(this.Options.ValueMode != 'none' && this.Options.HideTagValues) {
                newtag.TagValue = '';
                newtag.TagValueDisplay = '';
            }

            me.doTag(newtag);

			if(this.Options.AllowMultiple) {
				choiceli && this.Options.ShowChosenList && choiceli.addClass("chosen");
			}
		}

		this._itemChosen(node,choiceli);
	},

    doTag : function(newtag, supressEvents){
        var me = this;

        //set default values for optional parameters
        if(supressEvents === undefined){
            supressEvents = false;
        }

        var tag = new Array();

        if(newtag instanceof Array){
            tag = $.merge([],newtag);
        }else{
            tag = [newtag];
        }
        //tagging logic
            if(me.Options.AllowMultiple && !me.Options.TagPrepend) {
                $.each(tag, function(i,val){
                    me.taggableObject.addTag(me.Options.TagDirection,val,supressEvents);
                });
            } else if(me.Options.AllowMultiple && me.Options.TagPrepend){
                var curTags = me.taggableObject.getTags(me.Options.TagDirection,me.tagPartial);
                me.taggableObject.removeTags(me.Options.TagDirection,me.tagPartial);
                $.merge(tag,curTags);

                $.each(tag, function(i,val){
                    val.TagSortOrder = i+1;
                    me.taggableObject.addTag(me.Options.TagDirection,val,supressEvents);
                });
            } else {
                me.taggableObject.removeTag(me.Options.TagDirection,me.tagPartial,true);
                me.taggableObject.addTag(me.Options.TagDirection,tag[0],supressEvents);
                me.closeWidget.apply(me);

            }

    },

	showItemOptions : function(chosenli,cancelCallback) {

		this.closeWidget();

		var me = this;
		var tag = chosenli[0].getTag();

		this.DOM.itemOptionsList.empty();
		this.DOM.itemOptionsTypeinInput.val('');
		this.DOM.itemOptionsLabel.text(tag.TagLinkTitle);

		if(typeof cancelCallback != 'function') {
			cancelCallback = function(event){
				event.preventDefault();
				me.cancelItemOptions.apply(me);
			};
		}
		this.DOM.itemOptionsCancelButton.unbind('click').click(cancelCallback);

		this.DOM.itemOptionsDoneButton.unbind('click').click(function(event){
			event.preventDefault();
			me._itemOptionsChanged.apply(me,[tag]);
		});


		//ADD TYPEIN INPUT AND BUTTON EVENTS
		if(this.Options.ValueMode == 'typein') {

			var buildOptionLI = function() {
				var optionli = $('<li></li>');

				var id = me.UUID+'-opt-'+(me.DOM.itemOptionsList.children().length+1);

				var value = SlugUtils.createSlug(me.DOM.itemOptionsTypeinInput.val());
				var display = me.DOM.itemOptionsTypeinInput.val();
				var optioninput = $('<input id="'+id+'" value="'+value+'" type="checkbox"/>');
				optioninput.attr('name',me.UUID+'-optval');
				optioninput.attr('checked','checked');

				//CREATE A HELPER METHOD TO RETRIEVE THE VALUE/DISPLAY FOR THIS OPTION INPUT
				optioninput[0].getOptionValue = function() {
					return {value:value,display:display};
				};
				optionli.append(optioninput);
				optionli.append($('<label for="'+id+'">'+display+'</label>'));
				me.DOM.itemOptionsList.append(optionli);
			};

			if(this.Options.AllowMultipleValues) {
				this.DOM.itemOptionsAddButton.unbind('click').click(function(event){
					event.preventDefault();
					buildOptionLI();
					me.DOM.itemOptionsTypeinInput.val('')[0].focus();
				});
			} else {
				this.DOM.itemOptionsAddButton.unbind('click').click(function(event){
					event.preventDefault();
					buildOptionLI();
					me._itemOptionsChanged.apply(me,[tag]);
				});
			}
		}



		//BUILD VALUE OPTIONS
		var buildOption = function(i,option){
			var optionli = $('<li></li>');

			var id = me.UUID+'-opt-'+i;

			var optioninput = $('<input id="'+id+'" value="'+option.value+'" type="'+(me.Options.AllowMultipleValues?"checkbox":"radio")+'"/>');
			optioninput.attr('name',me.UUID+'-optval');

			//DETERMINE IF THIS OPTION INPUT MATCHES ONE OF THE VALUES FOR THIS CHOSEN
			$(chosenli[0].getValues()).each(function(j,val){
				if(val.value==option.value) {
					optioninput.attr('checked','checked');
					return false;
				}
			});

			//CREATE A HELPER METHOD TO RETRIEVE THE VALUE/DISPLAY FOR THIS OPTION INPUT
			optioninput[0].getOptionValue = function() {
				return option;
			};
			optionli.append(optioninput);
			optionli.append($('<label for="'+id+'">'+option.display+'</label>'));
			me.DOM.itemOptionsList.append(optionli);
		};

		if(this.Options.ValueMode == 'predefined') {

			$(this.Options.Values).each(buildOption);

		} else if(this.Options.ValueMode == 'typein') {

			$(chosenli[0].getValues()).each(buildOption);

			//ADD IN THE EXISTING VALUES WHICH DO NOT ALREADY OCCUR ON THE CHOSEN (BUILD A UNIQUE SET)
			if(this.ShowExistingValues)
				$($.grep(this.ExistingValues,function(v){
					var vals = chosenli[0].getValues();
					for(var i = 0; i < vals.length; i++)
						if(v.value == vals[i].value)
							return false;
					return true;
				})).each(buildOption);
		}


		//IE7 HACK
		//SET THE PARENT zIndex TO AN ABSOLUTE VALUE SO THE SEARCH CONTAINER SHOWS ON TOP
		$(this.DOM.container.parent().get(0)).css({zIndex:'2000'});


		//POSITION & SIZE PANEL OVER THE CHOSEN LI
		this.DOM.itemOptionsContainer.show();

		var height = { height : (this.DOM.itemOptionsContentContainer.height()+2) + "px"};
		this.DOM.itemOptionsContainer.css(height);
		this.DOM.itemOptionsClippingContainer.css(height);

		this.DOM.itemOptionsContainer.css({top:(chosenli.position().top-2)+'px',left:'6px'});
	},

	cancelItemOptions : function() {
		//IE7 HACK
		//CLEAR THE PARENT zIndex
		$(this.DOM.container.parent().get(0)).css({zIndex:''});

		this.DOM.itemOptionsContainer.hide();
	},

	clearChosenItems : function(event) {
        if(event)
            event.preventDefault();
		this.taggableObject.removeTags(this.Options.TagDirection,this.tagPartial);

		this._chosenItemsCleared();
	},

	removeItem : function(tagpartial) {
		this.taggableObject.removeTags(this.Options.TagDirection,tagpartial);
		this.closeWidget();
		this._itemRemoved(tagpartial);
	},

	activateReordering : function(event) {
		event.preventDefault();

		this.closeWidget();

		//BUILD SORTABLE LIST
		this.DOM.chosenReorderList.empty();//should always be empty, remove this?
		var list = $('<ol></ol>');
		this.DOM.chosenReorderList.append(list);

		this.DOM.chosenList.children().each(function(i,li){
			var newli = $('<li></li>');

			newli.append($(li).find('span.display').text()+' '+$(li).find('span.value').text());

			//COPY THE getTag() FUNCTION OVER TO SORTABLE LI
			//THIS IS USED LATER TO REORDER THE TAGS
			newli[0].getTag = li.getTag;

			//COPY THE getValues() FUNCTION OVER TO SORTABLE LI
			//THIS IS USED LATER TO REORDER THE TAGS
			newli[0].getValues = li.getValues;

			list.append(newli);
		});

		//ADD ALTERNATING CSS CLASS
		//list.find("li:nth-child(even)").addClass("striped");

		//CONFIGURE AND ENABLE SORTING
		list.sortable({
				revert: false,
				scroll: false,
				axis: 'y',
				update: function() {
					//UPDATE ALTERNATING CSS CLASS
					//list.children().removeClass("striped").not("li:nth-child(odd)").addClass("striped");
				}
			}).sortable('enable');

		//UPDATE UI COMPONENTS
		this.DOM.clearChosenListButton.hide();
		this.DOM.reorderChosenListButton.hide();
		this.DOM.activateButton.hide();
		this.DOM.chosenList.hide();

		this.DOM.chosenReorderList.show();
		this.DOM.reorderChosenListFinishButton.show();

		this._reorderingActivated();
	},

	deactivateReordering : function(event) {
		event.preventDefault();

		//COMMIT CHANGES TO taggableObject
		var newtags = new Array();
		this.DOM.chosenReorderList.find("li").each(function(i,li){
			var vals = li.getValues();
			$(vals).each(function(j,val){
				var tag = new Tag(li.getTag());
				tag.TagValue = val.value;
				tag.TagValueDisplay = val.display;
				tag.TagSortOrder = i+1;
				newtags.push(tag);
			});
		});
		this.taggableObject.updateTags(this.Options.TagDirection,this.tagPartial,newtags);

		//CLEAN UP SORTABLE LIST
		this.DOM.chosenReorderList.children().sortable('destroy').remove();

		//UPDATE UI COMPONENTS
		this.DOM.clearChosenListButton.show();
		this.DOM.reorderChosenListButton.show();
		this.DOM.activateButton.show();
		this.DOM.chosenList.show();

		this.DOM.chosenReorderList.hide();
		this.DOM.reorderChosenListFinishButton.hide();

		this._reorderingDeactivated();
	},

	processSearchInputKeyDown : function(event){
		var me = this;

		if(this.Locks.searchInput) { //THIS MAY NOT BE NECESSARY, RE-EVALUATE LATER
			event.preventDefault();
			return;
		}

		//CATCH THIS KEYPRESS, OTHERWISE AN ESC CHARACTER GOES TO THE SEARCH
		if(event.keyCode == 27 /*ESC*/) {
			event.preventDefault();
			return;
		}

//		if(!isKeyCodeAlphaNumeric(event.keyCode) &&
//		   event.keyCode != 8  /*BACKSPACE*/ &&
//		   event.keyCode != 32 /*SPACE*/ &&
//		   event.keyCode != 222/* " */ &&
//		   event.keyCode != 188/* , */ &&
//		   event.keyCode != 191/* / */ &&
//		   event.keyCode != 220/* \ */ &&
//		   event.keyCode != 0  /* ?_ */ &&
//		   event.keyCode != 109/* - */ &&
//		   event.keyCode != 107/* + */
//		) {
//			event.preventDefault();
//			return;
//		}

		if(this.Timers.searchInput)
			window.clearTimeout(this.Timers.searchInput);

		this.Timers.searchInput = setTimeout(function(){
			me.Locks.searchInput = true;
			me.enterSearchKeyword.apply(me,[me.DOM.searchInput.val()]);
			me.Locks.searchInput = false;
		},this.Options.TypeAheadSearchDelay);
	},

	_buildTag: function() {

		var newtag = new Tag({
			TagElement : '',
			TagSlug: '',
			TagRole : this.tagPartial.TagRole,
			TagValue: '',
			TagValueDisplay: '',
			TagSortOrder : this.Options.AllowReorderChosenList ? this.DOM.chosenList.children().length + 1 : 0
		});

		if(this.Options.TagDirection == 'in'){
			if(!this.Options.AllowMultiple) {
				newtag.MatchPartial = newtag.toPartial();
			}
		}

		return newtag;
	},

	quickAdd : function(event) {
		event.preventDefault();

		var me = this;

		var val = $.trim(this.DOM.searchResultsQuickAddInput.val());

		if(val.length == 0) {
			this.DOM.searchResultsQuickAddInput.val('').focus();
			return;
		}

		var type = this.Options.QuickAddElement;
		if(this._isMultiType()) {
            type = $.trim(this.DOM.searchResultsQuickAddTypeSelect.val());
            if(type == "-1") {
                this.DOM.searchResultsQuickAddTypeSelect.focus();
                return;
            }
		}

		var callback = function(node) {

			var newtag = me._buildTag.apply(me);

			newtag.TagElement = type != null ? type : node.Element.Slug;
			newtag.TagSlug = node.Slug;
			newtag.TagLinkTitle = node.Title;

			me.DOM.searchResultsQuickAddMessage.hide();
			me.DOM.searchResultsQuickAddInput.show();
			me.DOM.searchResultsQuickAddButton.show();

			me.DOM.searchResultsQuickAddInput.val('').focus();

			if(me.Options.ValueMode != 'none' && !me.Options.HideTagValues) {

				var existingTags = null;

				//ADD NEW TAG BUT SUPRESS EVENTS
				//WE DONT WANT THE CHOSEN LIST TO RE-RENDER JUST YET

                me.doTag(newtag,true);

				if(!me.Options.AllowMultiple) {
					//SAVE EXISTING TAGS, NEED TO BE REPLACE IF Cancel IS CLICKED
				    existingTags = me.taggableObject.getTags(me.Options.TagDirection,me.tagPartial);
				}

				me.closeWidget.apply(me);

				//ADD A TEMPORARY LINE TO THE CHOSEN LIST (WONT BE SEEN)
				var li = $('<li>Temporary Line</li>');

				//BIND A FUNCTION TO THE LI THAT RETURNS THE TAG OBJECT REPRESENTING THAT LINE
				li[0].getTag = function() {
					return newtag;
				};

				//BIND A FUNCTION TO THE LI THAT RETURNS THE TAG VALUES, NONE IN THIS CASE
				li[0].getValues = function() {
					return [];
				};

				me.DOM.chosenList.append(li);

				//SHOW THE ITEM OPTIONS PASSING A CUSTOM CANCEL ACTION WHICH REVERSES WHAT WE DID ABOVE
				me.showItemOptions(li,function(event){
					event.preventDefault();
					//REMOVE THE TEMPORARY CHOSEN LINE
					li.remove();

					if(me.Options.AllowMultiple) {
						//REMOVE THE TAG THAT WE ADDED EARLIER, SUPRESS EVENTS
						me.taggableObject.removeTag(me.Options.TagDirection,newtag.toPartial(),true);
					} else {
						//WE NEED TO REPLACE THE OLD EXISTING TAGS TO UNDO THIS QUCK ADD
						me.taggableObject.removeTags(me.Options.TagDirection,me.tagPartial,existingTags==true);
						if(existingTags) {
							me.taggableObject.addMoreTags(me.Options.TagDirection,existingTags);
						}
					}

					//BUBBLE UP TO ORIGINAL EVENT HANDLER
					me.cancelItemOptions.apply(me);
				});
			} else {

                if(me.Options.ValueMode != 'none' && me.Options.HideTagValues) {
                    newtag.TagValue = '';
                    newtag.TagValueDisplay = '';
                }

                me.doTag(newtag);

			}
			me.closeWidget.apply(me);

			me._afterQuickAdd.apply(me,[newtag,node]);
		};

		this.DOM.searchResultsQuickAddInput.hide();
		this.DOM.searchResultsQuickAddButton.hide();
		this.DOM.searchResultsQuickAddMessage.show();

        var node = new NodeObject();

        node.Element = {};
        node.Element.Slug = type;
        node.Title = val;

        this._beforeQuickAdd.apply(this,[node]);

        this.DefaultService.replace(node,{
            nonce : this.Options.ReplaceNonce,
            success : callback,
            url : this.Options.QuickAddURL == null ? undefined : this.Options.QuickAddURL
        });
	},

	//USER INTERFACE EVENT HANDLERS

	_beforeQuickAdd: function(node) {


	},

	_afterQuickAdd: function(tag,node) {


	},

	_itemChoiceOptionsCancelled : function(choiceli) {

	},

	_itemChosen : function(element,choiceli) {
	},


	_itemOptionsChanged : function(tag) {

		var tagPartial = tag.toPartial();

		if(this.Options.AllowMultipleValues) {

			var newtags = new Array();

			//GET ALL THE INPUTS IN THE itemOptionsList TO DETERMINE WHICH ONES ARE CHECKED
			$('input',this.DOM.itemOptionsList).each(function(i,input){
				if($(input).attr('checked')) {
					var newtag = new Tag(tag);

					newtag.TagValue = input.getOptionValue().value;
					newtag.TagValueDisplay = input.getOptionValue().display;

					newtags.push(newtag);
				}
			});

			if(newtags.length > 0) {

				//NULL OUT TAGVALUE BECAUSE WE DONT WANT TO MATCH ON VALUE
				tagPartial.TagValue = null;

				this.taggableObject.replaceTags(this.Options.TagDirection,tagPartial,newtags);

				this.cancelItemOptions();
			}

		} else {

			var newvalue = null;

			//GET ALL THE INPUTS IN THE itemOptionsList TO DETERMINE WHICH ONE IS CHECKED
			$('input',this.DOM.itemOptionsList).each(function(i,input){
				if($(input).attr('checked'))
					newvalue = input.getOptionValue();
			});

			if(newvalue) {
				var newtag = new Tag(tag);

				newtag.TagValue = newvalue.value;
				newtag.TagValueDisplay = newvalue.display;

				this.taggableObject.replaceTag(this.Options.TagDirection,tagPartial,newtag);

				this.cancelItemOptions();
			}
		}
	},

	_itemRemoved : function(tagpartial) {
	},

	_reorderingActivated : function() {
	},

	_reorderingDeactivated : function() {
	},

	_chosenItemsCleared : function() {
	},

	_renderChosenList : function() {

		var me = this;

		this.DOM.chosenList.empty();

		//GET ALL THE OUT TAGS FROM THE RECORD OBJECT THAT APPLY TO THIS WIDGET
		var outTags = this.Options.ShowChosenList ? this.taggableObject.getTags(this.Options.TagDirection,this.tagPartial) : new Array();

		//IT'S POSSIBLE THAT THERE COULD BE SEQUENTIAL TAGS REPRESENTING A MUTLI-VALUED TAG
		//LOOP OBJECT RESETS WHEN SLUG CHANGES
		var previousSlug = null;
        var previousElement = null;
		var previousLI = null;

		$(outTags).each(function(i,tag){

			if(tag.TagSlug == previousSlug && tag.TagElement == previousElement) {

				//APPEND THE VALUE TO THE ARRAY RETURNED BY THE getValues() FUNCTION
				var vals = previousLI[0].getValues();
				vals.push({value:tag.TagValue,display:tag.TagValueDisplay});
				previousLI[0].getValues = function() {
					return vals;
				};

				//ASSUME THE span.value CONTAINS A [, ]-SEPARATED LIST OF STRINGS ENCLOSED BY PARENS
				var val = $('span.value',previousLI);
				val.text(val.text().substr(0,val.text().length-1)+', '+tag.TagValueDisplay+')');

			} else {

				var li = $('<li></li>');

				if(me.Options.ValueMode != "none" && !me.Options.HideTagValues) {

					//BUILD CHOSEN LINE
					//TAG LINK DISPLAY BECOMES AN ANCHOR LINK TO EDIT THE VALUES
					if(me.Options.AllowEditChosen && !me.Options.ReadOnly)
						li.append($('<span class="display"></span>').append($('<a href="#" title="Edit">'+tag.TagLinkTitle+'</a>').click(function(event){
							event.preventDefault();
							me.showItemOptions.apply(me,[li]);
						})));
					else
						li.append($('<span class="display">'+tag.TagLinkTitle+(me._isMultiType() || me.Options.ShowElementInChosenList?' <em>('+SystemService.getElementBySlug(tag.TagElement).Name+')</em>':'')+'</span>'));

					//SHOW THE TAG VALUE IN PARENS AFTER THE LINK DISPLAY, THIS WILL GET APPENDED TO IF THERE ARE ANY MORE TAGS WITH THE SAME SLUG (SEE ABOVE)
					if(tag.TagValueDisplay != null && tag.TagValueDisplay.length > 0) {
						li.append($('<span class="value">('+tag.TagValueDisplay+')</span>'));
					}

				} else {
					//NO TAG VALUES, JUST SHOW THE LINK DISPLAY
					li.append($('<span class="display">'+tag.TagLinkTitle+(me._isMultiType() || me.Options.ShowElementInChosenList?' <em>('+SystemService.getElementBySlug(tag.TagElement).Name+')</em>':'')+'</span>'));

                    if(me.Options.HideTagValues && tag.TagValueDisplay != null && tag.TagValueDisplay.length > 0) {
                        li.append($('<span class="value">('+tag.TagValueDisplay+')</span>'));
                    }
				}

				//BIND A FUNCTION TO THE LI THAT RETURNS THE TAG OBJECT REPRESENTING THAT LINE
				li[0].getTag = function() {
					return tag;
				};

				//BIND A FUNCTION TO THE LI THAT RETURNS THE TAG VALUES
				li[0].getValues = function() {
					return [{value:tag.TagValue,display:tag.TagValueDisplay}];
				};

				if(me.Options.ShowRemoveButton && !me.Options.ReadOnly)
					li.prepend(
						$('<a href="#" title="Remove">&times;</a>').addClass("remove")
							.click(function(event){
								event.preventDefault();

                                if(me.Options.WarnOnRemove && !confirm("Remove this item?")) {
                                    return;
                                }

								var tagpartial = new TagPartial(
										tag.TagElement,
										tag.TagSlug,
										tag.TagRole
									);

								//SAVE TAGS FOR UNDO, UPDATE BEHAVIOR FOR UNDO BUTTON
								var savetags = me.taggableObject.getTags(me.Options.TagDirection,tagpartial);
								me.DOM.undoRemoveButton.show().unbind('click').click(function(event){
									event.preventDefault();
									me.taggableObject.addMoreTags(me.Options.TagDirection,savetags);
									me.DOM.undoRemoveButton.hide();
								});

								me.removeItem.apply(me,[tagpartial]);
							})
					);

				if(me.Options.ShowChosenLink && !me.Options.ReadOnly &&
                   typeof tag.TagLinkURL != 'undefined' &&
                   tag.TagLinkURL != null &&
                   tag.TagLinkURL != '' &&
                   tag.TagLinkNode.Status == 'published') {
					li.append($('<a class="record-link" href="'+tag.TagLinkURL+'" target="_blank">&raquo;</a>'));
				}

				me.DOM.chosenList.append(li);

				//CALL EXTENSION FUNCTION
				me._postRenderChosen.apply(me,[li,i+1]);


				previousSlug = tag.TagSlug;
				previousElement = tag.TagElement;
                previousLI = li;
			}
		});


		//UPDATE UI COMPONENTS THAT ARE AFFECTED BY THE CHOSEN LIST
		//this.DOM.chosenList.find(":nth-child(even)").addClass("striped");

		if(this.DOM.chosenList.children().length == 0) {
			this.DOM.clearChosenListButton.css({display:'none'});
			if(!this.Options.ShowLabelWhenEmpty) {
				this.DOM.label.css({display:'none'});
			}
		} else {
			this.DOM.clearChosenListButton.css({display:''});
			this.DOM.label.css({display:''});
		}

		if(this.Options.AllowReorderChosenList)
			if(this.DOM.chosenList.children().length > 1)
				this.DOM.reorderChosenListButton.show();
			else
				this.DOM.reorderChosenListButton.hide();


		this._positionSearchContainer();
	},

	_renderSearchResults : function(nodeQuery,append){

		var me = this;

        // If the append flag is set, new results will be appended to the
        // result list even if the nodequery's totalrecords is 0.  If a query
        // is out of range and comes back empty with totalrecords = 0, we
        // don't want to clear the results.
        append = (typeof append == 'undefined') || append; //DEFAULT TO APPEND
        if(!append) {
            this.DOM.searchResultsList.empty();
        }

		if(nodeQuery.getTotalRecords() > 0) {
			$(nodeQuery.getResults()).each(function(i,node){
                node = new NodeObject(node);
				var li = $('<li><span class="line-number"></span></li>');
				var choice = $('<a class="choice-link" href="#" title="Choose">'+node.Title+(me._isMultiType() || me.Options.ShowElementInSearchResults?' <em>('+SystemService.getElementBySlug(node.Element.Slug).Name+')</em>':'')+'</a>')
					.click(function(event){
						event.preventDefault();

						//THE CHOICE LINE IS ALREADY ACTIVE AND SELECTED (USED WITH predefined & typein)
						if(li.hasClass("highlight")) return;

						me.chooseItem.apply(me,[node,li]);
					})
                    .dblclick(function(event){
                        event.preventDefault();

                        //THE CHOICE LINE IS ALREADY ACTIVE AND SELECTED (USED WITH predefined & typein)
                        if(li.hasClass("highlight")) return;

                        me.chooseItem.apply(me,[node,li]);

                        if(me.Options.AllowMultiple)
                            me.closeWidget.apply(me);
                    });

				if(me.Options.ShowSlugInSearchResults) {
					choice.append($('<br/><em>'+node.Slug+'</em>'));
				}

				li.append(choice);
				me.DOM.searchResultsList.append(li);

				me._postRenderSearchResult.apply(me,[li,node,i+1,nodeQuery.getTotalRecords()]);
			});

			//SAVE TOTAL RECORDS SO WE CAN PREVENT UNNECESSARY SCROLL LOADS
			this.TotalRecords = nodeQuery.getTotalRecords();

			this.DOM.searchResultsTotalLabel.text(this.TotalRecords+" records found.").removeClass('loading');

			//ADD STRIPING TO SEARCH RESULTS
			this.DOM.searchResultsList.find('li:nth-child(even)').addClass('striped');

			if(this.Options.ShowLineNumbers)
				this.DOM.searchResultsList.find('span.line-number').each(function(i,span){
					if(i+1 <= 999) $(span).text(i+1); //ONLY 3 DIGITS FIT IN THE LEFT MARGIN
				});

		} else {

            if (!append) {
			    this.DOM.searchResultsTotalLabel.text("No records found.").removeClass('loading');
            }
		}

		var v = this.DOM.searchInput.val();
		if(this.Options.AllowQuickAdd && v != AbstractTagWidget.CONSTANTS.START_TYPING)
			this.DOM.searchResultsQuickAddInput.val(v);

	},

	_positionSearchContainer : function() {
		try {
			/*
			 							BROWSER
			.----------------------------------------------------------------.
			| http://cms.crowdfusion.com/pages/                              |
			.----------------------------------------------------------------.
			|       |                                                        |
			|<------|-----A------------------------------------------------->|
			|       |                         |                              |
			|       |                         v                              |
			|       |                     ->G F                              |
			|<------|-----------B---------->( BUTTON )                       |
			|       |                       .---------------.                |
			|       |                       |   SEARCH      |                |
			|       |                       |               |                |
			|<--D-->|                       |<--------C---->|                |
			|       |                       |               |                |
			|       |                       .---------------.                |
			|       |                                                        |
			|       |<---------E-------------------------------------------->|
			| LEFT  |                                                        |
			| BAR   |  APP CONTENT SCROLLABLE                           <-H->|
			|       |                                                        |
			.----------------------------------------------------------------.

			 */


			var A = $(window).width();
			var B = this.DOM.activateButton.offset().left;
			var C = this.DOM.searchContainer.width();
			var D = $(this.Options.Viewport).offset().left;
			var E = $(this.Options.Viewport).width();
			var F = this.DOM.activateButton.position().top;
			var G = this.DOM.activateButton.position().left;
			var H = 5; /* padding */

//			console_log('A = '+A);
//			console_log('B = '+B);
//			console_log('C = '+C);
//			console_log('D = '+D);
//			console_log('E = '+E);
//			console_log('F = '+F);
//			console_log('G = '+G);
//			console_log('H = '+H);

			var offset = G - 4; /* 4 is the drop shadow width of the search container */
			if(B - D + C > E - H)
				offset = E - H - (B - D + C);

//			console_log('Offset = '+offset);

			this.DOM.searchContainer.css({top:(F-2)+"px",left:offset+"px"});
		}catch(e){}
	},

    //MULTI-TYPE FUNCTIONALITY IS BASED ON THE SPECIFIED QUICKADD ELEMENT (OR ASPECT)
	_isMultiType : function() {

        return this.QuickAddElementCount > 1;
	},

	//PRIVATE DOM MANIPULATION ROUTINES
	//TODO: as extension points are identified, extract the logic into functions in this section

	_postRenderChosen : function(li,index) {
		//TODO: override this function to customize the rendering of the chosen li when the chosen list is rendered
	},

	_postRenderSearchResult : function(li,node,index/*starts at 1*/,total) {
		//TODO: override this function to customize the rendering of the search result li when the search result list is rendered
	}
};

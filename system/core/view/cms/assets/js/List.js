var List = function() {

	/*
	 * VARIABLES
	 */

	var t = this;
	var aspect = null;
	var resizeDelay;

	var currentRequest = null;
	var currentPostRequest = null;
	var dataBody = null;
	var dataTable = null;
	var firstRow = null;
	var headingsTable = null;
	var lastKeyPressCode = null;

	var totals = null;

	var scrollTop = null;
	var listdata = null;
	var loadingResults = false;
	var showingAll = false;

	var clickedLink = false;
    var holdingKey = false;

	var filterCookie = {};
	var sortCookie = {};

    var populateDelayTimer = null;
    var setPopulateDelayTimer = function() {
        return setTimeout(function(){
            $(document).trigger('list_populate_complete');
        },500);
    };
    var clearPopulateDelayTimer = function() {
        if(populateDelayTimer != null) {
            clearTimeout(populateDelayTimer);
        }
    };



	/*
	 * OPTIONS
	 */

	var opt = {
        listDataStubUrl: 'ajax/list-data/',
		templateStubUrl: 'ajax/template/',
		listDataTemplate: 'node/list-data.cft',
		listExpandTemplate: 'node/list-expand.cft',
        listFormTemplate: '/api/node/edit.json/',
		offset: 50,
		rowCount: 25,
		upAmount: '-=50',
		downAmount: '+=50',
		pageupAmount: '-=150',
		pagedownAmount: '+=150',
		scrollThreshold: 250
	};



	/*
	 * INITIALIZATION
	 */
	var init = function (elem, options) {
		if(!$('body').hasClass('list')) return;

        populateDelayTimer = setPopulateDelayTimer();

        aspect = elem || $('body').attr('id');

		opt = jQuery.extend(opt, options || {});


		listdata = $('#app-content');
		dataTable = $('table', listdata);
		dataBody = $('tbody', dataTable);
		dataTable.css('visibility', 'hidden');

		createHeadings();


		var filterCookie2 = $.cookie('list.filtering');
		if(filterCookie2) filterCookie = JSON.parse(filterCookie2);

		var sortCookie2 = $.cookie('list.sorting');
		if(sortCookie2) sortCookie = JSON.parse(sortCookie2);

		readFiltersAndSorts();

		//scrolling
		listdata.scroll(scrollList);

		if(!loadingResults) {
			dataTable.css('visibility', 'visible');

			readTotal();
			redrawHeadings(false);

			checkScrollHeight();

			parseRows(1, Paging.showingCount);
		}

		$(window).bind('resize', function() {
			clearTimeout(resizeDelay);
			resizeDelay = setTimeout(resize, 100);
		});

		//keypress
		$(document).bind('keydown', function(e) { keydown(e); });
        $(document).bind('keyup', function(e) { keyup(e); });
		$(':input').not('[type=image]').inputFocus();


        //cache widget id's to prevent repeated jquery DOM searches
        var widgetAutoExpandCache = [];

        var handleWidgetActivation = function(event,widgetUUID,widget){
            //widget is undefined for the WIDGET_DEACTIVATED event
            if(!!widget) {
                if(!(widgetUUID in widgetAutoExpandCache)) {
                    //this jquery search expression will be 0 if the widget isn't in the sidebar (not a filter widget)
                    widgetAutoExpandCache[widgetUUID] = widget.DOM.container.parents('#app-sidebar').length == 0;
                }

                if(widgetAutoExpandCache[widgetUUID]) {
                    widget.Options.AutoExpandParentHeight = true;
                }
            }
            //since the WIDGET_ACTIVATED event is fired before the widget search container is shown
            //need to adjust the list expand height in a separate thread (afterwards)
            setTimeout(function(){
                adjustExpandedHeight();
            },1);
		};

        $(document).bind(AbstractTagWidget.EVENTS.WIDGET_ACTIVATED,handleWidgetActivation);
        $(document).bind(AbstractTagWidget.EVENTS.WIDGET_DEACTIVATED,handleWidgetActivation); //this is also fired when chosen items are removed


		$('.filter').submit(function() { return false; });

		Date.format = 'yyyy-mm-dd';

		$('.filter li.filter-date input').datepicker({
            dateFormat: 'yy-mm-dd',
            nextText: '&raquo;',
            prevText: '&laquo;'
        });

//		if(element == 'pages' || element == 'news')
//			new ActiveEdits(element,null,null,{update:document.activeeditsInterval});

		return this;
	};


	/*
	 * OBJECTS
	 */

	var Paging = {
		offset: opt.offset,
		page: 1,
		totalCount: 0,
		showingCount: 0,
		toArray: function() {
			return { 'Page': this.page, 'Offset': this.offset, 'MaxRows': opt.rowCount };
		},
		reset: function() {
			this.page = 1;
			this.offset = 0;
			this.totalCount = 0;
			this.showingCount = 0;
		}
	};
	var Sorting = {

		sorts: [],

		clearSorts: function() {
			this.sorts = [];
		},
		getCurrentDirection: function(sortBy) {
			var curSort = null;
			$.each(this.sorts, function(i, s) {
				if(s.sortBy == sortBy) {
					curSort = s.direction;
					return false;
				}
			});
			return curSort;
		},
		addSort: function(sortLinkId, sortBy, direction) {
			this.sorts[this.sorts.length] = { link: sortLinkId, sortBy: sortBy, direction: direction };
		},
		toArray: function() {
			var str = [];
			$.each(this.sorts, function(i, s) {
				str['sort['+s.sortBy+']'] = s.direction;
			});
			return str;
		},

		setLinks: function() {
			$('th', headingsTable).removeClass('asc').removeClass('desc');
			$('th a', headingsTable).removeClass('asc').removeClass('desc');
			$.each(this.sorts, function(i, s) {
				$('#'+s.link).attr('title', 'Sort '+(s.direction=='asc'?'descending':'ascending')).addClass(s.direction).parent().addClass(s.direction);
			});
		}
	};
	var Filtering = {

		filters: [],
		clearFilters: function() {
			this.filters = [];
			$('.filter label').removeClass('filtered');
			$('.filter :input').not(':submit').val('');
            $('.filter :checkbox').attr('checked',null);
		},
		addFilter: function(name, value) {
            var fieldId = $(':input[name=\'filter['+name+']\']').attr('id');
			//if(fieldId == 'sitefilter') this.removeFilter('sitefilter');
			$('label[for='+fieldId+']').addClass('filtered');
			this.filters[this.filters.length] = {name: name, value: value};
		},
		removeFilter: function(fieldId) {
			var t2 = this;
			$.each(this.filters, function(i, f) {
                var ffieldId = $(':input[name=\'filter['+f.name+']\']').attr('id');
				if(ffieldId == fieldId) {
					$('label[for='+fieldId+']').removeClass('filtered');
					t2.filters.splice(i, 1);
					return false;
				}
			});
		},
		toArray: function() {
			var str = [];
			$.each(this.filters, function(i, f) {
				str['filter['+f.name+']'] = f.value;
			});
			return str;
		},

		setFieldValues: function() {
			$.each(this.filters, function(i, f) {
                var ffieldId = $(':input[name=\'filter['+f.name+']\']').attr('id');

				$('#'+ffieldId).val(f.value);
			});
		},

		getCookieFilters: function() {
			var newFilters = [];
			$.each(this.filters, function(i, f) {
//				if($('#'+f.field).attr('type') != 'hidden'){
                    var ffieldId = $(':input[name=\'filter['+f.name+']\']').attr('id');

					$('label[for='+ffieldId+']').addClass('filtered');
					newFilters[newFilters.length] = f;
//				}
			});
			return newFilters;
		}

	};


	/*
	 * cookie loading of sorting & filtering
	 */


	var readFiltersAndSorts = function() {


		var locationPath = window.location.pathname;
		var useSiteCookieOnly = false;
		var triggerReload = false;

        var qFilters = {};

		if(filterCookie) {
			if(filterCookie[locationPath] && filterCookie[locationPath].length > 0) {
				if(Filtering.filters != filterCookie[locationPath]){
					Filtering.filters = filterCookie[locationPath];
					$.each(Filtering.filters, function(i, f) {
                        var ffieldId = $(':input[name=\'filter['+f.name+']\']');
                        qFilters[f.name] = { "field": ffieldId, "name": f.name, "value":f.value };
					});
				}
			};
		}

		if(sortCookie) {
			if(sortCookie[locationPath] && sortCookie[locationPath].length > 0) {
				if(Sorting.sorts != sortCookie[locationPath]){
					Sorting.sorts = sortCookie[locationPath];
					Sorting.setLinks();
					triggerReload = true;
				}
			};
		}

        var foundOne = false;
		$('.filter :input').not(':submit').each(function(i, field) {
            var fieldName;
            var inputName;
			var nameAttr = $(field).attr('name');
			var qVal = $.query.get(nameAttr);

			if(nameAttr.indexOf('[') > -1) {
				fieldName = nameAttr.substr(nameAttr.indexOf('[')+1, (nameAttr.lastIndexOf(']')-nameAttr.indexOf('['))-1);
			}

			if(qVal != '') {
                if(!foundOne)
                {
                    qFilters = {};
                    foundOne = true;
                }
				qFilters[fieldName] = { "field": field, "name": fieldName, "value": (qVal===true?'':qVal) };
			}
		});

        var first = true;
        $.each(qFilters, function(i, f) {
            if(first)
            {
                Filtering.clearFilters();
                triggerReload = true;
                first = false;
            }
            $(f.field).val(f.value);//.trigger('change'); //TODO: disabled for jquery upgrade, ensure this works
            if ($(f.field).is(':checkbox')) { $(f.field).attr('checked', 1); }
            Filtering.addFilter(f.name, f.value);
        });

		if(triggerReload) reloadData();

	};

	var setCookies = function() {
		var locationPath = window.location.pathname;
		filterCookie[locationPath] = Filtering.getCookieFilters();
		$.cookie('list.filtering', JSON.stringify(filterCookie));
		sortCookie[locationPath] = Sorting.sorts;
		$.cookie('list.sorting', JSON.stringify(sortCookie));
	};


	/*
	 * PAGELESS PAGING
	 */
	var scrollList = function() {
        var myScrollTop = this.scrollTop;
        if (myScrollTop != scrollTop) {
            scrollTop = myScrollTop;
            if (((this.scrollHeight-this.offsetHeight)-scrollTop) <= opt.scrollThreshold) {
                if(!loadingResults && !showingAll) {
                    populate(null,false);
                }
            }
        }
	};

    /**
     * Check if a scrollbar is visible, if not populate more data
     * @param playNice if true, prevents the AJAX populate if one is already in-progress
     */
	var checkScrollHeight = function(playNice) {
		if(!showingAll) {
			var l = listdata[0];
			if (((l.scrollHeight-l.offsetHeight)-l.scrollTop) <= opt.scrollThreshold) {
				populate(null,playNice);
			}
		}
	};

    /**
     * Loads list data based on paging, filters, and sorting
     * @param callback This function is called after the list data is loaded
     * @param playNice if true, prevents an in-progress AJAX request from being aborted
     */
	var populate = function(callback,playNice) {
		if(currentRequest != null && ((typeof playNice == 'boolean' && !playNice) || typeof playNice == 'undefined')) {
			currentRequest.abort();
			loadingResults = false;
            showingAll = false;
		}

		if(!loadingResults && !showingAll) {
			loadingResults = true;
			$(document).trigger('show_loading');

            clearPopulateDelayTimer();

			currentRequest = jQuery.ajax({
				type: 'GET',
				url: opt.templateStubUrl,
				data: jQuery.extend( {
							'Template': opt.listDataTemplate,
							'Aspect': aspect
						},
						Paging.toArray(),
						Sorting.toArray() || {},
						Filtering.toArray() || {} ),
				error: function(req) {
					console_log(req.responseText.stripHtml());
					$(document).trigger('show_error', 'Unable to load data');
                    showingAll = true;
				},
				complete: function(res, status) {

					if (status == "success" || status == "notmodified" ) {

                        populateDelayTimer = setPopulateDelayTimer();

						$(document).trigger('hide_error');
						if(Paging.offset == 0) {
							$('tr:gt(0)', dataBody).remove();
                            currentRow = null;
	                        currentRowOpened = false;
						}
						var respLength = res.responseText.split("<tr").length-1;
						dataBody.append(res.responseText);
						parseRows(Paging.offset+1, Paging.offset + respLength);
						++Paging.page;
						Paging.offset = Paging.offset + respLength;
						readTotal();
					}

					loadingResults = false;
					$(document).trigger('hide_loading');
					dataTable.css('visibility', 'visible');
					redrawHeadings(false);
					Sorting.setLinks();

                    if(typeof callback == 'function')
                        callback.apply();
				}
			});
		}
	};

    /**
     * Dynamically resize the table heading widths based on the table contents
     * @param callback passed through to populate, called after list data is loaded
     * @param playNice if true, prevents the AJAX populate if one is already in-progress
     */
	var reloadData = function(callback,playNice) {
		Paging.reset();
		openForm = null;
		showingAll = false;
		setCookies();
		populate(callback,playNice);
	};

	/* headings */
	var createHeadings = function() {

		firstRow = $('tr:first', dataBody);

		headingsTable = $('<table class="data"><tbody></tbody></table>');
		var listHeader = $('<div id="app-content-header">');
		if(!$.browser.msie) listHeader.css({visibility: 'hidden'});

		listHeader.append($('<div id="list-headings">').append(headingsTable.append(firstRow.clone())));
		listdata.before(listHeader).css({marginTop: rowHeight});

		var rowHeight = listHeader.outerHeight();
		dataTable.css({marginTop: (rowHeight * -1)});
		listdata.css({marginTop: rowHeight});
		if(!$.browser.msie) listHeader.css({visibility: 'visible'});
	};

    /**
     * Dynamically resize the table heading widths based on the table contents
     * @param playNice if true (or undefined), prevents the AJAX populate if one is already in-progress
     */
	var redrawHeadings =  function(playNice) {
		headingsTable.width(dataTable.width());
		$.each($('tr:first th', dataBody), function(i, e) {
			var th = $(e);
			var width = th.innerWidth() - (parseInt(th.css('padding-left')) + parseInt(th.css('padding-right')));
			$('th:eq('+i+')', headingsTable).width(width); });
		checkScrollHeight(typeof playNice == 'undefined' ? true : playNice);
	};

	var resize = function() {
		redrawHeadings(false);
		adjustExpandedHeight();
	};

	/* totals */
	var readTotal = function() {
		Paging.showingCount = $('tr', dataBody).length-1;
		if(Paging.showingCount == 0) Paging.showingCount = $('tr:gt(0)', dataBody).length;
		if(Paging.totalCount == 0) Paging.totalCount = parseInt($('tr:eq(1) td.total', dataBody).text());
		if(!isFinite(Paging.totalCount)) Paging.totalCount = 0;

		//console_log('Total = '+Paging.totalCount);

		if(Paging.showingCount >= Paging.totalCount || Paging.totalCount == 0) showingAll = true;
		refreshTotal();
	};

	var refreshTotal = function() {
		if(totals == null) {
			totals = $('<em id="list-counts">(None)</em>');
			$('#app-main-header h2').append('&nbsp;').append(totals);
		}

		var text = (Paging.totalCount==0?'No':Paging.totalCount) + ' '+(Paging.totalCount > 1 || Paging.totalCount == 0?'items':'item');
		if(Paging.showingCount != Paging.totalCount) {
			text = Paging.showingCount + ' of '+text;
		}
		totals.text('('+text+')');

        $(document).trigger('list_total_count',[Paging.totalCount]);
	};

	var decrementCount =  function() {
		if(Paging.offset % opt.rowCount == 0 && Paging.page > 1)
			Paging.page--;
		Paging.offset--;
		Paging.totalCount--;
		Paging.showingCount--;
		refreshTotal();
	};



	/*
	 * KEYSTROKE COMMANDS
	 */
    var keydown = function(e) {
        var open;
		if(window.inputHasFocus) return;
		// track last key pressed
		lastKeyPressCode = e.keyCode;
		//console.log(e.keyCode);
		switch(e.keyCode) {
			case 33: // pageup
				listdata.scrollTo(opt.pageupAmount);
				e.preventDefault();
				e.stopPropagation();
                                break;
			case 32: //spacebar
				if(currentRow == null)
					getNextRow();
				if(currentRowOpened)
					currentRow.trigger('close');
				else
					currentRow.trigger('open');

				e.preventDefault();
				e.stopPropagation();
				break;
			case 34: // pagedown
				listdata.scrollTo(opt.pagedownAmount);
				e.preventDefault();
				e.stopPropagation();
                                break;
			case 38: // up
            case 75: // k
				//listdata.scrollTo(opt.upAmount);
				open = currentRowOpened;
				getPrevRow();
				highlightRow();
				scrollToRow();
                if(currentExpandRequest != null) {
                    currentExpandRequest.abort();
                    currentExpandRequest = null;
                    //currentRowOpened = true;
					//$(document).trigger('hide_loading');
                    currentRow.trigger('open');
                } else if(open)
					currentRow.trigger('open');
				e.preventDefault();
				e.stopPropagation();
				break;
			case 40: // down
            case 74: // j
				//listdata.scrollTo(opt.downAmount);
				open = currentRowOpened;
				getNextRow();
				highlightRow();
				scrollToRow();
                if(currentExpandRequest != null) {
                    currentExpandRequest.abort();
                    currentExpandRequest = null;
                    //currentRowOpened = true;
					//$(document).trigger('hide_loading');
                    currentRow.trigger('open');
                } else if(open)
					currentRow.trigger('open');
				e.preventDefault();
				e.stopPropagation();
				break;

			case 69: // e
				if(currentRow != null)
					$('a.edit-icon', currentRow).click(function() { window.location = this.href; }).click();
				e.preventDefault();
				e.stopPropagation();
				break;

			case 65: // a
				if(currentRow != null)
					$('a.accept-icon', currentRow).click();
				e.preventDefault();
				e.stopPropagation();
				break;

			case 72: // h
				if(currentRow != null)
					$('a.highpri-icon', currentRow).click();
				e.preventDefault();
				e.stopPropagation();
				break;

			case 77: // m
				if(currentRow != null)
					$('a.medpri-icon', currentRow).click();
				e.preventDefault();
				e.stopPropagation();
				break;

			case 76: // l
				if(currentRow != null)
					$('a.lowpri-icon', currentRow).click();
				e.preventDefault();
				e.stopPropagation();
				break;


			case 66: // b
			case 83: // s
				if(currentRow != null)
					$('a.backlog-icon', currentRow).click();
				e.preventDefault();
				e.stopPropagation();
				break;

			case 81: // q
				if(currentRow != null)
					$('a.queue-icon', currentRow).click();
				e.preventDefault();
				e.stopPropagation();
				break;

			case 46: // delete
			//case 8:  // backspace
			case 68: // d
				if(currentRow != null)
					$('a.reject-icon', currentRow).click();
				e.preventDefault();
				e.stopPropagation();
				break;
		}

        if(holdingKey)
            setTimeout(function() { keydown(e); }, 200);
	};

    var keyup = function()
    {
        holdingKey = false;
    };



	/*
	 * HIGHLIGHTING ROWS & LIST EXPAND
	 */
	var currentRow = null;
	var currentRowOpened = false;


	var currentExpandedRow = null;

	var openForm = null;

	var currentExpandRequest = null;


	/* navigate rows */

	var getNextRow = function() {
        var nextRow;
		if(currentRow != null) {
			if(currentRowOpened)
				currentRow.trigger('close');

			nextRow = currentRow.next();
			if(nextRow.length == 0) return;
		} else {
			nextRow = $('.collapsed:eq(0)', dataTable);
		}

		currentRow = nextRow;
	};

	var getPrevRow = function() {
        var prevRow;
		if(currentRow != null) {
			if(currentRowOpened)
				currentRow.trigger('close');

			prevRow = currentRow.prev();
			if(!prevRow.hasClass('collapsed') || prevRow.length == 0) return;
		} else {
			prevRow = $('.collapsed:eq(0)', dataTable);
		}

		currentRow = prevRow;
	};

	var scrollToRow = function() {
		if(currentRow == null || currentRow.length == 0) return;

        var topBound = listdata[0].scrollTop;
		var bottomBound = listdata[0].scrollTop + listdata[0].clientHeight;
		var topPosition = currentRow[0].offsetTop;
		if($.browser.safari)
			topPosition -= currentRow[0].offsetHeight-3;
		var bottomPosition = topPosition + currentRow.outerHeight();

		if(currentRowOpened)
			bottomPosition += currentExpandedRow.outerHeight();

		var above = (topPosition <= topBound);
		var below = (bottomPosition > bottomBound);
		if(above) {
			listdata.scrollTo(topPosition);
		} else if(below){
			listdata.scrollTo(bottomPosition - listdata[0].clientHeight);
		}

//        listdata.scrollTo(currentRow);
	};

	var highlightRow = function() {
		if(currentRow == null) return;
		$('.collapsed').removeClass('highlight');
		currentRow.addClass('highlight');
	};

	var adjustExpandedHeight = function() {
		if(!currentRowOpened) return;

		var height = $('.expanded-wrapper').height();

		// Add the margins and give room for some padding at the bottom
		height = height + 7;
		if(!$.browser.safari)
			height += 10;

		// Set the height
		$('td', currentExpandedRow).eq(0).height(height);
		//scrollToRow();
	};


	var doExpand = function() {
		if(!currentRow.hasClass('collapsed')) return;

        //if(typeof currentExpandRequest != 'undefined' && currentExpandRequest != null)
            //currentExpandRequest.abort();

		var type = currentRow.data('Type');
		var slug = currentRow.data('Slug');
        var element = currentRow.data('ElementSlug');
        var site = currentRow.data('SiteSlug');
        var aspect = currentRow.data('Aspect');
        var rowID = currentRow.data('RowID');

        var technicallyCurrentRow = currentRow;

		$(document).trigger('show_loading');

		currentExpandRequest = $.get(
			opt.templateStubUrl,
			{
                'Template': opt.listExpandTemplate,
                'Element': typeof element == 'undefined' ? '' : element,
                'Slug': slug,
                'OriginalSlug': slug,
                'Aspect': aspect,
                'Site': site,
//                'action': type+'-edit',
//                'action_datasource': type+'-single',
                'expand': true
            },
			function(data, status) {
				if (status == "success" || status == "notmodified" ) {
					if(currentRowOpened) currentExpandedRow.remove();

					technicallyCurrentRow.after(data);
                    technicallyCurrentRow.addClass('opened');

					currentExpandedRow = $('#expanded_'+rowID);

					updateColors();

					adjustExpandedHeight();
					setTimeout(adjustExpandedHeight, 100);
					$('.expanded-wrapper *', currentExpandedRow).load(adjustExpandedHeight);

					if($.browser.msie && $.browser.version < 7)
						$('.expanded-wrapper', currentExpandedRow).bgiframe();


					openForm = $('form', currentExpandedRow);

					openForm.submit(submitForm);

					$(':input', openForm).not('[type=image]').change(function() {
                        $('#saving').remove();
                        $(document).trigger('form_changed');
                    }).inputFocus();


                    //Bind cancel button; closes expanded row
                    $('a.cancel',currentExpandedRow).click(function(event){
                        event.preventDefault();
                        technicallyCurrentRow.trigger('close');
                    });


                    currentRowOpened = true;
					$(document).trigger('hide_loading');
                    $(document).trigger('list_expand',currentExpandedRow);

				}
				currentExpandRequest = null;
			}
		);


	};

	var updateColors = function() {
		var bgColor = $('td', currentRow).css('background-color');
		$('td', currentExpandedRow).css({backgroundColor: bgColor });
	};

	var parseRows = function(start, end) {
		for(var i = start; i <= end; ++i) {
			var row = dataBody[0].rows[i];
			var $row = $(row);
			if($row.hasClass('collapsed')) {
				bindRow($row);
			}
		};

	};


	var bindRow = function($row) {
		$row.bind('open', function(e) {
			if(currentExpandRequest != null) return;

			if(currentRowOpened && currentRow[0] != e.target)
			{
				currentRow.trigger('close');
				if(document.madeChanges) return;
			}

			currentRow = $(e.target);
			highlightRow();
			doExpand();

		});
		$row.bind('close', function(event,callback) {
			if(currentExpandRequest != null) { return; }
			if(document.madeChanges) {
				var res = confirm('Are you sure you want to navigate away from this record?\n\nPress OK to continue, or Cancel to stay on the current page.');
				if(!res)
                    return;
				openForm[0].reset();
				document.madeChanges = false;
                if(res && typeof callback == 'function')
                    callback();
			}
			currentExpandedRow.remove();
			currentRow.removeClass('opened');
			currentRowOpened = false;
		});
		$row.click(function(e) {
			e.stopPropagation();
			if(!clickedLink) {
				var $this = $(this);
				if(currentRowOpened && currentRow[0] == this) {
					$this.trigger('close');
				} else {
					$this.trigger('open');
				}
				return false;
			} else {
				clickedLink = false;
			}
		});
		$('a', $row).add('input', $row).click(function(e) {
			clickedLink = true;
			e.stopPropagation();
			return true;
		});
	};

	var widgetUpdated = function() {
		adjustExpandedHeight();
	};

	/* expand form submit */
	var submitForm = function(event) {
		if(currentPostRequest != null) return false;

		document.formSubmitting = true;

		$(document).trigger('show_loading');

		var form = this;
		var $form = $(this);
		var $submit = $('input[type=image]', $form).attr('disabled', 'disabled');
		$submit.before('<span id="saving" class="saving">&nbsp;Saving...</span>');

		var slug = currentRow.data('Slug');
        var elementSlug = currentRow.data('ElementSlug');
        var rowID = currentRow.data('RowID');

		currentPostRequest = $.ajax({
			type: 'POST',
			dataType: 'json',
			cache: false,
			url: opt.listFormTemplate,
			data: $form.serialize(),
			success: function(jsonObj) {

				$submit.removeAttr('disabled');
				$('#saving').removeClass('saving');

				if(jsonObj.Errors == null) {
					document.madeChanges = false;
					$('div.errors', form).remove();
					$('#saving').addClass('saved').html('&nbsp;Saved.');
					setTimeout(function() { $('#saving').fadeOut(500); }, 400);

					$.get(opt.listDataStubUrl,jQuery.extend( {
                            'Template': opt.listDataTemplate,
                            'Element': elementSlug,
                            'Aspect': aspect,
							'Slug': slug
						}, Filtering.toArray() || {} )
							, function(data) {

								$(document).trigger('hide_loading');

								currentExpandedRow = $('#expanded_'+rowID);
								currentRow = $('#collapsed_'+rowID);

                                var oldRow;
								if($.trim(data) != '') {
									if(currentExpandedRow.length > 0) {
										currentRow.remove();
										currentExpandedRow.before(data);
										currentRow = currentExpandedRow.prev();
										highlightRow(currentRow);
										doExpand(currentRow);
									} else {
										currentRow.before(data);
										currentRow.remove();
										currentRow = $('#collapsed_'+rowID);
										highlightRow(currentRow);
										currentRowOpened = false;
									}
									updateColors();
									bindRow(currentRow);
								}else {
									oldRow = currentRow;
									getNextRow();
									highlightRow();
									//scrollToRow();
									oldRow.remove();
									if(currentExpandedRow.length > 0) {
										currentExpandedRow.remove();
										currentExpandedRow = null;
									}
									decrementCount();
									redrawHeadings(false);
//									currentRowOpened = false;
//                                    currentRow = null;
								}

								currentPostRequest = null;
							});

				} else {

					$('#saving').remove();
					$('#errors-row', form).remove();

					var errorMessage = '\
					<div id="errors-row" style="margin-bottom: 5px">\
						<div class="errors">Warning! There was a problem saving the changes:<ul>';
                    $.each(jsonObj.Errors, function(i,errorField) {
                        errorMessage += '<li>'+errorField.Message+'</li>';
                    });
                    errorMessage += '</ul></div>\
					</div>';

                    $('label',form).add('input',form).removeClass('error');

					$.each(jsonObj.Errors, function(i,errorField) {
                        if(errorField.Resolved) {
                            var id = errorField.Resolved.substr(errorField.Resolved.lastIndexOf('.')+1);
	    					$('label[for='+id+']').add('#'+id).addClass('error');
                        }
					});

					$(form).prepend(errorMessage);
					adjustExpandedHeight();

					$(document).trigger('hide_loading');
					currentPostRequest = null;

                    scrollToRow();
				}
				return false;
			},
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				// typically only one of textStatus or errorThrown
				// will have info
				//	  this; // the options for this ajax request
				//alert('grr');

				$(document).trigger('hide_loading');

				currentExpandedRow = $('#expanded_'+currentRow.data('RowID'));
				currentRow = $('#collapsed_'+currentRow.data('RowID'));

				var oldRow = currentRow;

				if(XMLHttpRequest.status == '404') {
					getNextRow();
					highlightRow();
					scrollToRow();
					oldRow.remove();
					if(currentExpandedRow.length > 0) {
						currentExpandedRow.remove();
						currentExpandedRow = null;
					}
					decrementCount();
					redrawHeadings(false);
					currentRowOpened = false;
				}

				currentPostRequest = null;
				return false;
			}
		});

        event.preventDefault();
		return false;
	};

	$(document).ready( function() {
		init();
	});

	/* public functions */
	return {
        getTotal : function() {
            return Paging.totalCount;
        },

		adjustExpandedHeight: adjustExpandedHeight,

		redrawHeadings: redrawHeadings,

        reloadData: reloadData,

		sort: function(sortLink, sortBy, firstDirection) {
			var curDirection = Sorting.getCurrentDirection(sortBy) || $(sortLink).attr('class').toLowerCase();

			Sorting.clearSorts();
			if(curDirection == null || curDirection == '') {
				Sorting.addSort(sortLink.id, sortBy,  firstDirection.toLowerCase());
			} else if(curDirection.toLowerCase() != firstDirection.toLowerCase()) {
				// no sorting
			} else if(curDirection.toLowerCase() == 'desc') {
				Sorting.addSort(sortLink.id, sortBy, 'asc');
			} else {
				Sorting.addSort(sortLink.id, sortBy, 'desc');
			}
			Sorting.setLinks();

			reloadData(null,false);
		},

		filter: function(field, name, value, dontremove, dontreload, allowblank) {
			var id = field.id;

			if (!id)
				id = field.attr('id');

			//if(id == 'sitefilter')
				//changeSite($(field).val());

			if(!dontremove) {
				Filtering.removeFilter(id);
			}
			if($.trim(value) != '' || allowblank) Filtering.addFilter(name, value);

			if (!dontreload) {
				reloadData(null,false);
			}
		},

		clearFilters: function() {
			//if($('#sitefilter').length > 0) changeSite('');
			Filtering.clearFilters();
			reloadData(null,false);
		},

		submit: function(form, row) {
            currentRow = row;
			form.submit(submitForm).submit();
		},

        updateOption: function(name, value) {
            opt[name] = value;
        },

        isRowOpened : function() {
            return currentRowOpened;
        },

        getCurrentRow : function() {
            return currentRow;
        },

        getAspect : function() {
            return aspect;
        },

        getFilters : function() {
            return Filtering.filters;
        }
    };

}();


function keypressDelay(event, funcToExec) {
    if(event.keyCode == 13) {
        if (window.k_timeout) clearTimeout(window.k_timeout);
        funcToExec(event);
        preventDefault(event);
        return false;
    } else {
        if (window.k_timeout) clearTimeout(window.k_timeout);
        window.k_timeout = setTimeout(funcToExec, 400);
    }
}

function clearSearchInput(event) {

    preventDefault(event);

    var callback = function() {
        var input = $(event.target || event.srcElement).parent().children('input:eq(0)');
        input.val('');
        List.filter(input, input.attr('name'), '');
        input.focus();
    };

    if(List.isRowOpened()) {
        List.getCurrentRow().trigger('close',callback);
        return;
    }

    callback();
}

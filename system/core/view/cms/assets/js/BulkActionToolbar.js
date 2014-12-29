var BulkActionToolbar = (function() {

    /* private attributes */
    var toolbar;
    var currentSelection;
    var selectionTools;
    var actionTools;
    var selectActionLink;
    var confirmActionLink;
    var clearActionLink;
    var actionMenu;
    var pagingTotal;
    var filterMatch = false;
    var clickShield;
    var progressBar;
    var resultOutputPanel;
    var resultOutputList;
    var failureCount = 0;
    var cancelAction;
    var progressPanel;
    var iframe;
    var selectedAction = null;
    var registeredActions = {
        Total : 0
    };

    var totalRecords = 0;
    var currentRecord = 0;
    var nodeRefs = null;

    /* private methods */
    var _init = function() {

        var $body = $('body');

        if(!$body.hasClass('list') || $body.hasClass('signin')) return;

        //DISABLED DUE TO DYNAMIC LIST REFRESH LOGISTICS
        //Only render bulk action toolbar if checkboxes have been created for each row
        //if($('#app-content tr td :checkbox').length == 0) return;

        //Increase the width of the button column to accomodate the checkbox
        $('.data th.first').css('width','70px');

        if($.browser.msie)
            $('.data td:first').css('width','70px');

        var appContent = $('#app-content');

        if($.browser.msie) {
            appContent.css('height',appContent.height()-30+'px');
            //see ie.js for window resize event, which also affects this
        } else {
            appContent.css('margin-bottom','30px');
        }

        toolbar = $('<div id="bulk-action-toolbar"></div>');

        currentSelection = $('<div class="current-selection"><p><em>x</em> records selected</p></div>').css('display','none');
        toolbar.append(currentSelection);


        selectionTools = $('<div class="selection-tools"><span>Select:</span></div>');
        selectionTools.append(' ');
        selectionTools
            .append(
                $('<a href="#">All Visible</a>')
                    .click(function(event){
                        _selectAllVisible();
                        event.preventDefault();
                    })
            );
        /* DISABLED FOR NOW */
        /*
        selectionTools.append(', ');
        selectionTools
            .append(
                $('<a href="#">All Matching Filter</a>')
                    .click(function(event){
                        _selectFilterMatch();
                        event.preventDefault();
                    })
            );
        */
        selectionTools.append(', ');
        selectionTools
            .append(
                $('<a href="#">None</a>')
                    .click(function(event){
                        _selectNone();
                        event.preventDefault();
                    })
            );
        toolbar.append(selectionTools);


        actionTools = $('<div class="action-tools"></div>');
        selectActionLink = $('<a href="#" class="select-action"><span>Select Action...</span></a>')
                    .click(function(event){
                        if(actionMenu.css("display") != "none") {
                            _clearAction();
                        } else {
                            _showActionMenu();
                        }
                        event.preventDefault();
                    });
        actionTools.append(selectActionLink);
        confirmActionLink = $('<a href="#" class="confirm"><span>Confirm</span></a>')
                    .click(function(event){
                        _confirmAction();
                        event.preventDefault();
                    }).css('display','none');
        actionTools.append(' ').append(confirmActionLink);
        clearActionLink = $('<a href="#" class="cancel">Clear</a>')
                    .click(function(event){
                        _clearAction();
                        event.preventDefault();
                    }).css('display','none');
        actionTools.append(' ').append(clearActionLink);
        toolbar.append(actionTools.css('display','none'));

        //action menu
        _buildActionMenu();

        //progress bar
        progressPanel = $('<div id="bulk-progress"><div><span>Applying action <em>Task</em> to <em>0</em> records.</span></div></div>');
        progressBar = $('<div class="progress-bar"><div>&nbsp;</div></div>');
        cancelAction = $('<a href="#" title="Cancel Action" class="cancel">Cancel</a>').click(function(event){
            _cancelProgress();
            event.preventDefault();
        });

        resultOutputPanel = $('<div class="result-output"></div>');
        resultOutputList = $('<ol></ol>');
        resultOutputPanel.append(resultOutputList);

        progressPanel.children(':first').append(cancelAction).append(progressBar).append(resultOutputPanel);

        _updateRecordCount();

        //add tool bar to UI
        $('#app-main').append(toolbar);

        clickShield = $('<div id="clickshield"></div>');

        $('body')
            .append(clickShield.css('display','none'))
            .append(progressPanel.css('display','none'));


        $(document).bind('list_total_count', function(t,total){
            pagingTotal = total;
            _updateFilterTotal();
        });
    };

    var _buildActionMenu = function() {

        var numActions = registeredActions.Total;

        //action menu
        actionMenu = $('<div class="actions"></div>');

        var actionMenuList = $('<ul></ul>');


        $.each(registeredActions,function(slug,action){
            if(slug == 'Total') return true;

            actionMenuList.append(
                $('<li></li>')
                    .append(
                        $('<a href="#">'+action.Title+'</a>')
                            .click(function(event){
                                actionMenu.hide();
                                selectedAction = slug;
                                _chooseAction(action.Title);
                                event.preventDefault();
                            })
                    )
            );

            if(typeof action.Init == 'function')
                action.Init.apply();
        });

        actionMenu.append(actionMenuList);

        actionMenu.css({
            top: '-'+(21+(numActions*22))+'px',
            backgroundPosition: 'center '+(9+(numActions*22))+'px'
        });

        toolbar.append(actionMenu.css('display','none'));

//////////////////////////////////////
/*
        //assign task option menu
        var assignTaskMenu = $('<div class="action-options"></div>');
        var select = $('<select><option value="-1">Select a task...</option></select>');

        $.ajax({
            url:'/ajax/unique-tasks/',
            type:'GET',
            dataType:'json',
            cache: false,
            async:false,
            success:function(data) {
                if(typeof data === 'undefined' || data === null || data.length === 0)
                    return;

                for(i in data) {
                    select.append($('<option value="'+data[i].id+'">'+data[i].Name+'</option>'));
                }
            },
            error:function(xhr) {
            },
            complete:function() {
            }
        });

        assignTaskMenu.append(select);
        assignTaskMenu.append(
            $('<a href="#" class="ok">OK</a>').click(function(event){
                var val = select.val();
                if(val != -1) {
                    var actionOptions = $(':selected',select).text();
                    _chooseAction('Assign Task',actionOptions);
                    assignTaskMenu.hide();
                }
                event.preventDefault();
            })
        ).append(
            $('<a href="#" class="cancel">Cancel</a>').click(function(event){
                assignTaskMenu.hide();
                event.preventDefault();
            })
        );

        //action menu
        actionMenu = $('<div class="actions"></div>');

        actionMenu.append(
            $('<ul></ul>')
                .append($('<li></li>').append(
                    $('<a href="#">Assign Task</a>')
                        .click(function(event){
                            actionMenu.hide();
                            assignTaskMenu.show();
                            select.val(-1);
                            selectedAction = 'assign-task';
                            event.preventDefault();
                        })
                ))
        );
*/
    };

    var _updateFilterTotal = function() {

        if(filterMatch) {

            if(pagingTotal == null)
                pagingTotal = List.getTotal();

            currentSelection
                .show()
                .children(':first')
                .html('<em>'+pagingTotal+'</em> records selected (filter match)')
                .effect("highlight",{color:"#49b4c1"},1000);
        }
    };

    var _selectAllVisible = function() {
        var rowset = $('#app-content tr').not('tr.expanded');

        if(rowset.length <= 1) return;

        filterMatch = false;
        currentSelection
            .show()
            .children(':first')
            .effect("highlight",{color:"#49b4c1"},1000);

        $('p',currentSelection).html('<em>'+(rowset.length-1)+'</em> records selected');

        _setAllCheckboxes('checked');

        _showActionTools();
    };

    /*
    var _selectFilterMatch = function() {
        filterMatch = true;
        _setAllCheckboxes(null);

        _updateFilterTotal();

        _showActionTools();
    };
    */

    var _selectNone = function() {
        filterMatch = false;
        _setAllCheckboxes(null);

        currentSelection.fadeOut("fast");
        actionTools.fadeOut("fast");
        _clearAction();
    };

    var _showActionMenu = function() {
        actionMenu.fadeIn("fast");
    };

    var _showActionTools = function() {
        if(registeredActions.Total > 0)
            actionTools.show();
    };

    var _chooseAction = function(label) {

        var customLabel = typeof registeredActions[selectedAction].ChooseAction == 'function' ? registeredActions[selectedAction].ChooseAction.apply() : null;

        actionMenu.fadeOut("fast");
        $('span',selectActionLink).html((customLabel==null?label:customLabel)+':');
        $('div > span > em:first',progressPanel).html(label);
        selectActionLink.addClass('selected');
        confirmActionLink.show();
        clearActionLink.show();
    };

    var _confirmAction = function() {
        $('div',progressBar).width('0%');

        var rowset = $('#app-content tr td .bulk-action-id:checked');
        $('div > span > em:last',progressPanel).html(rowset.length);

        totalRecords = rowset.length;
        currentRecord = 0;
        failureCount = 0;
        nodeRefs = [];

        cancelAction.text('Cancel').attr('title','Cancel Action');

        $.each(rowset,function(i,e){
            var tr = $(e).parent().parent();
            if(typeof tr.data('ElementSlug') != "undefined" &&
               typeof tr.data('Slug') != "undefined" &&
               tr.data('ElementSlug') != null &&
               tr.data('Slug') != null)
                nodeRefs.push(tr.data('ElementSlug')+':'+tr.data('Slug'));
        });


        if($.browser.msie) {
            clickShield.show();
            progressPanel.show();
            _iframeRequest();
        } else {
            clickShield.fadeIn('fast',function(){
                progressPanel.show();
                _iframeRequest();
            });
        }
    };

    var _iframeRequest= function() {

        var numPer = registeredActions[selectedAction].NumPerRequest;

        var begin = currentRecord;
        var end = totalRecords - currentRecord > numPer ? currentRecord + numPer : totalRecords;

        var data = [];

        for(var i=begin; i<end; i++) {
            data.push(nodeRefs[i]);
        }

        var actionParams = {};
        if(typeof registeredActions[selectedAction].ConfirmAction == 'function')
            actionParams = registeredActions[selectedAction].ConfirmAction(data);

        var url = "bulk/"+selectedAction+"/?"+$.param($.extend({
            NodeRefs: data.join(',')
        },actionParams));

        if(iframe != null)
            iframe.remove();

        iframe = $('<iframe src="'+url+'" style="display:none"></iframe>');
        $('body').append(iframe);
    };

    var _updateProgressBar = function(percent) {
        var val = $('div',progressBar);
        //var w = parseInt(val.css('width'));

        val.css('width',percent+'%');
    };

    var _clearAction = function() {
        if(selectedAction && typeof registeredActions[selectedAction].Clear == 'function')
            registeredActions[selectedAction].Clear.apply();

        $('span',selectActionLink).html('Select Action...');
        selectActionLink.removeClass('selected');
        confirmActionLink.hide();
        clearActionLink.hide();
        actionMenu.hide();
        selectedAction = null;
    };

    var _setAllCheckboxes = function(value) {
        $('#app-content table .bulk-action-id:checkbox').each(function(i,e){
            $(e).attr('checked',value);
        });
    };

    var _completeProgress = function() {
        List.reloadData(function(){
            progressPanel.hide();
            currentSelection.hide();
            actionTools.hide();
            _selectNone();
            if($.browser.msie) clickShield.hide(); else clickShield.fadeOut();
            iframe.remove();
            resultOutputList.empty();
        });
    };

    var _cancelProgress = function() {
//        progressPanel.hide();
//        resultOutputList.empty();
//        clickShield.fadeOut();
//        iframe.remove();
        _completeProgress();
    };

    var _updateRecordCount = function() {

        var rowset = $('#app-content tr td .bulk-action-id:checked');
        if(rowset.length > 0) {
            $('p',currentSelection).html('<em>'+(rowset.length)+'</em> records selected');
            filterMatch = false;
            if(currentSelection.css('display') == 'none') {
            currentSelection
                .show()
                .children(':first')
                .effect("highlight",{color:"#49b4c1"},1000);
            }
            _showActionTools();
        } else {
            _selectNone();
        }
    };

    /* initialize singleton */
    //$(document).ready( function() {
        //Should be called manually after registering all actions.
        //init();
    //});


    /* public methods */
    return {
        init : function() {
            _init();
        },

        updateProgress : function(success,json) {

            var li = success ? $('<li>'+json.Slug+' successful.</li>') : $('<li class="error">'+json.Slug+' failed! ('+json.Message+')</li>');

            if(!success)
                failureCount++;

            resultOutputList.append(li);
            resultOutputPanel.scrollTo('li:last');

            currentRecord++;

            var percent = Math.ceil(currentRecord/totalRecords*100.0);
            _updateProgressBar(percent);

            if(currentRecord >= totalRecords) {
                if(failureCount == 0) {
                    _completeProgress();
                } else {
                    cancelAction.text('Close').attr('title','A failure occurred. Close Dialog.');
                }
                return;
            }

            if(currentRecord % registeredActions[selectedAction].NumPerRequest == 0) {
                _iframeRequest();
            }
        },

        toggleRecord : function(checkbox) {
            _updateRecordCount();
        },

        registerAction : function(slug,title,numPerRequestOrBulkActionObject,chooseActionCallback,confirmActionCallback,initCallback,clearCallback) {

            if(typeof slug == "undefined" || typeof title == "undefined") {
                console_log("Invalid slug or title passed when registering bulk action!");
                return;
            }

            if(typeof chooseActionCallback != 'undefined' && typeof chooseActionCallback != 'function') {
                console_log("Invalid chooseActionCallback function specified!");
                return;
            }

            if(typeof confirmActionCallback != 'undefined' && typeof confirmActionCallback != 'function') {
                console_log("Invalid confirmActionCallback function specified!");
                return;
            }

            if(typeof initCallback != 'undefined' && typeof initCallback != 'function') {
                console_log("Invalid initCallback function specified!");
                return;
            }

            if(typeof clearCallback != 'undefined' && typeof clearCallback != 'function') {
                console_log("Invalid clearCallback function specified!");
                return;
            }

            if(slug == 'Total') {
                console_log("Action slug 'Total' is reserved, please choose another slug!");
                return;
            }

            registeredActions[slug] = {
                Title : title,
                ChooseAction : typeof numPerRequestOrBulkActionObject == 'object' ? function(){numPerRequestOrBulkActionObject.choose();} : chooseActionCallback,
                ConfirmAction : typeof numPerRequestOrBulkActionObject == 'object' ? function(data){return numPerRequestOrBulkActionObject.confirm(data);} : confirmActionCallback,
                Init : typeof numPerRequestOrBulkActionObject == 'object' ? function(){numPerRequestOrBulkActionObject.init();} : initCallback,
                Clear : typeof numPerRequestOrBulkActionObject == 'object' ? function(){numPerRequestOrBulkActionObject.clear();} : clearCallback,
                NumPerRequest : typeof numPerRequestOrBulkActionObject == "undefined" ? 10 : (typeof numPerRequestOrBulkActionObject == 'object' ? 10 : numPerRequestOrBulkActionObject)
            };

            registeredActions.Total++;
        }

    };

})();

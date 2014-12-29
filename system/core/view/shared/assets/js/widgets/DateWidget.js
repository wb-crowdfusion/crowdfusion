var DateWidget = function(inputid, dateValue, timeValue, options) {

    var opt = $.extend(
    {
        timeOnly: false,
        dateOnly: false,
        clearable: false,
        nowLink: true
    }, options);

    if(typeof opt.timeOnly == 'string')
        opt.timeOnly = (opt.timeOnly == 'true');

    if(typeof opt.dateOnly == 'string')
        opt.dateOnly = (opt.dateOnly == 'true');

    if(typeof opt.nowLink == 'string')
        opt.nowLink = (opt.nowLink == 'true');

    //kill the label and the input
    var $dateField = $('#'+inputid);
    var $label = $('label[for='+inputid+']');
    var $field_holder = $('#' + inputid+ '-holder');
    var labelText = $label.text();
    var tabindex = $dateField.attr('tabindex');
    var value = $dateField.attr('value');
    var name = $dateField.attr('name');
    var clazz = !$dateField.attr('class')?'':$dateField.attr('class');

    var $newDateLabel = $('<label for="'+inputid+(!opt.timeOnly?'-date':'-time')+'" class="'+clazz+'">'+labelText+'</label>');
    var $newDateInput;
    if(!opt.timeOnly) {
        $newDateInput = $('<input id="'+inputid+'-date" type="text" value="'+dateValue+'" name="'+inputid+'-date" tabindex="'+tabindex+'" style="width: 85px" class="'+clazz+'"/>');
    } else {
        $newDateInput = $('<input type="hidden" id="'+inputid+'-date" name="'+inputid+'-date" value="'+dateValue+'"/>');
    }
    var $newTimeInput;
    if(!opt.dateOnly) {
        $newTimeInput = $('<input id="'+inputid+'-time" type="text" value="'+timeValue+'" name="'+inputid+'-time" tabindex="'+tabindex+'" style="width: 65px" class="'+clazz+'"/>');
    } else {
        $newTimeInput = $('<input type="hidden" id="'+inputid+'-time" name="'+inputid+'-date" value="'+timeValue+'"/>');
    }

    var $newHidden = $('<input type="hidden" id="'+inputid+'" name="'+name+'" value="'+value+'"/>');

    var updateVal = function() {
        if((!opt.dateOnly && $.trim($newTimeInput.val()) == '') || (!opt.timeOnly && $.trim($newDateInput.val()) == '') || Date.fromString($newDateInput.val()).asString() != $newDateInput.val()) {
            //$newDateInput.addClass('error');
            $newHidden.val('');
        } else {
            //$newDateInput.removeClass('error');
            $newHidden.val(  $newDateInput.val()  +  ' ' + $newTimeInput.val() );
        }
        $newHidden.trigger('upd');
        //console_log('newHidden upd');
    };

    $newDateInput.add($newTimeInput).bind('upd', updateVal).change(updateVal).focus(function() { window.inputHasFocus = true; }).blur(function() { window.inputHasFocus = false; });

    var keyupTime = function () {
        var val = $(this).val();
        if(/^(([1-9]|10|11|12):([0-5])([0-9])(\s| |\u00A0)+(AM|PM))$/.test(val)){
            $newTimeInput.removeClass('error');
        } else if (val.length > 0) {
            $newTimeInput.addClass('error');
        }
        $newHidden.trigger('upd');
    };

    $newTimeInput.bind('keyupTime', keyupTime).keyup(keyupTime).focus(function() { window.inputHasFocus = true; }).blur(function() { window.inputHasFocus = false; });

    $label.after($newDateLabel);

    $field_holder.append($newDateInput);
    $newDateInput.after($newTimeInput);
    $newTimeInput.after($newHidden);

    if(opt.clearable)
    {
        var $clearLink = $('<a class="dp-clear" href="#" title="Clear the date">clear</a>').click(function() {
            if($newDateInput.val() != '' || $newTimeInput.val() != '') {
                $(document).trigger('form_changed');
            }

            $newDateInput.val('').trigger('upd');
            $newTimeInput.val('').trigger('upd');
            return false;
        });

        $newTimeInput.after($clearLink);
    }

    if(opt.nowLink && opt.nowLink != "false")
    {
        var $nowLink = $('<a class="dp-now" href="#" title="Set date &amp; time to now">now</a>').click(function() {

            var link = $(this);

            link.addClass('loading');

            $.ajax({
                dataType: 'json',
                type: 'GET',
                url: '/api/system/localdate.json',
                async: false,
                success: function(json) {

                    var nowDt = json.Date;
                    var nowTm = json.Time;

                    if($newDateInput.val() != nowDt || $newTimeInput.val() != nowTm) {
                        $(document).trigger('form_changed');
                    }

                    $newDateInput.val(nowDt).trigger('upd');
                    $newTimeInput.val(nowTm).trigger('upd');
                    link.removeClass('loading');

                }
            });

            return false;
        });

    }

    $newTimeInput.after($nowLink);



    $label.remove();
    $dateField.remove();

    Date.format = 'yyyy-mm-dd';

    if(!opt.timeOnly) {
        $(document).ready(function(){
            $newDateInput.datepicker({
                dateFormat: 'yy-mm-dd',
                nextText: '&raquo;',
                prevText: '&laquo;'
            });
        });
    }

    $newDateInput.trigger('upd');
    $newTimeInput.trigger('upd').trigger('keyupTime');

};

DateWidget.prototype = {

};

var PartialUtils = {

    increasePartials : function(partials, increase) {
        if(partials == null)
            return increase;

        var arr = partials.split(',');

        if(!arr.some(function(e){ return $.trim(e) == increase; }))
            return partials+','+increase;

        return partials;
    },

    decreasePartials : function(partials, decrease) {
        if(partials == null)
            return partials;

        var arr = partials.split(',');

        var index = null;

        if(arr.some(function(e,i){ return ($.trim(e) == decrease && (index = i)); })) {
            delete arr[index];
            return arr.join(',');
        }

        return partials;
    }

};


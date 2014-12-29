var NodeQuery = function(parameters, orderBy, limit, offset) {

    if(typeof limit == 'number')
        this.limit = limit;
    else
        this.limit = false;
    
    if(typeof offset == 'number')
        this.offset = offset;
    else
        this.offset = false;
    
    if(typeof orderBy != 'undefined')
        this.orderBy = orderBy;
    else
        this.orderBy = {};

    if(typeof parameters != 'undefined')
        this.parameters = parameters;
    else
        this.parameters = {};

    this.results = null;
    this.totalRecords = null;

    this.retrieveAsObjects = false;
    this.retrieveTotalRecords = false;
};

NodeQuery.prototype = {

    mergeParameters : function(params)
    {
        var me = this;

        $.each(params,function(k,v){
            me.parameters[k] = v;
        });

        return this;
    },

    setParameter : function(name, value)
    {
        this.parameters[name] = value;
        return this;
    },

    setParameters : function(params)
    {
        this.parameters = params;
        return this;
    },

    getParameter : function(name)
    {
        return typeof this.parameters[name] == 'undefined' ? null : this.parameters[name];
    },

    removeParameter : function(name)
    {
        delete this.parameters[name];
        return this;
    },

    getParameters : function()
    {
        return this.parameters;
    },

    hasParameter : function(name)
    {
        return typeof this.parameters[name] != 'undefined';
    },

    setOrderBy : function(field, direction)
    {
        if(typeof field == 'undefined' || field == null)
            return this;

        this.orderBy[field] = typeof direction == 'undefined' ? 'ASC' : direction;
        
        return this;
    },

    getOrderBy : function(field)
    {
        return typeof this.orderBy[field] == 'undefined' ? null : this.orderBy[field];
    },

    setOrderBys : function(orderBys)
    {
        this.orderBy = orderBys;
    },

    getOrderBys : function()
    {
        return this.orderBy;
    },

    setLimit : function(limit)
    {
        this.limit = limit;
        return this;
    },

    getLimit : function()
    {
        return this.limit !== false ? this.limit : null;
    },

    setOffset : function(offset)
    {
        this.offset = offset;
        return this;
    },

    getOffset : function()
    {
        return this.offset !== false ? this.offset : null;
    },

    setResults : function(arr)
    {
        this.results = arr;
        return this;
    },

    getResults : function()
    {
        return this.results;
    },

    getResult : function(index)
    {
        return typeof this.results[index] == 'undefined' ? null : this.results[index];
    },

    setTotalRecords : function(total)
    {
        this.totalRecords = total;
        return this;
    },

    getTotalRecords : function()
    {
        return this.totalRecords;
    },

    hasResults : function()
    {
        return this.results.length > 0;
    },

    getResultsAsObjects : function()
    {
        return this.results;
    },

    getResultsAsArray : function()
    {
        return this.results;
    },

    getColumnOfResults : function(col)
    {
        var data = new Array();

        $.each(this.results,function(k,v){
            if(typeof v[col] != 'undefined')
                data.push(v[col]);
            else
                data.push(null);
        });

        return data;
    },

    asObjects : function()
    {
        this.retrieveAsObjects = true;
        return this;
    },

    asArray : function()
    {
        this.retrieveAsObjects = false;
        return this;
    },

    isRetrieveAsObjects : function(val)
    {
        if(typeof val != 'undefined' && val != null) {
            this.retrieveAsObjects = val;
            return this;
        }

        return this.retrieveAsObjects;
    },

    isRetrieveTotalRecords : function(val)
    {
        if(typeof val != 'undefined' && val != null) {
            this.retrieveTotalRecords = val;
            return this;
        }

        return this.retrieveTotalRecords;
    }

};
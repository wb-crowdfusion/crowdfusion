var Eventable = function() {};

Eventable.prototype = {

    __construct : function() {
        this._eventHandlers = {};
    },

	bind : function() {
		var event = 'bind'+arguments[0];
		if(event in this)
			this[event].apply(this,arguments);
		else
			throw new Error("Event not supported: "+event);
	},

	trigger : function() {
		var event = 'trigger'+arguments[0];
		if(event in this)
			this[event].apply(this,arguments);
		else
			throw new Error("Event not supported: "+event);
	},

	/*
		DEFAULT INITIALIZED EVENT HANDLERS ARE PROVIDED
	 */
	bindInitialized : function(event,callback) {
		if(typeof callback != 'function' || callback == null)
			throw new Error("Invalid callback argument");

		$(this).bind(event,callback);
	},

	triggerInitialized : function(event) {
		$(this).trigger(event);
	},

    bindPostInitialize : function(event,callback) {
        if(typeof callback != 'function' || callback == null)
            throw new Error("Invalid callback argument");

        $(this).bind(event,callback);
    },

    triggerPostInitialize : function(event) {
        $(this).trigger(event);
    }
};

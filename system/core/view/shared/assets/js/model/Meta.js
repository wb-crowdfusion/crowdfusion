var Meta = function(obj) {

	this.MetaID = null;
	this.MetaName = null;
    this.MetaValue = null;
	this.MetaSectionID = 0;
	this.MetaString = null;

	if(typeof obj != 'undefined' && obj) {
		for(var attr in obj) {
			if(attr in this)
				this[attr] = obj[attr];
		}
	}
};

Meta.prototype = {

	toString : function() {
		return "meta#"+this.MetaName+"="+this.MetaValue;
	}
};

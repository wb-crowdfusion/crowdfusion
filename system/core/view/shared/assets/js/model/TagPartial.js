var TagPartial = function(tagStringOrElement,slug,role,value) {

	if(arguments.length == 0) throw new Error("TagPartial constructor must at least 1 argument");

	//if only 1 argument is passed, assume it's a tagstring & parse it
	//otherwise set each field individually

	if(arguments.length > 1 && (
			(typeof slug != 'undefined' && slug != null) ||
			(typeof role != 'undefined' && role != null) ||
			(typeof value != 'undefined' && value != null)
		)) {

		this.TagElement = tagStringOrElement;
		this.TagSlug = slug;
		this.TagRole = role;
		this.TagValue = value;

	} else {
		var pattern = new RegExp(/^(((@)?([a-z0-9-]+))?(:([a-z0-9\/\-]+)?)?)?(#([a-z0-9-]+)?)?(=(.+?))?$/g);

		var m = pattern.exec(tagStringOrElement);

		if(m != null) {
			if(typeof m[4] != 'undefined')
				if(typeof m[3] != 'undefined' && m[3] == '@')
					this.TagElement = '@'+m[4];
				else
					this.TagElement = m[4];

			if(typeof m[6] != 'undefined')
				this.TagSlug = m[6];

			if(typeof m[8] != 'undefined')
				this.TagRole = m[8];

			if(typeof m[10] != 'undefined')
				this.TagValue = m[10];
				
		} else {
			throw new Error("Invalid tag partial string: "+tagStringOrElement);
		}
	}
};

TagPartial.prototype = {

	isMatch : function(tag,exact) {
		if(typeof exact == 'undefined') exact = false;

		var fieldsToCompare = ['TagElement', 'TagSlug', 'TagRole', 'TagValue'];

		for(var i in fieldsToCompare) {
			var field = fieldsToCompare[i];

			if(!exact)
				if(typeof this[field] == 'undefined' || this[field] == null || this[field].length == 0)
					continue;

			var A = this[field];
			var B = tag[field];

			var isAMulti = A && A.length > 0 && A.charAt(0) == '@';
			var isBMulti = B && B.length > 0 && B.charAt(0) == '@';

			if(field == 'TagElement' && (isAMulti || isBMulti)) { //TagAspect
				var elements;
				if(isAMulti && !isBMulti) {
					elements = SystemService.getElementSlugsByAspect(A.substring(1));
					if(!elements[B])
						return false;
				} else if(!isAMulti && isBMulti) {
					elements = SystemService.getElementSlugsByAspect(B.substring(1));
					if(!elements[A])
						return false;
				}
			} else if (A !== B)
				return false;
		}
		return true;
	},

	isMatchExact : function(tag) {
		return this.isMatch(tag,true);
	},

	toString : function() {
		var tagString = "";

		if(this.TagElement != null && this.TagElement.length > 0) {
			tagString = this.TagElement;

			if(this.TagSlug != null && this.TagSlug.length > 0)
				tagString += ":"+this.TagSlug;

			if(this.TagRole != null && this.TagRole.length > 0) {
				tagString += "#"+this.TagRole;

				if(this.TagValue != null && this.TagValue.length > 0)
					tagString += "="+this.TagValue;
			}
		}

		return tagString;
	}

};

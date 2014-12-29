var Tag = function(obj) {

    this.TagDirection = null;

    this.TagID = null;
    this.TagElementID = null;
    //this.TagSiteID = null;
    this.TagElement = null;
    this.TagSite = null;
    this.TagSlug = null;
    this.TagRole = null;
    this.TagRoleDisplay = null;
    this.TagValue = null;
    this.TagValueDisplay = null;

    this.TagSectionID = 0;
    this.TagSortOrder = null;

    this.TagLinkTitle = null;
    this.TagLinkStatus = null;
    this.TagLinkActiveDate = null;
	this.TagLinkSortOrder = null;
	this.TagLinkURL = null;
    this.TagLinkNode = null;

	this.TagString = null;
	this.MatchPartial = null;

	if(typeof obj != 'undefined' && obj) {
		for(var attr in obj) {
			if(attr in this)
				this[attr] = obj[attr];
		}
	}

	if(this.TagLinkTitle == null)
		this.TagLinkTitle = this.TagSlug;

};

Tag.prototype = {

    fromString: function(str) {

        this.TagValue = '';
        this.TagValueDisplay = '';

        if((pos = str.indexOf('"')) > -1) {
            parseTag = str.substr(0, pos);
            if(str.substr(str.length-1, 1) != '"')
                return '';
            this.TagValueDisplay = str.substr(pos+1, (str.length-2)-pos);
        } else {
            parseTag = str;
        }

		var pattern = new RegExp(/^(((@)?([a-z0-9-]+))?(:([a-z0-9\/\-]+)?)?)?(#([a-z0-9-]+)?)?(=(.+?))?$/g);

		var m = pattern.exec(parseTag);

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
			throw new Error("Invalid tag partial string: "+str);
		}

    },

	toString : function() {
		var tagString = "";

		tagString = this.TagElement;

		tagString += ":"+this.TagSlug;

		tagString += "#"+this.TagRole;

		if(this.TagValue != null && this.TagValue.length > 0) {
			tagString += "="+this.TagValue;

			if(this.TagValueDisplay != null && this.TagValueDisplay.length > 0)
				tagString += '"'+this.TagValueDisplay+'"';
		}

		return tagString;

	},

	toPartial : function() {
		return new TagPartial(
			this.TagElement,
			this.TagSlug,
			this.TagRole,
			this.TagValue == '' ? null : this.TagValue
			);
	}
};

Tag.toPartial = function(tag) {
    return new TagPartial(
        tag.TagElement,
        tag.TagSlug,
        tag.TagRole,
        tag.TagValue == '' ? null : tag.TagValue
        );
};

Tag.DIRECTION = {
	OUT : 'out',
	IN : 'in'
};
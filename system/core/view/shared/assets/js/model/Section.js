var Section = function(obj) {

	this.ID = ++Section.MasterCount;
	this.SectionID = null;
	this.SectionType = null;
	this.SectionTitle = null;
	this.SectionSortOrder = null;

	if(typeof obj != 'undefined' && obj) {
		for(var attr in obj) {
			if(	attr == 'SectionID' ||
				attr == 'SectionType' ||
				attr == 'SectionTitle' ||
				attr == 'SectionSortOrder')
				this[attr] = obj[attr];
		}

		this.addTags(
			'Metas' in obj ? obj.Metas : null,
			'OutTags' in obj ? obj.OutTags : null,
			null /*no in tags for sections*/
		);
	}
};

Section.prototype = {

	clone : function() {
		var section = new Section(this);

		var outTags = this.getOutTags();
		var metas = this.getMetas();

		section.addTags(metas,outTags,null);

		section.SectionID = 0;

		return section;
	},

	equals : function(other) {
		if(other instanceof Section)
			return this.ID == other.ID;
		else
			return false;
	}
};

decorate(Section,Taggable);
decorate(Section,Eventable);

Section.sort = function(sections) {
	sections.sort(function(a,b) {
		return parseInt(a.SectionSortOrder) - parseInt(b.SectionSortOrder);
	});
};

Section.MasterCount = 0;

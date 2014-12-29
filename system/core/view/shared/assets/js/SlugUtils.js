var SlugUtils = {

	createSlug: function(txtOriginal, allowSlashes) {
		if(allowSlashes == null)
			allowSlashes = false;
	
		var theSlug = '';
		var decodedSlug = '';
		theSlug = txtOriginal.substring(0,255);
		theSlug = theSlug.toLowerCase();
		for (var n = 0; n < theSlug.length; n++) {
			var c = theSlug.charCodeAt(n);
			if (c <= 127) {
				decodedSlug += String.fromCharCode(c);
			}
			if ((c >=224 && c <=229) || (c>=192 && c<=198) || (c>=281 && c<=286)) {
				decodedSlug += 'a';
			} else if ((c >=232 && c<=235) || (c>=200 && c<=203)) {
				decodedSlug += 'e';
			} else if ((c>=236 && c<=239) || (c>=204 && c<=207)) {
				decodedSlug += 'i';
			} else if ((c>=242 && c<=248) || (c>=210 && c<=216)) {
				decodedSlug += 'o';
			} else if ((c>=249 && c<=252) || (c>=217 && c<=220)) {
				decodedSlug += 'u';
			} else if (c==253 || c==255 || c==221 || c==376) {
				decodedSlug += 'y';
			} else if (c==230 || c==198) {
				decodedSlug += 'ae';
			} else if (c==338 || c==339) {
				decodedSlug += 'oe';
			} else if (c==199 || c==231) {
				decodedSlug += 'c';
			} else if (c==209 || c==241) {
				decodedSlug += 'n';
			} else if (c==352 || c==353) {
				decodedSlug += 's';
			} else if (c==208 || c==240) {
				decodedSlug += 'eth';
			} else if (c==223) {
				decodedSlug += 'sz';
            } else if ((c>=8219 && c<=8223) || c==8242 || c==8243 || c==8216 || c==8217 || c==168 || c==180 || c==729 || c==733) {
                //all the strange curly single and double quotes
			} else if (c==188) {
				decodedSlug += '-one-quarter-';
			} else if (c==189) {
				decodedSlug += '-one-half-';
			} else if (c==190) {
				decodedSlug += '-three-quarters-';
			} else if (c==178) {
				decodedSlug += '-squared-';
			} else if (c==179) {
				decodedSlug += '-cubed-';
			} else if  (c>127) {
				decodedSlug += '-';
			}
		}
		theSlug = decodedSlug;
		theSlug = theSlug.replace(/[\'\"]/gi, '');
		theSlug = theSlug.replace(/&/gi, '-and-');
		theSlug = theSlug.replace(/%/gi, '-percent-');
		theSlug = theSlug.replace(/@/gi, '-at-');
		if(!allowSlashes)
			theSlug = theSlug.replace(/[^a-zA-Z0-9\-]/gi,'-');
		else
			theSlug = theSlug.replace(/[^a-zA-Z0-9\-\/]/gi,'-');
			
		theSlug = theSlug.replace(/\\/gi, '-');
		theSlug = theSlug.replace(/\-+/gi, '-');
		
		if(theSlug.match(/.+\-$/))
			theSlug = theSlug.replace(/\-$/gi, '');
		if(theSlug.match(/^\-.+/))
			theSlug = theSlug.replace(/^\-/gi, '');
		
		if(allowSlashes)
			theSlug = theSlug.replace(/\-\/\-/gi, '/');
		
		theSlug = theSlug.trim('/');
		
		return theSlug;
	},
	
    addDateToSlug: function(slug, date)
    {
        return date.formatDate('Y/m/d/')+this.removeDateFromSlug(slug);
    },

    removeDateFromSlug: function(slug)
    {
        slug = slug.trim('/');
        while(slug.match(/^\d{4}\/\d{2}\/\d{2}\/?(\S+)?/))
            slug = slug.replace(/^\d{4}\/\d{2}\/\d{2}\/?/, '');
        return slug;
    },

    slugContainsDate: function (slug)
    {
        return slug.match(/^\d{4}\/\d{2}\/\d{2}\/?(\S+)?/);
    }

};
var SystemService = (function() {

    var elements = {};
    var aspects = {};
	var sites = {};

    /* public methods */
    return {

        init : function(_sites,_elements,_aspects) {
            sites = _sites;
            elements = _elements;
            aspects = _aspects;
        },

        getElementAllowSlugSlashes : function(slug) {
            return typeof elements[slug] == 'undefined' ? false : elements[slug].AllowSlugSlashes;
        },

        getElementBySlug : function(slug) {
            return typeof elements[slug] == 'undefined' ? null : elements[slug];
        },

        getAspectBySlug : function(slug) {
            if(slug.charAt(0) == '@')
                slug = slug.substring(1);

            return typeof aspects[slug] == 'undefined' ? null : aspects[slug];
        },

        getElementByID : function(id) {
            var match = null;
            $.each(elements,function(k,e){
                if(e.ElementID == id) {
                    match = e;
                    return false;
                }
            });
            return match;
        },

        aspectsShareElements : function(aspect1, aspect2) {
            var elements1 = SystemService.getElementsByAspect(aspect1);
            var elements2 = SystemService.getElementsByAspect(aspect2);

            for(var i in elements1) {
                for(var j in elements2) {
                    if(elements1[i].Slug == elements2[j].Slug)
                        return true;
                }
            }

            return false;
        },

        getElementSlugsByAspect : function(aspect) {
            var match = {};

            $.each(elements,function(k,e){
                if(typeof e.AspectSlugs != 'undefined')
                    $.each(e.AspectSlugs,function(i,a){
                        if(a == aspect) {
                            match[k] = e;
                            return false;
                        }
                    });
            });

            return match;
        },

        getElementsByAspect : function(aspect) {
            var match = [];

            $.each(elements,function(k,e){
                if(typeof e.AspectSlugs != 'undefined')
                    $.each(e.AspectSlugs,function(i,a){
                        if(a == aspect) {
                            match.push(e);
                            return false;
                        }
                    });
            });

            return match;
        },

        getSiteBySlug : function(slug) {
            return typeof sites[slug] == 'undefined' ? null : sites[slug];
        },

        getSites : function() {
            var s = [];

            for(var slug in sites) {
                s.push(sites[slug]);
            }

            return s;
        }

    };

})();
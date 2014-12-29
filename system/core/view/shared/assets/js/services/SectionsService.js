var SectionsService = (function() {

    /* private attributes */

	/* private methods */
    var init = function() {

    };

    /* initialize singleton */
    $(document).ready( function() {
        init();
    });


    /* public methods */
    return {

		getSectionContents : function(section,siteid,element,template,min,max,tags,callback) {

			if(section && typeof callback == 'function') {
				var data = $.extend({
						Element : element,
						SiteID : siteid,
						PopulateSection : true,
						Template : template,
						Min : min,
						Max : max,
						TempSectionID : section.ID,
						SectionID : section.SectionID,
						SectionTitle : section.SectionTitle == null ? '' : section.SectionTitle,
						SectionType : section.SectionType
					},tags);
				$.ajax({
					type: 'POST',
					dataType: 'html',
					cache: false,
					url: 'ajax/get-section/',
					data: data,

					complete: function(response) {
						callback(response.responseText);
					}
				});
			}
		}
    };

})();

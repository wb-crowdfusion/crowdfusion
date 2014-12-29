var NodeObject = function(obj) {

//    this.SiteSlug = null; //
//    this.SiteName = null; //
//    this.SiteID = null; //used?
//    this.ElementSlug = null; //
//    this.ElementName = null; //
//    this.ElementID = null; //used?

	this.Site = null;
	this.Element = null;
	
	this.Title = null; //
	this.SortOrder = null; //
	this.Slug = null; //
	this.Status = null; //
	this.ActiveDate = null; //
	this.CreationDate = null; //
	this.ModifiedDate = null; //

	this.RecordLink = null; //
    this.Cheaters = null; //
    this.NodeRef = null; //

    //Automatically set attributes on obj that are defined in this class
    if(typeof obj != 'undefined' && obj) {
        for(var attr in obj) {
            if(attr in this)
                this[attr] = obj[attr];
        }
    }

    //Call constructors on the decorator classes
    for(var i in this._constructors) {
        if(typeof this._constructors[i] == 'function' && typeof obj != 'undefined')
            this._constructors[i].call(this,obj);
    }
};

NodeObject.EVENTS = {
//    SECTIONS_UPDATED : 'SectionsUpdated',
//    SECTIONS_UPDATED_WITH_TYPE : 'SectionsUpdatedWithType',
    INITIALIZED : 'Initialized',
    POST_INIT : 'PostInitialize'
};

NodeObject.prototype = {

	init : function() {
		this.trigger(NodeObject.EVENTS.INITIALIZED); //THIS WILL FIRE ALL INITIALIZED EVENTS
		this.trigger(NodeObject.EVENTS.POST_INIT); //THIS WILL FIRE ALL POST-INITIALIZE EVENTS
	}
	
    
//    getSectionPartials: function() {
//        return this.SectionPartials;
//    },
    
    
    
    /////////////////
    // SECTIONABLE //
    /////////////////
    
    
//    bindSectionsUpdated : function(event,callback) {
//        this.bindInitialized(event,callback);
//    },
//
//    bindSectionsUpdatedWithType : function(event,callback,type) {
//        if(typeof callback != 'function' || callback == null)
//            throw new Error("Invalid callback argument");
//
//        if(typeof this._eventHandlers.SECTIONS_UPDATED_WITH_TYPE == 'undefined')
//            this._eventHandlers.SECTIONS_UPDATED_WITH_TYPE = {};
//
//        if(!(type in this._eventHandlers.SECTIONS_UPDATED_WITH_TYPE))
//            this._eventHandlers.SECTIONS_UPDATED_WITH_TYPE[type] = {
//                type : type,
//                callbacks : new Array()
//            };
//
//        this._eventHandlers.SECTIONS_UPDATED_WITH_TYPE[type].callbacks.push(callback);
//    },
//
//    triggerSectionsUpdated : function(event) {
//        this.triggerInitialized(event);
//    },
//
//    triggerSectionsUpdatedWithType : function(event,type) {
//        if(typeof type == 'undefined' || !type) return;
//
//        if(typeof this._eventHandlers.SECTIONS_UPDATED_WITH_TYPE != 'undefined')
//            for(var i in this._eventHandlers.SECTIONS_UPDATED_WITH_TYPE) {
//                if(this._eventHandlers.SECTIONS_UPDATED_WITH_TYPE[i].type == type) {
//                    for(var c in this._eventHandlers.SECTIONS_UPDATED_WITH_TYPE[i].callbacks) {
//                        this._eventHandlers.SECTIONS_UPDATED_WITH_TYPE[i].callbacks[c].apply();
//                    }
//                }
//            }
//    },
//
//    addSection : function(section, suppressEvents) {
//        this.addSections([section]);
//
//        if(typeof suppressEvents == 'undefined' || !suppressEvents) {
//            this.trigger(NodeObject.EVENTS.SECTIONS_UPDATED_WITH_TYPE,section.SectionType);
//            this.trigger(NodeObject.EVENTS.SECTIONS_UPDATED);
//        }
//    },
//
//    updateSection : function(section, suppressEvents) {
//        //TODO: what constitutes an update? nothing using this at the moment
//
//        if(typeof suppressEvents == 'undefined' || !suppressEvents) {
//            this.trigger(NodeObject.EVENTS.SECTIONS_UPDATED_WITH_TYPE,section.SectionType);
//            this.trigger(NodeObject.EVENTS.SECTIONS_UPDATED);
//        }
//    },
//
//    removeSection : function(section, suppressEvents) {
//        var index = -1;
//
//        $.each(this.sections,function(i,sec){
//            if(sec.equals(section)) {
//                index = i;
//                return false;
//            }
//        });
//
//        if(index != -1) {
//            this.sections.splice(index,1);
//
//            if(typeof suppressEvents == 'undefined' || !suppressEvents) {
//                this.trigger(NodeObject.EVENTS.SECTIONS_UPDATED_WITH_TYPE,section.SectionType);
//                this.trigger(NodeObject.EVENTS.SECTIONS_UPDATED);
//            }
//        }
//    },
//
//    removeAllSections : function(suppressEvents) {
//        this.sections = [];
//
//        if(typeof suppressEvents == 'undefined' || !suppressEvents) {
//            this.trigger(NodeObject.EVENTS.SECTIONS_UPDATED);
//        }
//    },
//
//    removeSectionsByType : function(type,suppressEvents) {
//        var newSections = [];
//
//        var cnt = 0;
//
//        $.each(this.sections,function(i,sec){
//            if(sec.SectionType != type) {
//                newSections.push(sec);
//            } else {
//                cnt++;
//            }
//        });
//
//        this.sections = newSections;
//
//        if(cnt > 0 && (typeof suppressEvents == 'undefined' || !suppressEvents)) {
//            this.trigger(NodeObject.EVENTS.SECTIONS_UPDATED_WITH_TYPE,type);
//            this.trigger(NodeObject.EVENTS.SECTIONS_UPDATED);
//        }
//    },
//
//    addSections : function(sections) {
//        if(typeof this.sections == 'undefined')
//            this.sections = [];
//        
//        var me = this;
//                    
//        $.each(sections, function(i, section) {
//            section = section instanceof Section ? section : new Section(section);
//
//            section.bind(Taggable.EVENTS.TAGS_UPDATED,function(){
//                $(document).trigger('form_changed');
//            });
//
//            me.sections.push(section);
//        });
//        
//        
//    },
//
//    getSections : function(type) {
//
//        if(typeof type == 'undefined') {
//            return this.sections;
//        } else {
//            return $.grep(this.sections,function(section){
//                return section.SectionType == type;
//            });
//        }
//    },
//
//    getSectionTypes : function() {
//        var types = {};
//
//        $.each(this.sections,function(i,section){
//            types[section.SectionType] = section.SectionType;
//        });
//
//        return types;
//    }	
	

};
decorate(NodeObject,Taggable);
decorate(NodeObject,Eventable);
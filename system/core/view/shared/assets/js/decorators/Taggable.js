var Taggable = function(){};

Taggable.EVENTS = {
	TAGS_UPDATED : 'TagsUpdated',
	TAGS_UPDATED_WITH_PARTIAL : 'TagsUpdatedWithPartial',
	INITIALIZED : 'Initialized'
};

Taggable.prototype = {

    __construct : function(obj) {
        this.outTags = new Array();
        this.inTags = new Array();
        this.metas = new Array();
        this.MetaPartials = '';
        this.OutPartials = '';
        this.InPartials = '';

        if(typeof obj != 'undefined' && obj) {
            //TODO: uncomment this after testing:
//            this.addTags(obj.metas ? obj.metas : obj.Metas,obj.OutTags,obj.InTags);
            this.addTags(obj.Metas,obj.OutTags,obj.InTags);

            this._processCheaters(obj);

            if(typeof obj.NodePartials != 'undefined')
            	this.setPartials(obj.NodePartials.MetaPartials,obj.NodePartials.OutPartials,obj.NodePartials.InPartials);
        }
    },

    //////////////
    // Taggable //
    //////////////

    _getInputsArray : function() {

        var me = this;

        var formtags = {};

        $.each(this.getTags(), function(n, tags) {
            var dir = (n=='in')?'InTags':'OutTags';
            $.each(tags, function(i, tag) {
                formtags[dir+'[#'+tag.TagRole+']['+i+'][TagElement]'] = tag.TagElement;
                formtags[dir+'[#'+tag.TagRole+']['+i+'][TagSlug]'] = tag.TagSlug;
                formtags[dir+'[#'+tag.TagRole+']['+i+'][TagRole]'] = tag.TagRole;
                formtags[dir+'[#'+tag.TagRole+']['+i+'][TagValue]'] = tag.TagValue;
                formtags[dir+'[#'+tag.TagRole+']['+i+'][TagValueDisplay]'] = tag.TagValueDisplay;
                formtags[dir+'[#'+tag.TagRole+']['+i+'][TagSortOrder]'] = tag.TagSortOrder;
                formtags[dir+'[#'+tag.TagRole+']['+i+'][TagLinkTitle]'] = tag.TagLinkTitle;
            });
        });

        return formtags;
    },

    /**
     * This function recurses through the cheater tags and wraps all TagLinkNode properties in a NodeObject instance.
     * @param obj The source node object (having a Cheaters property)
     */
    _processCheaters : function(obj) {

        var f = function(o) {
            if(typeof o == 'undefined' || o == null) return;
            $.each(o,function(i,e){
                if(e instanceof Array) {
                    f.apply(this,[e]);
                } else if(typeof e.TagDirection != 'undefined') {
                    e.TagLinkNode = new NodeObject(e.TagLinkNode);
                }
            });
        };

        f(obj.Cheaters);
    },

    getScalar : function(expr,fallbackToCheaters) {
        if(typeof expr != 'string')
            return null;

        var dotpos = expr.indexOf('.');

        if(dotpos != -1) {
            var partial = expr.substr(0,dotpos);
            var tags = this.getOutTags(new TagPartial(partial),fallbackToCheaters);
            if(tags.length > 0 && typeof tags[0].TagLinkNode == 'object' && tags[0].TagLinkNode != null) {
                return tags[0].TagLinkNode.getScalar(expr.substr(dotpos+1));
            }
        } else {
            //end of expr, assume meta
            return this.getMetaValue(expr);
        }

        return null;
    },

    /*
        TAGGABLE-SPECIFIC EVENT PROCESSORS
        SEE: Eventable.js
     */

    bindTagsUpdated : function(event,callback) {
        this.bindInitialized(event,callback);
    },

    bindTagsUpdatedWithPartial : function(event,callback,tagpartial) {
        if(typeof callback != 'function' || callback == null)
            throw new Error("Invalid callback argument");

        if(typeof tagpartial != 'undefined' && !(tagpartial instanceof TagPartial)) //TODO: have to handle metapartials too?
            throw new Error("Invalid tagpartial argument");

        var partialstr = tagpartial.toString();

        if(typeof this._eventHandlers.TAGS_UPDATED_WITH_PARTIAL == 'undefined')
            this._eventHandlers.TAGS_UPDATED_WITH_PARTIAL = {};

        if(!(partialstr in this._eventHandlers.TAGS_UPDATED_WITH_PARTIAL))
            this._eventHandlers.TAGS_UPDATED_WITH_PARTIAL[partialstr] = {
                partial : tagpartial,
                callbacks : new Array()
            };

        this._eventHandlers.TAGS_UPDATED_WITH_PARTIAL[partialstr].callbacks.push(callback);
    },

    triggerTagsUpdated : function(event) {
        this.triggerInitialized(event);
    },

    triggerTagsUpdatedWithPartial : function(event,tagOrPartial) {
        if(typeof tagOrPartial == 'undefined' || (!(tagOrPartial instanceof Tag) && !(tagOrPartial instanceof TagPartial))) return;

        if(typeof this._eventHandlers.TAGS_UPDATED_WITH_PARTIAL != 'undefined')
            for(var i in this._eventHandlers.TAGS_UPDATED_WITH_PARTIAL) {
                if(this._eventHandlers.TAGS_UPDATED_WITH_PARTIAL[i].partial.isMatch(tagOrPartial)) {
                    for(var c in this._eventHandlers.TAGS_UPDATED_WITH_PARTIAL[i].callbacks) {
                        this._eventHandlers.TAGS_UPDATED_WITH_PARTIAL[i].callbacks[c].apply();
                    }
                }
            }
    },

    getTags : function(direction,tagPartial,fallBackToCheaters) {

        if(arguments.length == 0) {
            return {'out':this.outTags,'in':this.inTags};
        }

        if(typeof tagPartial == 'undefined') {
            if(direction == Tag.DIRECTION.OUT)
                return this.outTags;
            else if(direction == Tag.DIRECTION.IN)
                return this.inTags;
            else
                return null;
        }

        if(!(tagPartial instanceof TagPartial)) throw new Error("Second argument [tagPartial] must be an instance of TagPartial");

        if(direction != Tag.DIRECTION.OUT && direction != Tag.DIRECTION.IN) throw new Error("First argument [direction] must be 'out' or 'in'");

        var tags = this[direction+'Tags'];

        var newtags = new Array();

        for(var i in tags) {
            var tag = tags[i];

            if(tagPartial.isMatch(tag))
                newtags.push(tag);
        }

        if(newtags.length == 0 && typeof fallBackToCheaters != 'undefined' && fallBackToCheaters) {

            var isFound = function(tag,tags) {
                var found = false;
                $.each(tags,function(i,e){
                    if(Tag.toPartial(tag).isMatch(e)) {
                        found = true;
                        return false;
                    }
                });
                return found;
            };

            var findIt = function(o) {
                if(typeof o == 'undefined' || o == null) return;
                $.each(o,function(i,tag){
                    if(tag instanceof Array) {
                        findIt.apply(this,[tag]);
                    } else if(typeof tag.TagDirection != 'undefined') {
                        //tags may be repeated within the cheaters, only add uniques to the list
                        if(tagPartial.isMatch(tag) && !isFound(tag,newtags)) {
                            newtags.push(tag);
                        }
                    }
                });
            };

            findIt(this.Cheaters);
        }

        return newtags;
    },

    getOutTags : function(tagPartial,fallBackToCheaters) {
        return this.getTags(Tag.DIRECTION.OUT,tagPartial,fallBackToCheaters);
    },

    getInTags : function(tagPartial,fallBackToCheaters) {
        return this.getTags(Tag.DIRECTION.IN,tagPartial,fallBackToCheaters);
    },

    getMetas : function() {
        return this.metas;
    },

    updateMeta : function(role, value, suppressEvents) {
        var me = this;

        if($.trim(value) != '') {
            var meta = new Meta();
            meta.MetaName = role;
            meta.MetaValue = value;

            me.metas[role] = meta;
        } else {
        	if(me.metas[role])
        		delete me.metas[role];
        }

//        $.each(this.metas, function(i, tag) {
//            if(tag.MetaName == role) {
//                me.metas.splice(i, 1);
//                return false;
//            }
//        });


        if(typeof suppressEvents == 'undefined' || !suppressEvents) {
            this.trigger(Taggable.EVENTS.TAGS_UPDATED);
        }
    },

    updateTags : function(direction,tagPartial,tags,suppressEvents) {
        this.removeTags(direction,tagPartial,true);
        if(direction == 'out')
            this.addOutTags(tags,true);
        else
            this.addInTags(tags,true);

        if(typeof suppressEvents == 'undefined' || !suppressEvents) {
            this.trigger(Taggable.EVENTS.TAGS_UPDATED_WITH_PARTIAL,tagPartial);
            this.trigger(Taggable.EVENTS.TAGS_UPDATED);
        }
    },

    removeTags : function(direction,tagPartial,suppressEvents) {

        if(!(tagPartial instanceof TagPartial)) throw new Error("Second argument [tagPartial] must be an instance of TagPartial");

        var tags = direction == 'out' ? this.outTags : this.inTags;

        var newtags = $.grep(tags,function(tag){
            return tagPartial.isMatch(tag);
        },true);

        if(newtags.length < tags.length) {

            if(direction == 'out')
                this.outTags = newtags;
            else
                this.inTags = newtags;

            if(typeof suppressEvents == 'undefined' || !suppressEvents) {
                this.trigger(Taggable.EVENTS.TAGS_UPDATED_WITH_PARTIAL,tagPartial);
                this.trigger(Taggable.EVENTS.TAGS_UPDATED);
            }
        }
    },

    //TODO: this could possibly be merged with removeTags() - very similar
    removeTag : function(direction,tagPartial,suppressEvents) {

        var me = this;

        if(!(tagPartial instanceof TagPartial)) throw new Error("Second argument [tagPartial] must be an instance of TagPartial");

        var tags = this[direction+'Tags'];
        $(tags).each(function(i,tag){
            if(tagPartial.isMatch(tag)) {
                tags.splice(i,1);

                if(typeof suppressEvents == 'undefined' || !suppressEvents) {
                    me.trigger(Taggable.EVENTS.TAGS_UPDATED_WITH_PARTIAL,tagPartial);
                    me.trigger(Taggable.EVENTS.TAGS_UPDATED);
                }

                //only process the first match
                return false;
            }
        });
    },

    removeOutTag : function(tagPartial,suppressEvents) {
        this.removeTag(Tag.DIRECTION.OUT,tagPartial,suppressEvents);
    },

    removeInTag : function(tagPartial,suppressEvents) {
        this.removeTag(Tag.DIRECTION.IN,tagPartial,suppressEvents);
    },

    getMetaValue : function(role) {
        if(role.substr(0,1) == '#')
            role = role.substring(1);

//        console_log(this.metas[role]);
        if(this.metas[role])
        	return this.metas[role].MetaValue;

//        var matches = $.grep(this.metas, function(meta) {
//        	console_log(meta);
//            return (meta.MetaName == role);
//        });
//        console_log(matches[0]);

//        if(matches.length > 0)
//            return matches[0].MetaValue;

        return null;
    },

    addTag : function(direction,tag,suppressEvents) {
        //TODO: validate param & current existence in outTags
        this[direction+'Tags'].push(tag);

        var tagpartial = tag.toPartial();
        //tagpartial.TagValue = null;
        //tagpartial.TagValueDisplay = null;

        if(typeof suppressEvents == 'undefined' || !suppressEvents) {
            this.trigger(Taggable.EVENTS.TAGS_UPDATED_WITH_PARTIAL,tagpartial);
            this.trigger(Taggable.EVENTS.TAGS_UPDATED);
        }
    },

    addOutTag : function(tag,suppressEvents) {
        this.addTag(Tag.DIRECTION.OUT,tag,suppressEvents);
    },

    addInTag : function(tag,suppressEvents) {
        this.addTag(Tag.DIRECTION.IN,tag,suppressEvents);
    },

    addMoreTags : function(direction,tags,suppressEvents) {
        if(!tags || tags.length == 0) return;

        var me = this;
        //TODO: validate param & current existence in outTags

        $.each(tags,function(i,tag){
            me[direction+'Tags'].push(tag);
        });

        var tagpartial = tags[0].toPartial();
        //tagpartial.TagValue = null;
        //tagpartial.TagValueDisplay = null;

        if(typeof suppressEvents == 'undefined' || !suppressEvents) {
            this.trigger(Taggable.EVENTS.TAGS_UPDATED_WITH_PARTIAL,tagpartial);
            this.trigger(Taggable.EVENTS.TAGS_UPDATED);
        }
    },

    addOutTags : function(tags,suppressEvents) {
        this.addMoreTags(Tag.DIRECTION.OUT,tags,suppressEvents);
    },

    addInTags : function(tags,suppressEvents) {
        this.addMoreTags(Tag.DIRECTION.IN,tags,suppressEvents);
    },

    replaceTag : function(direction,tagPartial,newtag,suppressEvents) {
        //TODO: validate params

        var tags = this[direction+'Tags'];

        for(var i in tags) {
            var tag = tags[i];

            if(tagPartial.isMatch(tag)) {
                tags[i] = newtag;

                if(typeof suppressEvents == 'undefined' || !suppressEvents) {
                    this.trigger(Taggable.EVENTS.TAGS_UPDATED_WITH_PARTIAL,tagPartial);
                    this.trigger(Taggable.EVENTS.TAGS_UPDATED);
                }

                return;
            }
        }
    },

    replaceOutTag : function(tagPartial,newtag,suppressEvents) {
        this.replaceTag(Tag.DIRECTION.OUT,tagPartial,newtag,suppressEvents);
    },

    replaceInTag : function(tagPartial,newtag,suppressEvents) {
        this.replaceTag(Tag.DIRECTION.IN,tagPartial,newtag,suppressEvents);
    },

    replaceTags : function(direction,tagPartial,newtags,suppressEvents) {
        //TODO: validate params

        var found = false;
        var newTags = new Array();

        $(this[direction+'Tags']).each(function(i,tag){

            if(tagPartial.isMatch(tag)) {
                if(!found) {
                    $(newtags).each(function(j,newtag){
                        newTags.push(newtag);
                    });
                    found = true;
                }
            } else {
                newTags.push(tag);
            }
        });

        if(found) {
            this[direction+'Tags'] = newTags;
        }

        if(found && (typeof suppressEvents == 'undefined' || !suppressEvents)) {
            this.trigger(Taggable.EVENTS.TAGS_UPDATED_WITH_PARTIAL,tagPartial);
            this.trigger(Taggable.EVENTS.TAGS_UPDATED);
        }
    },

    replaceOutTags : function(tagPartial,newtags,suppressEvents) {
        this.replaceTags(Tag.DIRECTION.OUT,tagPartial,newtags,suppressEvents);
    },

    replaceInTags : function(tagPartial,newtags,suppressEvents) {
        this.replaceTags(Tag.DIRECTION.IN,tagPartial,newtags,suppressEvents);
    },

    addTags : function(metas,outTags,inTags) {
        var me = this;

//        if(typeof this.metas == 'undefined')
//            this.metas = new Array();


        var t;
        me.metas = metas;

//        if(metas != null && metas.length > 0) {
//            $.each(metas,function(i,tag){
//
//                t = new Meta(tag);
//                me.metas.push(t);
//            });
//        }

        if(outTags != null && outTags.length > 0) {
            $.each(outTags,function(i,tag){
                t = new Tag(tag);

                if(t.TagLinkNode != null)
                    t.TagLinkNode = new NodeObject(t.TagLinkNode);

                me.outTags.push(t);
            });
        }

        if(inTags != null && inTags.length > 0) {
            $.each(inTags,function(i,tag){
                t = new Tag(tag);

                if(t.TagLinkNode != null)
                    t.TagLinkNode = new NodeObject(t.TagLinkNode);

                me.inTags.push(t);
            });
        }

        return this;

        //DO NOT FIRE EVENTS, THIS METHOD IS ONLY USED TO INITIALIZE OBJECT WITH TAGS
    },

    setPartials : function( metaPartials, outPartials, inPartials ) {

        this.MetaPartials = metaPartials;
        this.OutPartials = outPartials;
        this.InPartials = inPartials;

        return this;
    },

    getMetaPartials: function() {
        return this.MetaPartials;
    },

    getOutPartials: function() {
        return this.OutPartials;
    },

    getInPartials: function() {
        return this.InPartials;
    },

    increaseMetaPartials : function(metaName) {
        if(typeof metaName == 'undefined' || metaName == null)
            return;

        if(metaName == 'fields' || metaName == 'all')
            this.MetaPartials = 'all';
        else
            this.MetaPartials = PartialUtils.increasePartials(this.MetaPartials, '#'+metaName.lTrim('#'));
    },

    increaseOutPartials : function(role) {
        if(typeof role == 'undefined' || role == null)
            return;

        if(role == 'all')
            this.OutPartials = 'all';
        else if(role == 'fields')
            this.OutPartials = PartialUtils.increasePartials(this.OutPartials, 'fields');
        else
            this.OutPartials = PartialUtils.increasePartials(this.OutPartials, '#'+role.lTrim('#'));
    },

    increaseInPartials : function(role) {
        if(typeof role == 'undefined' || role == null)
            return;

        if(role == 'all')
            this.InPartials = 'all';
        else if(role == 'fields')
            this.InPartials = PartialUtils.increasePartials(this.InPartials, 'fields');
        else
            this.InPartials = PartialUtils.increasePartials(this.InPartials, '#'+role.lTrim('#'));
    },

    decreaseMetaPartials : function(metaName) {
        this.MetaPartials = PartialUtils.decreasePartials(this.MetaPartials, '#'+metaName.lTrim('#'));
    },

    decreaseOutPartials : function(role) {
        this.OutPartials = PartialUtils.decreasePartials(this.OutPartials, '#'+role.lTrim('#'));
    },

    decreaseInPartials : function(role) {
        this.InPartials = PartialUtils.decreasePartials(this.InPartials, '#'+role.lTrim('#'));
    },

    hasMetaPartials : function() {
        return this.MetaPartials != null;
    },

    hasOutPartials : function() {
        return this.OutPartials != null;
    },

    hasInPartials : function() {
        return this.InPartials != null;
    }
};

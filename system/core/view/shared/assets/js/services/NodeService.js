/*
THE ROSCO SINGLETON-FACTORY PATTERN:

var T = (function(){

    var me = function() {
            var d = new Date();

            return {
                a : function() {
                    return d;
                },
                getInstance : function() {
                    return me();
                }
            }
        };

    return {
        getInstance : me
    };
})().getInstance();

var newT = T.getInstance();

var notTheSame = newT.a() != T.a();

*/

var NodeService = (function() {

    var newInstance = function() {

        var UUID = 'nodeservice-'+(new Date().getTime())+Math.floor(Math.random()*1024);

        /* private attributes */
        var quickAddLoading = false;
        var findAllLoading = false;
        var currentRequest;

        var checkResponse = function(json,url,params) {
            if(json == null || typeof json != "object") {
                var paramMsg = "Params:\n";
                if(params != null && typeof params == 'object') {
                    for(var paramkey in params) {
                        paramMsg += "   "+paramkey+": "+params[paramkey]+"\n";
                    }
                }

                var jsonMsg = "JSON is ";
                if(json == null)
                    jsonMsg += "null";
                else if(typeof json != 'object')
                    jsonMsg += "not an object: "+json;
                else
                    jsonMsg += "is valid";

                jsonMsg += "\n\n";

                var urlMsg = "URL: " + url + "\n\n";

                console_log("There was a problem with the following API call:\n\n" + jsonMsg + urlMsg + paramMsg);

                return false;
            }

            return true;
        };

        /* public methods */
        return {

            getUUID : function() {
                return UUID;
            },

            getInstance : function() {
                return newInstance();
            },

            updateMeta : function(node, metaID, value, options) {

                options = $.extend({
                    nonce : null,
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/update-meta.json/',
                    async : true
                },options || {});

                var params = {
                    MetaID: metaID,
                    Value: value,
                    ElementSlug: node.Element.Slug,
                    NodeSlug: node.Slug,
                    action_nonce: options.nonce
                };

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({

                    dataType: 'json',
                    type: 'POST',
                    url: options.url,
                    data: params,
                    async : options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {

                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                            if(typeof options.success == 'function')
                                options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                });

            },

            edit : function(node, options) {

                options = $.extend({
                    nonce : null,
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/edit.json/',
                    async : true
                },options || {});

                var params = {
                    ElementSlug: node.Element.Slug,
                    NodeSlug: node.Slug,
                    action_nonce: options.nonce
                };

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({

                    dataType: 'json',
                    type: 'POST',
                    url: options.url,
                    data: params,
                    async: options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {

                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                        if(typeof options.success == 'function')
                            options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                });

            },

            add : function(node,options) {
                options = $.extend({
                    nonce : null,
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/add.json/',
                    async : true
                },options || {});

                var params = {
                    ElementSlug: node.Element.Slug,
                    NodeSlug: node.Slug,
                    Title: node.Title,
                    action_nonce: options.nonce
                };

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({

                    dataType: 'json',
                    type: 'POST',
                    url: options.url,
                    data: params,
                    async: options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {

                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                        if(typeof options.success == 'function')
                            options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                });
            },

            updateTags : function(node,tags,options,direction) {
                options = $.extend({
                    nonce : null,
                    tagRole : null,
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/update-tags.json/',
                    async : true
                },options || {});

                var params = {
                    ElementSlug: node.Element.Slug,
                    NodeSlug: node.Slug,
                    TagDirection: direction,
                    tags : [],
                    tagRole : options.tagRole,
                    action_nonce: options.nonce
                };

                $.each(tags,function(i,t) {
                    params.tags.push({
                        TagElement: t.TagElement,
                        TagSlug: t.TagSlug,
                        TagRole: t.TagRole,
                        TagValue: t.TagValue,
                        TagValueDisplay: t.TagValueDisplay,
                        TagSortOrder: t.TagSortOrder
                    });
                });

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({
                    dataType: 'json',
                    type: 'POST',
                    url: options.url,
                    data: params,
                    async: options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {
                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                        if(typeof options.success == 'function')
                            options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                })
            },

            addTag : function(node,tag,options) {
                options = $.extend({
                    nonce : null,
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/add-tag.json/',
                    async : true
                },options || {});

                var params = {
                    ElementSlug: node.Element.Slug,
                    NodeSlug: node.Slug,
                    TagElement: tag.TagElement,
                    TagSlug: tag.TagSlug,
                    TagRole: tag.TagRole,
                    TagValue: tag.TagValue,
                    TagValueDisplay: tag.TagValueDisplay,
                    TagSortOrder: tag.TagSortOrder,
                    action_nonce: options.nonce
                };

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({

                    dataType: 'json',
                    type: 'POST',
                    url: options.url,
                    data: params,
                    async: options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {

                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                        if(typeof options.success == 'function')
                            options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                });
            },

            removeTag : function(node,tag,options) {
                options = $.extend({
                    nonce : null,
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/remove-tag.json/',
                    async : true
                },options || {});

                var params = {
                    ElementSlug: node.Element.Slug,
                    NodeSlug: node.Slug,
                    TagElement: tag.TagElement,
                    TagSlug: tag.TagSlug,
                    TagRole: tag.TagRole,
                    TagValue: tag.TagValue,
                    TagValueDisplay: tag.TagValueDisplay,
                    action_nonce: options.nonce
                };

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({

                    dataType: 'json',
                    type: 'POST',
                    url: options.url,
                    data: params,
                    async: options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {

                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                        if(typeof options.success == 'function')
                            options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                });
            },

            getTags : function(node,direction,tagPartial,options) {
                options = $.extend({
                    nonce : null,
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/get-tags.json/',
                    async : true
                },options || {});

                var params = {
                    ElementSlug: node.Element.Slug,
                    NodeSlug: node.Slug,
                    TagDirection: direction,
                    TagPartial: tagPartial.toString()
                };

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({

                    dataType: 'json',
                    type: 'POST',
                    url: options.url,
                    data: params,
                    async: options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {

                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                        if(typeof options.success == 'function')
                            options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                });
            },

            quickAdd : function(node, options) {

                options = $.extend({
                    nonce : null,
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/quick-add.json/',
                    async : true
                },options || {});

                var params = {
                    Title: node.Title,
                    ElementSlug: node.Element.Slug,
                    action_nonce: options.nonce
                };

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({

                    dataType: 'json',
                    type: 'POST',
                    url: options.url,
                    data: params,
                    async: options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {

                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                        if(typeof options.success == 'function')
                            options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                });

            },

            replace : function(node, options) {

                options = $.extend({
                    nonce : null,
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/replace.json/',
                    async : true
                },options || {});

                var params = {
                    Title: node.Title,
                    ElementSlug: node.Element.Slug,
                    action_nonce: options.nonce
                };

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({

                    dataType: 'json',
                    type: 'POST',
                    url: options.url,
                    data: params,
                    async: options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {

                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                        if(typeof options.success == 'function')
                            options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                });

            },

            get : function(node, options) {

                options = $.extend({
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/get.json/',
                    async : true
                },options || {});

                var params = {
                    NodeSlug: node.Slug,
                    ElementSlug: node.Element.Slug
                };

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({

                    dataType: 'json',
                    type: 'GET',
                    url: options.url,
                    data: params,
                    async: options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {

                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                        if(typeof options.success == 'function')
                            options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                });

            },

            findAll : function(nodeQuery,options) {

                options = $.extend({
                    success : null,
                    error : null,
                    params : null,
                    url : '/api/node/find-all.json/',
                    async : true
                },options || {});

                if(!(nodeQuery instanceof NodeQuery))
                    throw Error('First parameter must be an instance of NodeQuery');

                if(!findAllLoading && typeof options.success == 'function') {

                    var params = nodeQuery.getParameters();
                    params['MaxRows'] = nodeQuery.getLimit();
                    params['Page'] = nodeQuery.getOffset();

                    $.each(nodeQuery.getOrderBys(),function(k,v){
                       params['sort['+k+']'] = v;
                    });

                    findAllLoading = true;

                    if(typeof currentRequest != 'undefined') currentRequest.abort();

                    if(typeof options.params == 'function')
                        options.params(params);

                    currentRequest = $.ajax({
                        dataType: 'json',
                        type: 'GET',
                        url: options.url,
                        data: params,
                        async: options.async,

                        error: function(xhr, msg) {
                            if(typeof options.error == 'function')
                                options.error({
                                    "Error" : {
                                        "Code" : xhr.status,
                                        "Message" : msg
                                    }
                                });
                        },

                        success: function(json) {

                            if(!checkResponse(json,options.url,params)) return;

                            if(json.Errors) {

                                if(json.Errors.length > 0) {
                                    alert(json.Errors[0].Message);
                                }

                                if(typeof options.error == 'function')
                                    options.error(json);

                            } else {

                                nodeQuery.setTotalRecords(json.TotalRecords);
                                nodeQuery.setResults(json.Nodes);

                                options.success(nodeQuery);
                            }
                        },

                        complete: function() {
                            findAllLoading = false;
                        }
                    });
                }

                //no need for quickSearch if findall is implemented
            },

            remove : function(node,options) {
                options = $.extend({
                    nonce : null,
                    success : null,
                    error : null,
                    complete : null,
                    params : null,
                    url : '/api/node/delete.json/',
                    async : true
                },options || {});

                var params = {
                    ElementSlug: node.Element.Slug,
                    NodeSlug: node.Slug,
                    action_nonce: options.nonce
                };

                if(typeof options.params == 'function')
                    options.params(params);

                $.ajax({

                    dataType: 'json',
                    type: 'POST',
                    url: options.url,
                    data: params,
                    async: options.async,

                    error: function(req, err) {
                        if(typeof options.error == 'function')
                            options.error(err);
                    },

                    success: function(json) {

                        if(!checkResponse(json,options.url,params)) return;

                        if(typeof options.error == 'function' && json.Errors) {
                            options.error(json);

                            if(json.Errors.length > 0) {
                                alert(json.Errors[0].Message);
                            }

                        } else if(!json.Errors) {
                        if(typeof options.success == 'function')
                            options.success(json);
                        }
                    },

                    complete: function() {
                        if(typeof options.complete == 'function')
                            options.complete();
                    }
                });

            }
        };
    };

    return {
        getInstance : newInstance
    }
})().getInstance();

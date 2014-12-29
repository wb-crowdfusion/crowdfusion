var NodeTagWidget = function(page,tagPartial,domID,options) {

    NodeTagWidget.superclass.constructor.call(this,page,tagPartial,domID,options);

    this.DefaultService = NodeService;

};
extend(NodeTagWidget,AbstractTagWidget);


Ext.ns('Tine.widgets.dialog');

Tine.widgets.dialog.MultipleEditDialog = function (config) {
    Ext.apply(this, config);
};

Tine.widgets.dialog.MultipleEditDialog.prototype = {
    
    init: function(dialog) {
//        dialog.onEnable = dialog.onEnable.createSequence(this.onEnable, dialog);
        dialog.onRender = dialog.onRender.createSequence(this.onRender, dialog);
    },
//    
//    onEnable: function() {
//          alert('asd');
//          return;
//          
//    },
    
    onRender: function() {
        Tine.log.debug('SEL',this.selections);
        Tine.log.debug('REC',this.record);
        Tine.log.debug('MODE',this.mode);
    }
}
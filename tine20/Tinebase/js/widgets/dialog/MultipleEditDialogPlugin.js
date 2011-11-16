
Ext.ns('Tine.widgets.dialog');

Tine.widgets.dialog.MultipleEditDialog = function (config) {
    Ext.apply(this, config);
};

Tine.widgets.dialog.MultipleEditDialog.prototype = {
    init: function(dialog) {
        
        dialog.onRender = dialog.onRender.createSequence(this.onRender, dialog);
        

    },
    
    onRender: function() {
        Tine.log.debug(this);
    }
}
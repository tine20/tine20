
Ext.ns('Tine.widgets.editDialog');

Tine.widgets.dialog.MultipleEditDialog = function (config) {
    Ext.apply(this, config);
};

Tine.widgets.dialog.MultipleEditDialog.prototype = {
    
    app: null,
    
    editDialog: null,
    
    form: null,
    
    disabledFields: [],
    
    init: function(editDialog) {
        
        this.disabledFields = {};
        
        this.editDialog = editDialog;
        this.app = Tine.Tinebase.appMgr.get(this.editDialog.app);
        
        this.editDialog.selections = Ext.decode(this.editDialog.selections);
        this.form = this.editDialog.getForm();
       
        this.editDialog.on('load', this.loadCombinedRecord, this);
    },
    
    loadCombinedRecord: function() {
        for(fieldName in this.editDialog.record.data) {
            if(typeof(this.editDialog.record.data[fieldName]) == 'object') continue;
            Ext.each(this.editDialog.selections, function(selection,index){
                if(selection[fieldName] != this.editDialog.record.data[fieldName]) {
                    if(field = this.form.findField(fieldName)) {
                        Tine.log.debug('FIELD:',field);
                        field.disable();
                    }
                    
                }
            },this);   
        }
    }
}
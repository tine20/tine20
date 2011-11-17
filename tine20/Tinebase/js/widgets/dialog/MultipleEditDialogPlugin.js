
Ext.ns('Tine.widgets.editDialog');

Tine.widgets.dialog.MultipleEditDialog = function (config) {
    Ext.apply(this, config);
};

Tine.widgets.dialog.MultipleEditDialog.prototype = {
    
    app: null,
    
    editDialog: null,
    
    form: null,
    
    disabledFields: [],
    
    changes: null,
    
    init: function(editDialog) {
        
        this.disabledFields = {};
        
        this.editDialog = editDialog;
        this.app = Tine.Tinebase.appMgr.get(this.editDialog.app);
        
        this.editDialog.selections = Ext.decode(this.editDialog.selections);
        this.form = this.editDialog.getForm();
        
        this.editDialog.on('load', this.disableFields, this);
        
        this.editDialog.onRecordUpdate = this.editDialog.onRecordUpdate.createInterceptor(this.onRecordUpdate, this);
        this.editDialog.onApplyChanges = function(button, event, closeWindow) { this.onRecordUpdate(); }       
    },
    
    onRecordUpdate: function() {
        this.changes = [];
        this.form.items.each(function(item){
            if(item.edited) {
                this.changes.push({ name: item.getName(),  value: item.getValue() });
            }
        },this);
        
        Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to change these {0} records?'), this.editDialog.selections.length), function(_btn) {
            if (_btn == 'yes') {
                Ext.MessageBox.wait(_('Please wait'), _('Applying changes'));
                
                Tine.log.debug('RClass',this.editDialog.recordClass);
                Tine.log.debug('SELECTIONS',this.editDialog.selections);
                Tine.log.debug('CHANGES',this.changes);
        
                // here comes the backend call
                Ext.Ajax.request({
                    url : 'index.php',
<<<<<<< HEAD
                    params : { method : 'Tinebase.updateMultipleRecords', recordModel: this.editDialog.recordClass.getMeta('appName') + '_Model_' + this.editDialog.recordClass.getMeta('modelName'), changes: this.changes, selections: this.editDialog.selections },
=======
                    params : { method : 'Tinebase.updateMultipleRecords', recordClass: this.editDialog.recordClass.getMeta('modelName'), changes: this.changes, selections: this.editDialog.selections },
>>>>>>> 277a2fe1b814cbaac9883b208be69737d29ae17a
                    success : function(_result, _request) {
                        Ext.MessageBox.hide();
                        this.editDialog.purgeListeners();
                        this.editDialog.window.close();                       
                    }
                });
            }
        }, this);
        
        return false;
    },
    
    disableFields: function() {
        for(fieldName in this.editDialog.record.data) {
            if(typeof(this.editDialog.record.data[fieldName]) == 'object') continue;
            Ext.each(this.editDialog.selections, function(selection,index) {
                var field = this.form.findField(fieldName);
                if(field) {
                    field.originalValue = this.editDialog.record.data[fieldName];
                    
                    // set back??
//                    field.on('dblclick',function(){
//                        this.setValue(this.originalValue);
//                        this.edited = false;
//                        this.removeClass('applyToAll');
//                        if(this.multi) {
//                            this.addClass('notEdited');
//                        }
//                    });
                    
                if(selection[fieldName] != this.editDialog.record.data[fieldName]) {
              
                        
                        field.setReadOnly(true);
                        field.addClass('notEdited');
                        field.multi = true;
                        field.edited = false;
                        field.on('focus',function() {
                            this.setReadOnly(false);
                            this.on('blur',function() {
                                if(this.originalValue != this.getValue()) {
                                    this.removeClass('notEdited');
                                    this.addClass('applyToAll');
                                    this.edited = true;
                                }
                            });
                        });
                                       
                } else {
                  
                  field.on('blur',function() {
                                if(this.originalValue != this.getValue()) {
                                    this.addClass('applyToAll');
                                    this.edited = true;
                                } else {
                                    this.edited = false;
                                    this.removeClass('applyToAll');
                                    if(this.multi) {
                                        this.addClass('notEdited');
                                    }
                                }
                            });  
                }
                }
            },this);   
        } 
    }
}

Ext.ns('Tine.widgets.editDialog');

Tine.widgets.dialog.MultipleEditDialogPlugin = function (config) {
    Ext.apply(this, config);
};

Tine.widgets.dialog.MultipleEditDialogPlugin.prototype = {
    
    app: null,
    
    editDialog: null,
    
    form: null,
    changes: null,
    
    init: function(editDialog) {
        
        this.editDialog = editDialog;
        this.app = Tine.Tinebase.appMgr.get(this.editDialog.app);
        this.form = this.editDialog.getForm();
        
        this.editDialog.on('render', this.handleFields, this);
        
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
        
        if(this.changes.length == 0) {
            this.editDialog.purgeListeners();
            this.editDialog.window.close(); 
            return false;
        }
        
        var filter = this.editDialog.sm.getSelectionFilter();
 
        Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to change these {0} records?'), this.editDialog.sm.getCount()), function(_btn) {
            if (_btn == 'yes') {
                Ext.MessageBox.wait(_('Please wait'), _('Applying changes'));
          
                // here comes the backend call
                Ext.Ajax.request({
                    url : 'index.php',
                    params : { method : 'Tinebase.updateMultipleRecords', 
                               appName: this.editDialog.recordClass.getMeta('appName'), 
                               modelName: this.editDialog.recordClass.getMeta('modelName'), 
                               changes: this.changes, 
                               filter: filter 
                             },
                    success : function(_result, _request) {
                        Ext.MessageBox.hide();
                        this.editDialog.fireEvent('update');
                        this.editDialog.purgeListeners();
                        this.editDialog.window.close();                       
                    }, scope: this
                });
            }
        }, this);
        
        return false;
    },
    
    handleFields: function() {
        // Default Fields    
        Ext.each(this.editDialog.record.store.fields.keys, function(fieldKey) {
            var field = this.form.findField(fieldKey);

            Ext.each(this.editDialog.sm.getSelections(), function(selection,index) {          
                if(field) {
                   field.originalValue = this.editDialog.record.data[fieldKey];

                   if(selection.data[fieldKey] != field.originalValue) {
                        this.handleField(field,fieldKey,false);
                        return false;
                    } else {
                        if(index == this.editDialog.sm.selections.length-1) {
                             this.handleField(field,fieldKey,true);
                             return false;
                        }
                    }
                     
                }
            },this);   
        },this);
       
        // Customfields
        Ext.each(this.editDialog.cfConfigs, function(el) {
            var fieldKey = el.data.name;
            var field = this.form.findField('customfield_' + fieldKey);
            field.originalValue = this.editDialog.record.data.customfields[fieldKey];
             Ext.each(this.editDialog.sm.getSelections(), function(selection,index) {
                if(field) { 
                    
                    if(selection.data.customfields[fieldKey] != field.originalValue) {
                        this.handleField(field,fieldKey,false);
                        return false;
                    } else {
                    if(index == this.editDialog.sm.selections.length-1) {
                             this.handleField(field,fieldKey,true);
                             return false;
                        }
                    }

                }
            },this);            
            
            
        },this);
        
    },
    
    handleField: function(field,fieldKey,samevalue) {
        if(!samevalue) {
            field.setReadOnly(true);
            field.addClass('notEdited');
            field.multi = true;
            field.edited = false;
                        
            field.on('focus', function() {
                if(this.readOnly) this.originalValue = this.startValue;
                this.setReadOnly(false);
            });
                        
            field.on('blur', function() {
                if(this.originalValue != this.getValue()) {
                    this.removeClass('notEdited');
                    this.addClass('applyToAll');
                    this.edited = true;
                } else {
                    this.edited = false;
                    this.removeClass('applyToAll');
                    
                    if(this.multi) {
                        this.setReadOnly(true);
                        this.addClass('notEdited');
                    }
                }
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
}
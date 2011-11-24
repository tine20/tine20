
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
        
        this.editDialog.on('render', function() {
        	this.onAfterRender();
        },this);
        
        this.editDialog.onRecordLoad = this.editDialog.onRecordLoad.createInterceptor(this.onRecordLoad, this);
        this.editDialog.onRecordUpdate = this.editDialog.onRecordUpdate.createInterceptor(this.onRecordUpdate, this);
        this.editDialog.onApplyChanges = function(button, event, closeWindow) { this.onRecordUpdate() }
        
        
    },
    
    onRecordLoad: function() {
        
    	if (! this.editDialog.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
    	
    	this.editDialog.getForm().loadRecord(this.editDialog.record);
    	
        Tine.log.debug('loading of the following record completed:');
        Tine.log.debug(this.editDialog.record);
    	
    	this.editDialog.getForm().clearInvalid();
    	
        this.editDialog.window.setTitle(String.format(_('Edit {0} {1}'), this.editDialog.sm.getCount(), this.editDialog.i18nRecordsName ));

    	
    	Ext.each(this.form.record.store.fields.keys, function(fieldKey) {
    		var field = this.form.findField(fieldKey);
    		
    		if(field) {
    			Ext.each(this.editDialog.sm.getSelections(), function(selection,index) {          

                    field.originalValue = this.editDialog.record.data[fieldKey];

                    if(selection.data[fieldKey] != field.originalValue) {
                 	    
                         this.handleField(field, fieldKey, false);
                         return false;
                     } else {
                         if(index == this.editDialog.sm.selections.length-1) {
                              this.handleField(field, fieldKey, true);
                              return false;
                         }
                     }
             },this);       
    		}
    		
    		Tine.log.debug(fieldKey,field);
    	}, this);

    	Ext.each(this.editDialog.cfConfigs, function(el) {
            var fieldKey = el.data.name;
            var field = this.form.findField('customfield_' + fieldKey);
            Tine.log.debug('CF ' + fieldKey, this.editDialog.record.data.customfields[fieldKey]);
            if(field) {
            	field.setValue(this.editDialog.record.data.customfields[fieldKey]);
            	Ext.each(this.editDialog.sm.getSelections(), function(selection,index) {
            		          
                    if(selection.data.customfields[fieldKey] != this.editDialog.record.data.customfields[fieldKey]) {
                        this.handleField(field,fieldKey,false);
                        return false;
                    } else {
                        if(index == this.editDialog.sm.selections.length-1) {
                             this.handleField(field,fieldKey,true);
                             return false;
                        }
                    }
            	},this);
            }
        },this);    	
    	
        this.editDialog.updateToolbars(this.editDialog.record, this.editDialog.recordClass.getMeta('containerProperty'));
        this.editDialog.loadMask.hide();
        
        this.editDialog.tbar.hide();

    	return false;
    },
    
    onAfterRender: function() {
        this.form.items.each(function(item){
        	if ((!(item instanceof Ext.form.TextField)) && (!(item instanceof Ext.form.Checkbox))) {
        		item.disable();
        	}
        });
    },
    
    
    onRecordUpdate: function() {
        this.changes = [];
        this.changedHuman = _('<br /><ul style="padding:10px;border:1px">');
        this.form.items.each(function(item){
            if(item.edited) {
                this.changes.push({ name: item.getName(),  value: item.getValue() });
                this.changedHuman += '<li style="padding: 3px 0 3px 15px">' + item.fieldLabel + ': '+ item.getValue() + '</li>';
            }
        },this);
        this.changedHuman += '</ul>';
        
        if(this.changes.length == 0) {
            this.editDialog.purgeListeners();
            this.editDialog.window.close(); 
            return false;
        }
        
        var filter = this.editDialog.sm.getSelectionFilter();
 
        Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to change these {0} records?') + this.changedHuman, this.editDialog.sm.getCount()), function(_btn) {
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
       
    handleField: function(field,fieldKey,samevalue) {
    	
        if(!samevalue) {
            field.setReadOnly(true);
            field.addClass('notEdited');
            field.multi = true;
            field.edited = false;
            field.setValue('');
            
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
        	
        	field.on('focus', function(){
        		if(!this.edited) this.originalValue = this.startValue;
        	});
        	
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
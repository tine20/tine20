
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
        this.editDialog.isValid = this.editDialog.isValid.createInterceptor(this.isValid, this);
        this.editDialog.onApplyChanges = function(button, event, closeWindow) { this.onRecordUpdate(); }       
    },
    
    isValid: function() {
        return true;
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
//    		Tine.log.debug('REC',this.editDialog.record);
    		if(field) {
                
                field.validationTask = null;
                field.allowBlankOrig = field.allowBlank;
                field.allowBlank = true;
                
    			Ext.each(this.editDialog.sm.getSelections(), function(selection,index) {          

                    field.originalValue = this.editDialog.record.data[fieldKey];
//                    Tine.log.debug('Field',field);
//                    Tine.log.debug('FK',selection.data[fieldKey]);
                    
//                    if(typeof this.editDialog.record.data[fieldKey] == 'object') {
//                        field.disable();
//                        return false;
//                    } else {
                    
                        if(selection.data[fieldKey] != field.originalValue) {
                            this.handleField(field, fieldKey, false);
                            return false;
                     } else {
                         if(index == this.editDialog.sm.selections.length-1) {
                              this.handleField(field, fieldKey, true);
                              return false;
                         }
                     }
//                    }
    			},this);       
    		}
    		
    		
    	}, this);

    	Ext.each(this.editDialog.cfConfigs, function(el) {
            var fieldKey = el.data.name;
            var field = this.form.findField('customfield_' + fieldKey);
            
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

        Ext.each(this.editDialog.tbarItems, function(el) {
        	el.disable();
        });
        
        this.editDialog.loadMask.hide();

    	return false;
    },
    
    onAfterRender: function() {
        this.form.items.each(function(item) {
        	if ((!(item instanceof Ext.form.TextField)) && (!(item instanceof Ext.form.Checkbox))) {
        		item.disable();
        	} else if (item instanceof Ext.form.TextField) {
                var subLeft = 0;
                if (item instanceof Ext.form.TriggerField) subLeft = 17;
                if (item instanceof Ext.form.DateField) subLeft += 17;
                item.on('focus',function() {
//                    if(!(this.el.dom instanceof HTMLInputElement)) return;
                    
                    var el = this.el.parent().select('.clearableTrigger');
                    if(el.elements.length > 0) {
                        el.setStyle({display:'block'});
                        return;
                    }
                    
                    var width = this.getWidth();
                    var span = new Ext.Element(document.createElement('span'));

                    span.addClass('clearableTrigger');
                    span.addClassOnOver('over');
                    span.setStyle({left: (width - 16 - subLeft) + 'px'});
                    
                    span.on('click',function() {
                        Tine.log.debug(this);
                        this.setValue('');
                        this.addClass('applyToAll');
                        this.removeClass('notEdited');
                        this.edited = true;
                        span.setStyle({display: 'none'});
                    },this);
                    
                    
                    
                    this.el.setStyle({position:'relative'});
                    this.el.insertSibling(span);
//                    this.un('focus');
                });
                item.on('blur',function() {
                        var el = this.el.parent().parent().select('.clearableTrigger');
                        if(el) el.setStyle({display:'none'});
                    });
                
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
        
//        Tine.log.debug(this.changes); return false;
        
        if(this.changes.length == 0) {
            this.editDialog.purgeListeners();
            this.editDialog.window.close(); 
            return false;
        }
        
//        if(!this.editDialog.isValid()) {
//        	
//        	Ext.MessageBox.alert(_('Errors'), this.editDialog.getValidationErrorMessage());
//        	
//        	this.form.items.each(function(item){
//        		if(item.activeError) {
//        			if(!item.edited) item.activeError = null;
//        		}
//        			Tine.log.debug(item);
//        	});        	
//        	
//        	return false;
//        } else {
        
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
    	
    	Tine.log.debug(field.name,field);
    	
        if(!samevalue) {
        	
            field.setReadOnly(true);
            field.addClass('notEdited');
            field.multi = true;
            field.edited = false;
            field.setValue('');
            field.originalValue = '';
            
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
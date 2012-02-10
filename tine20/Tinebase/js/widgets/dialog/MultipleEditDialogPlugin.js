/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.editDialog');

/**
 * @namespace   Tine.widgets.editDialog
 * @class       Tine.widgets.dialog.MultipleEditDialogPlugin
 * @author      Alexander Stintzing <alex@stintzing.net>
 * 
 * @plugin for Tine.widgets.editDialog
 */
Tine.widgets.dialog.MultipleEditDialogPlugin = function(config) {
    Ext.apply(this, config);
};

Tine.widgets.dialog.MultipleEditDialogPlugin.prototype = {

    app : null,

    editDialog : null,

    selectedRecords: null,
    selectionFilter: null,
    
    form : null,
    changes : null,

    init : function(editDialog) {
        
        this.editDialog = editDialog;
        this.app = Tine.Tinebase.appMgr.get(this.editDialog.app);
        this.form = this.editDialog.getForm();    
        this.editDialog.on('render', this.onAfterRender, this);

        this.editDialog.onRecordLoad = this.editDialog.onRecordLoad.createInterceptor(this.onRecordLoad, this);
        this.editDialog.onRecordUpdate = this.editDialog.onRecordUpdate.createInterceptor(this.onRecordUpdate, this);
        this.editDialog.isValid = this.editDialog.isValid.createInterceptor(this.isValid, this);
        this.editDialog.onApplyChanges = function(button, event, closeWindow) { this.onRecordUpdate(); };
    },

    /**
     * overwrites the isValid function of ther default dialogs
     * @return {}
     */
    isValid : function() {
        // application dependend

        var isValid = true;
        
        if(this.app.appName == 'Addressbook') {
        if (((this.form.findField('n_family').getValue() === '') && (this.form.findField('n_family').edited)) 
            && ((this.form.findField('org_name').getValue() === '') && (this.form.findField('org_name').edited))) {
            var invalidString = String.format(this.app.i18n._('Either {0} or {1} must be given'), this.app.i18n._('Last Name'), this.app.i18n._('Company'));
            
            this.form.findField('n_family').markInvalid(invalidString);
            this.form.findField('org_name').markInvalid(invalidString);
            
            isValid = false;
            }
        } else if (this.app.appName == 'Timetracker') {
            if((this.form.findField('description').getValue() === '') && (this.form.findField('description').edited)) {
                this.form.findField('description').markInvalid(this.app.i18n._('Field "Description" must not be empty'));
                isValid = false;  
            }
        }
        return isValid;
        
    },

    /**
     * find out which fields has differences
     */
    onRecordLoad : function() {

        if (!this.editDialog.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }

        this.editDialog.getForm().loadRecord(this.editDialog.record);

        Tine.log.debug('loading of the following record completed:');
        Tine.log.debug(Ext.decode(this.editDialog.record));

        this.editDialog.getForm().clearInvalid();

        var length = (this.editDialog.selectedRecords.length) ? this.editDialog.selectedRecords.length : _('multiple');
        
        this.editDialog.window.setTitle(String.format(_('Edit {0} {1}'), length, this.editDialog.i18nRecordsName));

        Ext.each(this.form.record.store.fields.keys, function(fieldKey) {
            var field = this.form.findField(fieldKey);

            if (!field) return true;
            
            if(field.multiEditable === false) {
                field.disable();
                return true;
            }
            
            if(! this.editDialog.selectedRecords.length) {
                this.handleField(field, fieldKey, false);
            } else {
                var referenceSelectionData = false; 
                field.isClearable = true;
                return Ext.each(this.editDialog.selectedRecords, function(selection, index) {
                    
                    if(!referenceSelectionData) {
                        referenceSelectionData = selection[fieldKey];
                        if(referenceSelectionData) {
                           if(Ext.isObject(referenceSelectionData)) {
                                if(fieldKey == 'account_id') {
                                    field.originalValue = referenceSelectionData.accountId;
                                    field.isClearable = false;
                                }
                                else if(referenceSelectionData.hasOwnProperty('id')) {
                                    field.originalValue = referenceSelectionData.id;
                                    if(field.allowBlank === false) field.isClearable = false;
                                }
                                else {
                                    // TODO: handle DateFields
                                    field.disable();
                                    return true;
                                }
                            } else {
                                field.originalValue = referenceSelectionData;
                            } 
                        
                        } else {
                            return true;
                        }
                        return true;
                    }

                    if (Ext.encode(selection[fieldKey]) != Ext.encode(referenceSelectionData)) {
                        this.handleField(field, fieldKey, false);
                        return false;
                    } else {
                        if (index == this.editDialog.selectedRecords.length) {
                            this.handleField(field, fieldKey, true);
                            return false;
                        }
                    }
                }, this);
            }
        }, this);

        Ext.each(this.editDialog.cfConfigs, function(el) {
            var fieldKey = el.data.name;
            var field = this.form.findField('customfield_' + fieldKey);

            if (field) {
                
                if(! this.editDialog.selectedRecords.length) {
                    this.handleField(field, fieldKey, false);
                } else {
                
                field.setValue(this.editDialog.record.data.customfields[fieldKey]);
                var referenceSelectionData = false;
                Ext.each(this.editDialog.selectedRecords, function(selection, index) {
                    if(!referenceSelectionData) {
                        referenceSelectionData = selection.customfields[fieldKey];
                        return true;
                    }

                    if (Ext.encode(selection.customfields[fieldKey]) != Ext.encode(referenceSelectionData)) {
                        this.handleField(field, fieldKey, false);
                        return false;
                    } else {
                        if (index == this.editDialog.selectedRecords.length) {
                            this.handleField(field, fieldKey, true);
                            return false;
                        }
                    }
                }, this);
            }
            }
        }, this);

        this.editDialog.updateToolbars(this.editDialog.record, this.editDialog.recordClass.getMeta('containerProperty'));

        Ext.each(this.editDialog.tbarItems, function(item) {
            item.setDisabled(true);
            item.multiEditable = false;
            });

        this.editDialog.loadMask.hide();

        return false;
    },

    /**
     * handle fields for multiedit
     */
    onAfterRender : function() {
        
        Ext.each(this.editDialog.getDisableOnEditMultiple(), function(item){
            item.setDisabled(true);
            item.multiEditable = false;
        });
        
        Ext.each(this.form.record.store.fields.keys, function(fieldKey) {
            var item = this.form.findField(fieldKey);
            if(!item) return true;
        
            if ((!(item.isXType('textfield'))) && (!(item.isXType('checkbox'))) || item.multiEditable === false) {
                item.setDisabled(true);
                return true;
            }

            if (item.isXType('textfield')) {
                item.on('focus', function() {
                  if (!(item.isXType('extuxclearabledatefield', true)) && (item.isClearable !== false)) {
                    var subLeft = 0;
                    if (item.isXType('trigger')) subLeft += 17;

                    var el = this.el.parent().select('.tinebase-editmultipledialog-clearer'), 
                        width = this.getWidth(), 
                        left = (width - 18 - subLeft) + 'px';

                    if (el.elements.length > 0) {
                        el.setStyle({left: left});
                        el.removeClass('hidden');
                        return;
                    }

                    // create Button
                    var button = new Ext.Element(document.createElement('img'));
                    button.set({
                        'src': Ext.BLANK_IMAGE_URL,
                        'title': _('Delete value from all selected records'),
                        'class': 'tinebase-editmultipledialog-clearer',
                        'style': 'left:' + left
                        });
                    
                    button.addClassOnOver('over');
                    button.addClassOnClick('click');

                    button.on('click', function() {
                        if(button.hasClass('undo')) {
                            this.setValue(this.originalValue);
                            button.set({title: _('Delete value from all selected records')});
                            if (this.multi) this.cleared = false;
                        } else {
                            if (this.multi) this.cleared = true;
                            this.setValue('');
                            button.set({title: _('Undo delete value from all selected records')});
                        }
                        this.fireEvent('blur',this);
                    }, this);
                    
                    this.el.insertSibling(button);
                  }
                    this.on('blur', function() {
                        var el = this.el.parent().select('.tinebase-editmultipledialog-clearer');
                        if ((this.originalValue != this.getValue()) || this.cleared) {
                            this.removeClass('tinebase-editmultipledialog-noneedit');
                            
                            var ar = this.el.parent().select('.tinebase-editmultipledialog-dirty');
                            if(ar.elements.length > 0) {
                                ar.setStyle('display','block');
                            } else {
                                var arrow = new Ext.Element(document.createElement('img'));
                                arrow.set({
                                    'src': Ext.BLANK_IMAGE_URL,
                                    'class': 'tinebase-editmultipledialog-dirty',
                                    'height': 5,
                                    'width': 5
                                });
                                this.el.insertSibling(arrow);
                            }
                            
                            this.edited = true;
                            el.addClass('undo');
                            el.removeClass('hidden');
                        } else {
                            this.edited = false;
                            var ar = this.el.parent().select('.tinebase-editmultipledialog-dirty');
                            if(ar.elements.length > 0) {
                                ar.setStyle('display','none');
                            }
                            
                            if (this.multi) {
                                this.setReadOnly(true);
                                this.addClass('tinebase-editmultipledialog-noneedit');
                            }
                            el.removeClass('undo');
                            el.addClass('hidden');
                        }
                        
                    });
                    this.un('focus');
                });
            } else {    // handle Checkboxes
                item.on('render', function() { this.on('check', function() { this.edited = true; })});
            }
        }, this);
    },

    /**
     * is called when the form is submitted. only fieldvalues with edited=true are committed 
     * @return {Boolean}
     */
    onRecordUpdate : function() {
        this.changes = [];
        this.changedHuman = _('<br /><br /><ul>');
        
        Ext.each(this.form.record.store.fields.keys, function(fieldKey) {
            var item = this.form.findField(fieldKey);
            if(!item) return true;
        
            if (item.edited) {
                this.changes.push({name: item.getName(), value: item.getValue()});
                this.changedHuman += '<li><span style="font-weight:bold">' + ((item.fieldLabel) ? item.fieldLabel : item.boxLabel) + ':</span> ';
                this.changedHuman += (item.lastSelectionText) ? item.lastSelectionText : item.getValue();  
                this.changedHuman += '</li>';
            }
        }, this);
        
        this.changedHuman += '</ul>';

        if (this.changes.length == 0) {
            this.editDialog.purgeListeners();
            this.editDialog.window.close();
            return false;
        }

        if(!this.isValid()) {
             
            Ext.MessageBox.alert(_('Errors'), this.getValidationErrorMessage());
            this.form.items.each(function(item){
                if(item.activeError) {
                    if(!item.edited) item.activeError = null;
                }
            });
                    
            return false;
        } else {
            var filter = this.editDialog.selectionFilter;
            Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to change these {0} records?')
            + this.changedHuman, this.editDialog.selectedRecords.length), function(_btn) {
                if (_btn == 'yes') {
                    Ext.MessageBox.wait(_('Please wait'),_('Applying changes'));
                    Ext.Ajax.request({
                        url: 'index.php',
                        timeout: 120000,
                        params: {
                            method: 'Tinebase.updateMultipleRecords',
                            appName: this.editDialog.recordClass.getMeta('appName'),
                            modelName: this.editDialog.recordClass.getMeta('modelName'),
                            changes: this.changes,
                            filter: filter
                        },
                        success: function(_result, _request) {                           
                            Ext.MessageBox.hide();
                            var resp = Ext.decode(_result.responseText);
                            if(resp.failcount > 0) {
                                var window = Tine.widgets.dialog.MultipleEditResultSummary.openWindow({
                                    response: _result.responseText,
                                    appName: this.app.appName,
                                    recordClass: this.editDialog.recordClass
                                });

                                window.on('close', function() {
                                    this.editDialog.fireEvent('update');
                                    this.editDialog.purgeListeners();
                                    this.editDialog.window.close();
                                },this);
                            } else {
                                this.editDialog.fireEvent('update');
                                this.editDialog.purgeListeners();
                                this.editDialog.window.close();
                            }
                        },
                        scope: this
                    });
                }
            }, this);
         }
        return false;
    },
    
    getValidationErrorMessage: function() {
        return _('Please fix the errors noted.');
    },
    
    handleField: function(field, fieldKey, samevalue) {

        if(field.disabled) return true;
        
        if (!samevalue) {
            field.setReadOnly(true);
            field.addClass('tinebase-editmultipledialog-noneedit');
            field.multi = true;
            field.edited = false;
            field.setValue('');
            field.originalValue = '';

            field.on('focus', function() {
                if (this.readOnly) this.originalValue = this.getValue();
                this.setReadOnly(false);
            });
        } else {
            field.on('focus', function() {
                if (!this.edited) this.originalValue = this.getValue();
            });
        }
    }
}
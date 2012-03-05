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
    /**
     * the application calling this plugin
     */
    app : null,
    /**
     * the editDialog the plugin is applied to
     */
    editDialog : null,
    /**
     * the selected records 
     */
    selectedRecords: null,
    /**
     * the selections' filter
     */
    selectionFilter: null,
    /**
     * this record is created on the fly and never saved as is, only changes to this record are sent to the backend
     */
    interRecord: null,   
    /**
     * a shorthand for this.editDialog.getForm() 
     */
    form : null,
    /**
     * Array which holds the fieldConfigs which can be handled by this plugin
     * Array of Objects: { key: <the raw key>, type: <custom/default>', formField: <the corresponding form field>, recordKey: <used for getters/setters of the record>}
     * @type Array
     */
    handleFields: null,
    
    /**
     * initializes the plugin
     */    
    init : function(editDialog) {
        this.interRecord = new editDialog.recordClass({});
        this.editDialog = editDialog;
        this.app = Tine.Tinebase.appMgr.get(this.editDialog.app);
        this.form = this.editDialog.getForm();    
        // load in editDialog means rendered and loaded
        this.editDialog.on('load', this.onAfterRender, this);
        this.handleFields = [];
        this.editDialog.initRecord = Ext.emptyFn;
        
        this.editDialog.onApplyChanges = this.editDialog.onApplyChanges.createInterceptor(this.onRecordUpdate, this); 
        this.editDialog.onRecordUpdate = Ext.emptyFn;
        if (this.editDialog.isMultipleValid) this.editDialog.isValid = this.editDialog.isMultipleValid;       
    },

    /**
     * find out which fields have differences
     */
    onRecordLoad : function() {

        if(!this.editDialog.rendered) {
            this.onRecordLoad.defer(100, this);
            return;
        }
        
        Ext.each(this.handleFields, function(field) {
            var refData = false; 
            
            Ext.each(this.editDialog.selectedRecords, function(recordData, index) {

                var record = this.editDialog.recordProxy.recordReader({responseText: Ext.encode(recordData)});

                // the first record of the selected is the reference
                if(index === 0) {
                    refData = record.get(field.recordKey);
                }

                if ((Ext.encode(record.get(field.recordKey)) != Ext.encode(refData)) || this.editDialog.isFilterSelect) {
                    this.interRecord.set(field.recordKey, '');
                    this.setFieldValue(field, false);
                    return false;
                } else {
                    if (index == this.editDialog.selectedRecords.length - 1) {
                        this.interRecord.set(field.recordKey, refData);
                        this.setFieldValue(field, true);
                        return true;
                    }
                }
            }, this);
        }, this);

        this.interRecord.set('container_id', {account_grants: {editGrant: true}});
        this.interRecord.dirty = false;
        this.interRecord.modified = {};
        
        this.editDialog.window.setTitle(String.format(_('Edit {0} {1}'), this.editDialog.totalRecordCount, this.editDialog.i18nRecordsName));

        Tine.log.debug('loading of the following intermediate record completed:');
        Tine.log.debug(this.interRecord);
        
        this.editDialog.updateToolbars(this.interRecord, this.editDialog.recordClass.getMeta('containerProperty'));

        Ext.each(this.editDialog.tbarItems, function(item) {
            item.setDisabled(true);
            item.multiEditable = false;
            });

        Ext.QuickTips.init();
        
        this.editDialog.loadMask.hide();
        return false;
    },

    /**
     * handle fields for multiedit
     */
    onAfterRender : function() {

        Ext.each(this.editDialog.getDisableOnEditMultiple(), function(item) {
            item.setDisabled(true);
            item.multiEditable = false;
        });

        var keys = [];
        
        Ext.each(this.editDialog.recordClass.getFieldNames(), function(key) {
            var field = this.form.findField(key);
            if (!field) return true;
            keys.push({key: key, type: 'default', formField: field, recordKey: key});
        }, this);
        
        Ext.each(this.editDialog.cfConfigs, function(config) {
            var field = this.form.findField('customfield_' + config.data.name);
            if (!field) return true;
            keys.push({key: config.data.name, type: 'custom', formField: field, recordKey: '#' + config.data.name});
        }, this);

        Ext.each(keys, function(field) {
            var ff = field.formField;
            if ((!(ff.isXType('textfield'))) && (!(ff.isXType('checkbox'))) || ff.multiEditable === false) {
                ff.setDisabled(true);
                this.interRecord.set(field.recordKey, '');
                return true;
            }
            
            this.handleFields.push(field);

                ff.on('focus', function() {

                    if (! ff.isXType('extuxclearabledatefield', true)) {
                    var subLeft = 0;
                    if (ff.isXType('trigger')) subLeft += 17;

                    var el = this.el.parent().select('.tinebase-editmultipledialog-clearer'), 
                        width = this.getWidth(), 
                        left = (width - 18 - subLeft) + 'px';

                    if (el.elements.length > 0) {
                        el.setStyle({left: left});
                        el.removeClass('hidden');
                        return;
                    }

                    // create Button
                    this.multiButton = new Ext.Element(document.createElement('img'));
                    this.multiButton.set({
                        'src': Ext.BLANK_IMAGE_URL,
                        'ext:qtip': _('Delete value from all selected records'),
                        'class': 'tinebase-editmultipledialog-clearer',
                        'style': 'left:' + left
                        });
                    
                    this.multiButton.addClassOnOver('over');
                    this.multiButton.addClassOnClick('click');

                    this.multiButton.on('click', function() {
                        if(this.multiButton.hasClass('undo')) {
                            this.setValue(this.originalValue);
                            if (this.multi) this.cleared = false;
                        } else {
                            this.setValue('');
                            if (this.multi) this.cleared = true;
                        }

                        this.fireEvent('blur', this);

                    }, this);
                    
                    this.el.insertSibling(this.multiButton);
                  }
                    this.on('blur', function(action) {

                        var ar = this.el.parent().select('.tinebase-editmultipledialog-dirty');

                        if ((this.originalValue != this.getValue()) || this.cleared) {  // if edited or cleared
                            // Create or set arrow
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
                            // Set field
                            this.edited = true;
                            
                            this.removeClass('tinebase-editmultipledialog-noneedit');
                            
                            // Set button
                            this.multiButton.addClass('undo');
                            this.multiButton.removeClass('hidden');
                            this.multiButton.set({'ext:qtip': _('Undo change for all selected records')});
                            
                        } else {    // If set back
                            // Set arrow
                            if(ar.elements.length > 0) {
                                ar.setStyle('display','none');
                            }
                            // Set field
                            this.edited = false;
                            if (this.multi) {
                                this.setReadOnly(true);
                                this.addClass('tinebase-editmultipledialog-noneedit');
                            }
                            
                            // Set button
                            this.multiButton.removeClass('undo');
                            this.multiButton.addClass('hidden');
                            this.multiButton.set({'ext:qtip': _('Delete value from all selected records')});
                            
                        }
                    });
                    this.un('focus');
                });

        }, this);
        
        this.onRecordLoad();
        return false;
    },
    
    /**
     * Set field value
     * @param {} Ext.form.Field field
     * @param {} String fieldKey
     * @param {} Boolean samevalue
     */
    setFieldValue: function(field, samevalue) {
        
        var ff = field.formField;
        
        if (! samevalue) {
            ff.setReadOnly(true);
            ff.addClass('tinebase-editmultipledialog-noneedit');
            
            ff.multi = true;
            
            ff.setValue('');
            ff.originalValue = '';
            Ext.QuickTips.register({
                target: ff,
                dismissDelay: 30000,
                title: _('Different Values'),
                text: _('This field has different values. Editing this field will overwrite the old values.'),
                width: 200
            });
            
            if (ff.isXType('checkbox')) {
                ff.on('afterrender', function() {
                    this.getEl().wrap({tag: 'span', 'class': 'tinebase-editmultipledialog-dirtycheck'});
                    this.originalValue = null;
                    this.setValue(false);
                });
                
            } else {
                ff.on('focus', function() {
                    if (this.readOnly) this.originalValue = this.getValue();
                    this.setReadOnly(false);
                });
            }
            
        } else {
            
            if (ff.isXType('checkbox')) {
                ff.originalValue = !! ff.checked;
                ff.setValue(!!ff.checked);
            } else {
                ff.setValue(this.interRecord.get(field.recordKey));
                ff.on('focus', function() {
                    if (!this.edited) this.originalValue = this.getValue();
                });
            }
        }
        
        ff.edited = false;
        
        if (ff.isXType('checkbox')) {
            ff.on('check', function() {
                this.edited = (this.originalValue !== this.getValue());
                });
        }
        
    },
    
    /**
     * is called when the form is submitted. only fieldvalues with edited=true are committed 
     * @return {Boolean}
     */
    onRecordUpdate : function() {
        this.changedHuman = '<br /><br /><ul>';
        var changes = [];
        
        Ext.each(this.handleFields, function(field) {
            var ff = field.formField,
                renderer = Ext.util.Format.htmlEncode;
        
            if (ff.edited === true) {
                var label = ff.fieldLabel ? ff.fieldLabel : ff.boxLabel;
                    label = label ? label : ff.ownerCt.title; 

                changes.push({name: ff.getName(), value: ff.getValue()});
                this.changedHuman += '<li><span style="font-weight:bold">' + label + ':</span> ';
                if(ff.isXType('checkbox')) renderer = Tine.Tinebase.common.booleanRenderer;
                this.changedHuman += ff.lastSelectionText ? renderer(ff.lastSelectionText) : renderer(ff.getValue());  
                this.changedHuman += '</li>';
            }
        }, this);
        
        this.changedHuman += '</ul>';

        if (changes.length == 0) {
            this.editDialog.onCancel();
            return false;
        }

        if(!this.editDialog.isMultipleValid()) {
            Ext.MessageBox.alert(_('Errors'), _('Please fix the errors noted.'));
            Ext.each(this.handleFields, function(item) {
                if(item.activeError) {
                    if(!item.edited) item.activeError = null;
                }
            });
            return false;

        } else {
            var filter = this.editDialog.selectionFilter;
            
            Ext.MessageBox.confirm(
                _('Confirm'),
                String.format(_('Do you really want to change these {0} records?') + this.changedHuman, this.editDialog.totalRecordCount),
                
                function(_btn) {
                if (_btn == 'yes') {
                    Ext.MessageBox.wait(_('Please wait'),_('Applying changes'));
                    Ext.Ajax.request({
                        url: 'index.php',
                        timeout: 3600000, // 1 hour
                        params: {
                            method: 'Tinebase.updateMultipleRecords',
                            appName: this.editDialog.recordClass.getMeta('appName'),
                            modelName: this.editDialog.recordClass.getMeta('modelName'),
                            changes: changes,
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
                                    this.editDialog.onCancel();
                                },this);
                            } else {
                                this.editDialog.fireEvent('update');
                                this.editDialog.onCancel();
                            }
                        },
                        scope: this
                    });
                }
            }, this);
         }
        return false;
    }
};

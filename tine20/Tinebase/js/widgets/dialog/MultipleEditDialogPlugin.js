/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.editDialog');

/**
 * @namespace   Tine.widgets.editDialog
 * @class       Tine.widgets.dialog.MultipleEditDialogPlugin
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
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
     * if records are defined by a filterselection
     * @type 
     */
    isFilterSelect: null,
    
    /**
     * count of all handled records
     * @type 
     */
    totalRecordCount: null,
    
    /**
     * component registry to skip on multiple edit
     * @type 
     */
    skipItems: [],
    
    /**
     * initializes the plugin
     */    
    init : function(ed) {
        ed.mode = 'local';
        ed.evalGrants = false;
        ed.onRecordLoad = ed.onRecordLoad.createSequence(this.onAfterRender, this);
        ed.onApplyChanges = ed.onApplyChanges.createInterceptor(this.onRecordUpdate, this);
        ed.onRecordUpdate = Ext.emptyFn;
        ed.initRecord = Ext.emptyFn;
        ed.useMultiple = true;
        
        ed.on('load', function(editDialog, record, ticketFn) {
            this.loadInterceptor = ticketFn();
        }, this);
        
        this.app = Tine.Tinebase.appMgr.get(ed.app);
        this.form = ed.getForm();
        this.handleFields = [];
        this.interRecord = new ed.recordClass({});

        if (ed.hasOwnProperty('isMultipleValid') && Ext.isFunction(ed.isMultipleValid)) {
            ed.isValid = ed.isMultipleValid;
        } else {
            ed.isValid = function() {return true};
        }

        this.editDialog = ed;
    },
    /**
     * method to register components which are disabled on multiple edit
     * call this from the component to disable by: Tine.widgets.dialog.MultipleEditDialogPlugin.prototype.registerSkipItem(this);
     * @param {} item
     */
    registerSkipItem: function(item) {
        this.skipItems.push(item);
    },
    
    /**
     * waits until the dialog is rendered and fetches real records by the filter on filter selection
     */
    onRecordLoad : function() {
        // fetch records from server on filterselection to get the exact difference
        if (this.isFilterSelect && this.selectionFilter) {
            this.fetchRecordsOnLoad();
        } else {
            var records = [];
            Ext.each(this.selectedRecords, function(recordData, index) {
                records.push(this.editDialog.recordProxy.recordReader({responseText: Ext.encode(recordData)}));
            }, this);
            this.onRecordPrepare(records);
        }
    },
    
    /**
     * find out which fields have differences
     */
    onRecordPrepare: function(records) {
        Ext.each(this.handleFields, function(field) {
            var refData = false;
            
            Ext.each(records, function(record, index) {
                if(field.type == 'relation') {
                    this.setFieldValue(field, false);
                } else {
                    // the first record of the selected is the reference
                    if(index === 0) {
                        refData = record.get(field.recordKey);
                    }

                    if ((Ext.encode(record.get(field.recordKey)) != Ext.encode(refData))) {
                        this.interRecord.set(field.recordKey, '');
                        this.setFieldValue(field, false);
                        return false;
                    } else {
                        if (index == records.length - 1) {
                            this.interRecord.set(field.recordKey, refData);
                            this.setFieldValue(field, true);
                            return true;
                        }
                    }
                }
            }, this);
        }, this);
        
        // TODO: grantsProperty not handled here, not needed at the moment but sometimes, perhaps.
        var cp = this.editDialog.recordClass.getMeta('containerProperty') ? this.editDialog.recordClass.getMeta('containerProperty') : 'container_id';
        if(this.interRecord.get(cp) !== undefined) {
            this.interRecord.set(cp, {account_grants: {editGrant: true}});
        }
        this.interRecord.dirty = false;
        this.interRecord.modified = {};
        
        this.editDialog.window.setTitle(String.format(_('Edit {0} {1}'), this.totalRecordCount, this.editDialog.i18nRecordsName));

        Tine.log.debug('loading of the following intermediate record completed:');
        Tine.log.debug(this.interRecord);
        
        this.editDialog.updateToolbars(this.interRecord, this.editDialog.recordClass.getMeta('containerProperty'));
        if(this.editDialog.tbarItems) {
            Ext.each(this.editDialog.tbarItems, function(item) {
                if(Ext.isFunction(item.setDisabled)) item.setDisabled(true);
                item.multiEditable = false;
            });
        }
        
        this.loadInterceptor();
        return false;
    },

    /**
     * handle fields for multiedit
     */
    onAfterRender : function() {
        Ext.each(this.skipItems, function(item) {
            item.setDisabled(true);
        }, this);

        var keys = [];
        
        // disable container selector
        var field = this.form.findField(this.editDialog.recordClass.getMeta('containerProperty'));
        if(field) {
            field.disable();
        }
        
        Ext.each(this.editDialog.recordClass.getFieldNames(), function(key) {
            var field = this.form.findField(key);
            if (!field) return true;
            keys.push({key: key, type: 'default', formField: field, recordKey: key});
        }, this);
        
        var cfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, this.editDialog.recordClass);
        if(cfConfigs) {
            Ext.each(cfConfigs, function(config) {
                var field = this.form.findField('customfield_' + config.data.name);
                if (!field) return true;
                keys.push({key: config.data.name, type: 'custom', formField: field, recordKey: '#' + config.data.name});
            }, this);
        }
        
        if(this.editDialog.relationPickers) {
            Ext.each(this.editDialog.relationPickers, function(picker){
                keys.push({key: picker.relationType + '-' + picker.fullModelName, type: 'relation', formField: picker.combo, recordKey: '%' + picker.relationType + '-' + picker.fullModelName});
            });
        }
        
        Ext.each(keys, function(field) {
            var ff = field.formField;
            if ((!(ff.isXType('textfield'))) && (!(ff.isXType('checkbox'))) && (!(ff.isXType('datetimefield'))) || ff.multiEditable === false) {
                ff.setDisabled(true);
                this.interRecord.set(field.recordKey, '');
                return true;
            }
            
            this.handleFields.push(field);

            ff.on('focus', function() {
                var subLeft = 18;
                
                if(ff.isXType('tinerecordpickercombobox')) {
                    ff.disableClearer = true;
                    subLeft += 19;
                } else if (ff.isXType('trigger') || ff.isXType('datetimefield')) {
                    subLeft += 17; 
                }
                var el = this.el.parent().select('.tinebase-editmultipledialog-clearer'), 
                    width = this.getWidth(), 
                    left = (width - subLeft) + 'px';

                if (el.elements.length > 0) {
                    el.setStyle({left: left});
                    el.removeClass('hidden');
                    return;
                }

                // create Button
                this.multiButton = new Ext.Element(document.createElement('img'));
                this.multiButton.set({
                    'src': Ext.BLANK_IMAGE_URL,
                    'ext:qtip': Ext.util.Format.htmlEncode(_('Delete value from all selected records')),
                    'class': 'tinebase-editmultipledialog-clearer',
                    'style': 'left:' + left
                    });
                
                this.multiButton.addClassOnOver('over');
                this.multiButton.addClassOnClick('click');

                this.multiButton.on('click', function() {
                    if(this.multiButton.hasClass('undo')) {
                        this.setValue(this.originalValue);
                        this.clearInvalid();
                        if (this.multi) this.cleared = false;
                    } else {
                        this.setValue('');
                        if (this.multi) {
                            this.cleared = true;
                            this.allowBlank = this.origAllowBlank;
                            this.markInvalid();
                        }
                    }

                    this.fireEvent('blur', this);

                }, this);
                
                this.el.insertSibling(this.multiButton);
                
                this.on('blur', function(action) {

                    var ar = this.el.parent().select('.tinebase-editmultipledialog-dirty');
                    
                    var currentValue = this.getValue();
                    var originalValue = this.originalValue;

                    if ((Ext.encode(originalValue) != Ext.encode(currentValue)) || (this.cleared === true)) {  // if edited or cleared
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
                        this.multiButton.set({'ext:qtip': Ext.util.Format.htmlEncode(_('Undo change for all selected records'))});
                        
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
                        this.multiButton.set({'ext:qtip': Ext.util.Format.htmlEncode(_('Delete value from all selected records'))});
                    }
                });
                this.un('focus');
            });

        }, this);
        
        this.editDialog.record = this.interRecord;
        this.onRecordLoad();
    },
    
    /**
     * Set field value
     * @param {} Ext.form.Field field
     * @param {} String fieldKey
     * @param {} Boolean samevalue
     */
    setFieldValue: function(field, samevalue) {
        
        var ff = field.formField;
        
        if (! samevalue) {  // The records does not have the same value on this field
            ff.setReadOnly(true);
            ff.addClass('tinebase-editmultipledialog-noneedit');
            ff.origAllowBlank = ff.allowBlank;
            ff.allowBlank = true;
            ff.multi = true;
            
            ff.setValue('');
            ff.originalValue = '';
            ff.clearInvalid();
            Ext.QuickTips.register({
                target: ff,
                dismissDelay: 30000,
                title: _('Different Values'),
                text: _('This field has different values. Editing this field will overwrite the old values.'),
                width: 200
            });
            
            if (ff.isXType('checkbox')) {
                this.wrapCheckbox(ff);
            } else {
                ff.on('focus', function() {
                    if (this.readOnly) this.originalValue = this.getValue();
                    this.setReadOnly(false);
                });
            }            
        } else { // All records has the same value on this field
            if (ff.isXType('checkbox')) {
                ff.originalValue = this.interRecord.get(ff.name);
                ff.setValue(this.interRecord.get(ff.name));
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
     * Wraps the checkbox with dirty colored span
     * @param {Ext.form.Field} checkbox
     */
    wrapCheckbox: function(checkbox) {
        if(checkbox.rendered !== true) {
            this.wrapCheckbox.defer(100, this, [checkbox]);
            return;
        }
        checkbox.getEl().wrap({tag: 'span', 'class': 'tinebase-editmultipledialog-dirtycheck'});
        checkbox.originalValue = null;
        checkbox.setValue(false);
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
                var label = ff.fieldLabel ? ff.fieldLabel : ff.boxLabel ? ff.boxLabel : ff.ownerCt.fieldLabel;
                    label = label ? label : ff.ownerCt.title;

                changes.push({name: (field.type == 'relation') ? field.recordKey : ff.getName(), value: ff.getValue()});
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
            var filter = this.selectionFilter;
            
            Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to change these {0} records?') + this.changedHuman, this.editDialog.totalRecordCount),                
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
    },
    
    /**
     * fetch records from backend
     */
    fetchRecordsOnLoad: function() {
        Tine.log.debug('Fetching additional records...');
        this.editDialog.recordProxy.searchRecords(this.selectionFilter, null, {
            scope: this,
            success: function(result) {
                this.onRecordPrepare.call(this, result.records);
            }
        });
    }
};

Ext.ComponentMgr.registerPlugin('multiple_edit_dialog', Tine.widgets.dialog.MultipleEditDialogPlugin);


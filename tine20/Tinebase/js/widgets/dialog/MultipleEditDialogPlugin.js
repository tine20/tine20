/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * holds the fields which have been changed
     * @type {array} changedFields
     */
    changedFields: null,
    
    /**
     * count of all handled records
     * @type {Integer} totalRecordCount
     */
    totalRecordCount: null,
    
    /**
     * component registry to skip on multiple edit
     * @type {Array} skipItems
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
        ed.interRecord = new ed.recordClass({});
        ed.loadRecord = true;
        
        ed.on('load', function(editDialog, record, ticketFn) {
            this.loadInterceptor = ticketFn();
        }, this);
        
        this.app = Tine.Tinebase.appMgr.get(ed.app);
        this.form = ed.getForm();
        this.handleFields = [];
        this.interRecord = ed.interRecord;
        
        if (ed.action_saveAndClose) {
            ed.action_saveAndClose.disable();
        }
        ed.on('fieldchange', this.findChangedFields, this);
        
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
     * handle fields for multiedit
     */
    onAfterRender : function() {
        // skip registered form items
        Ext.each(this.skipItems, function(item) {
            item.setDisabled(true);
        }, this);

        var keys = [];
        
        // disable container selector - just container_id
        var field = this.form.findField('container_id');
        if (field) {
            field.disable();
        }
        
        // get fields to handle
        Ext.each(this.editDialog.recordClass.getFieldNames(), function(key) {
            var field = this.form.findField(key);
            if (!field) {
                Tine.log.info('No field found for property "' + key + '". Ignoring...');
                return true;
            }
            Tine.log.info('Field found for property "' + key + '".');
            keys.push({key: key, type: 'default', formField: field, recordKey: key});
        }, this);
        
        // get customfields to handle
        var cfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, this.editDialog.recordClass);
        if (cfConfigs) {
            Ext.each(cfConfigs, function (config) {
                var field = this.form.findField('customfield_' + config.data.name);
                if (!field) {
                    Tine.log.info('No customfield found for property "' + config.data.name + '". Ignoring...');
                    return true;
                }
                Tine.log.info('Customfield found for property "' + config.data.name + '".');
                keys.push({key: config.data.name, type: 'custom', formField: field, recordKey: '#' + config.data.name});
            }, this);
        }
        
        // get relationpickerfields to handle
        if (this.editDialog.relationPickers) {
            Ext.each(this.editDialog.relationPickers, function(picker) {
                Tine.log.info('RelationPicker found. Using "' + '%' + picker.relationType + '-' + picker.fullModelName + '" as key.');
                keys.push({key: picker.relationType + '-' + picker.fullModelName, type: 'relation', formField: picker.combo, recordKey: '%' + picker.relationType + '-' + picker.fullModelName});
            });
        }

        Ext.each(keys, function(field) {
            var ff = field.formField;
            // disable fields which cannot be handled atm.
            if ((!(ff.isXType('textfield'))) && (!(ff.isXType('checkbox'))) && (!(ff.isXType('datetimefield'))) || ff.multiEditable === false) {
                Tine.log.debug('Disabling field for key "' + field.recordKey + '". Cannot be handled atm.');
                ff.setDisabled(true);
                this.interRecord.set(field.recordKey, '');
                return true;
            }
            // remove empty text
            if (ff.hasOwnProperty('emptyText')) {
                ff.emptyText = '';
            }
            
            // default event for init
            ff.startEvents = ['focus'];
            // default trigger event
            ff.triggerEvents = ['blur'];
            
            // special events for special field types
            if (ff.isXType('tinedurationspinner')) {
                ff.emptyOnZero = true;
                ff.startEvents = ['focus', 'spin'];
                ff.triggerEvents = ['spin', 'blur'];
            } else if (ff.isXType('tinerecordpickercombobox')) {
                ff.startEvents = ['focus', 'expand'];
                ff.triggerEvents = ['select', 'blur'];
            }
            // add field to handlefields array
            this.handleFields.push(field);
            
            Ext.each(ff.startEvents, function(initEvent) {
                ff.on(initEvent, this.onInitField, this, [ff]);
            }, this);

        }, this);
        
        this.editDialog.record = this.interRecord;
        this.onRecordLoad();
    },
    
    /**
     * is called when an init handler for this field is called
     * @param {Object} ff the form field
     */
    onInitField: function(ff) {
        Tine.log.info('Init handler called for field "' + ff.name + '".');
        
        // if already initialized, dont't repeat setting start values and inserting button
        if (! ff.multipleInitialized) {
            if (! ff.isXType('checkbox')) {
                this.createMultiButton(ff);
            }
            
            var startValue = this.interRecord.get(ff.name);
            
            if (ff.isXType('addressbookcontactpicker') && ff.useAccountRecord) {
                ff.startRecord = startValue ? startValue : null;
                startValue = startValue['accountId'] ? startValue['accountId'] : null;
            } else if (ff.isXType('timefield')) {
                if (startValue.length) {
                    startValue = startValue.replace(/\d{4}-\d{2}-\d{2}\s/, '').replace(/:\d{2}$/, '');
                }
            } else if (ff.isXType('trigger') && ff.triggers) {
                ff.on('blur', this.hideTriggerClearer);
                ff.on('focus', this.hideTriggerClearer);
                ff.on('select', this.hideTriggerClearer);
            } else if (Ext.isObject(startValue)) {
                startValue = startValue[ff.recordClass.getMeta('idProperty')];
                ff.startRecord = new ff.recordClass(startValue);
            }
            ff.startingValue = (startValue == undefined || startValue == null) ? '' : startValue;
            
            Tine.log.info('Setting start value to "' + startValue + '".');
            
            Ext.each(ff.triggerEvents, function(triggerEvent) {
                ff.on(triggerEvent, this.onTriggerField, ff);
                ff.on('fieldchange', function() {
                    this.editDialog.fireEvent('fieldchange');
                }, this);
            }, this);
    
            ff.multipleInitialized = true;

            if (ff.isXType('tinedurationspinner')) {
                ff.fireEvent(ff.triggerEvents[1]);
            }
            
        } else {
            if (ff.multiButton) {
                ff.multiButton.removeClass('hidden');
            }
        }
    },
    
    /**
     * hides the default clearer
     */
    hideTriggerClearer: function() {
        this.triggers[0].hide();
    },
    /**
     * creates the multibutton (button to reset or clear the field value)
     * @param {Ext.form.field} formField
     */
    createMultiButton: function(formField) {
        var subLeft = 18;
        if (formField.isXType('tinerecordpickercombobox')) {
            formField.disableClearer = true;
            subLeft += 19;
        } else if (formField.isXType('extuxclearabledatefield')) {
            formField.disableClearer = true;
            if (!formField.multi) {
                subLeft += 17;
            }
        } else if (formField.isXType('tine.widget.field.AutoCompleteField')) {
            // is trigger, but without button, so do nothing
        } else if (formField.isXType('trigger')) {
            if (! formField.hideTrigger) {
                subLeft += 17;
            }
        } else if (formField.isXType('datetimefield')) {
            subLeft += 17; 
        }
        var el = formField.el.parent().select('.tinebase-editmultipledialog-clearer'), 
            width = formField.getWidth(), 
            left = (width - subLeft) + 'px';
            
        formField.startLeft = (width - subLeft);

        if (el.elements.length > 0) {
            el.setStyle({left: left});
            el.removeClass('hidden');
            return;
        }
        
        // create Button
        formField.multiButton = new Ext.Element(document.createElement('img'));
        formField.multiButton.set({
            'src': Ext.BLANK_IMAGE_URL,
            'ext:qtip': Ext.util.Format.htmlEncode(_('Delete value from all selected records')),
            'class': 'tinebase-editmultipledialog-clearer',
            'style': 'left:' + left
            });
        
        formField.multiButton.addClassOnOver('over');
        formField.multiButton.addClassOnClick('click');

        // handles the reset/restore button
        formField.multiButton.on('click', this.onMultiButton, formField);
        formField.el.insertSibling(formField.multiButton);
    },
    
    /**
     * is called if the multibutton of "this" field is triggered
     */
    onMultiButton: function() {
        Tine.log.debug('Multibutton called.');
        // scope: formField
        if (this.multiButton.hasClass('undo')) {
            Tine.log.debug('Resetting value to "' + this.startingValue + '".');
            if (this.startRecord) {
                this.store.removeAll();
                this.setValue(this.startRecord);
                this.value = this.startingValue;
            } else {
                this.setValue(this.startingValue);
            }
            this.clearInvalid();
            
            if (this.isXType('extuxclearabledatefield') && this.multi) {
                var startLeft = this.startLeft ? this.startLeft : 0;
                var parent = this.el.parent().select('.tinebase-editmultipledialog-clearer');
                parent.setStyle('left', startLeft + 'px');
            }
            if (this.multi) {
                this.cleared = false;
            }
        } else {
            Tine.log.debug('Clearing value.');
            if (this.isXType('extuxclearabledatefield') && this.multi) {
                var startLeft = this.startLeft ? this.startLeft : 0;
                startLeft -= 17;
                var parent = this.el.parent().select('.tinebase-editmultipledialog-clearer');
                parent.setStyle('left', startLeft + 'px');
            }
            this.setValue('');
            if (this.multi) {
                this.cleared = true;
                this.allowBlank = this.origAllowBlank;
            }
        }
        // trigger event
        this.fireEvent(this.triggerEvents[0], this);
    },
    
    /*
     * is called when a trigger event is fired
     */
    onTriggerField: function() {
        // scope on formField
        Tine.log.info('Trigger handler called for field "' + this.name + '".');
        var ar = this.el.parent().select('.tinebase-editmultipledialog-dirty');
        var originalValue = this.hasOwnProperty('startingValue') ? String(this.startingValue) : String(this.originalValue),
            currentValue;
        
        if (this.isXType('datefield')) {
            currentValue = this.fullDateTime ? this.fullDateTime.format('Y-m-d H:i:s') : '';
        } else if (this.isXType('timefield')) {
            currentValue = this.fullDateTime;
        } else {
            currentValue = String(this.getValue());
        }
        
        Tine.log.info('Start value: "' + originalValue + '", current: "' + currentValue + '"');
        if ((Ext.encode(originalValue) != Ext.encode(currentValue)) || (this.cleared === true)) {  // if edited or cleared
            // Create or set arrow
            if (ar.elements.length > 0) {
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
            this.setReadOnly(false);
            
            this.removeClass('tinebase-editmultipledialog-noneedit');
            
            // Set button
            this.multiButton.addClass('undo');
            this.multiButton.removeClass('hidden');
            this.multiButton.set({'ext:qtip': Ext.util.Format.htmlEncode(_('Undo change for all selected records'))});
            
        } else {    // If set back
            // Set arrow
            if (ar.elements.length > 0) {
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
        this.fireEvent('fieldchange');
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
                records.push(new this.editDialog.recordClass(recordData));
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
                if (field.type == 'relation') {
                    this.setFieldValue(field, false);
                } else {
                    // the first record of the selected is the reference
                    if (index === 0) {
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
//        var cp = this.editDialog.recordClass.getMeta('containerProperty') ? this.editDialog.recordClass.getMeta('containerProperty') : 'container_id';
//        if (this.interRecord.get(cp) !== undefined) {
//            this.interRecord.set(cp, {account_grants: {editGrant: true}});
//        }
        this.interRecord.dirty = false;
        this.interRecord.modified = {};
        
        this.editDialog.window.setTitle(String.format(_('Edit {0} {1}'), this.totalRecordCount, this.editDialog.i18nRecordsName));

        Tine.log.debug('loading of the following intermediate record completed:');
        Tine.log.debug(this.interRecord);
        
        this.editDialog.updateToolbars(this.interRecord, this.editDialog.recordClass.getMeta('containerProperty'));
        if (this.editDialog.tbarItems) {
            Ext.each(this.editDialog.tbarItems, function(item) {
                if (Ext.isFunction(item.setDisabled)) item.setDisabled(true);
                item.multiEditable = false;
            });
        }
        
        this.loadInterceptor();
        
        // some field sanitizing
        Ext.each(this.handleFields, function(field) {
            // handle TimeFields to set original value (not possible before)
            if (field.formField.isXType('timefield')) {
                var value = this.interRecord.get(field.key);
                if (value) {
                    field.formField.setValue(new Date(value));
                }
            }
        }, this);
        
        return false;
    },

    
    /**
     * Set field value
     * @param {Ext.form.Field} field
     * @param {Boolean} samevalue true, if value is the same of all records
     */
    setFieldValue: function(field, samevalue) {
        var ff = field.formField;
        
        ff.removeClass('x-form-empty-field');
        
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
            } 
        } else { // All records have the same value on this field
            if (ff.isXType('checkbox')) {
                ff.originalValue = this.interRecord.get(ff.name);
                ff.setValue(this.interRecord.get(ff.name));
            } else if (ff.isXType('tinerecordpickercombobox')) {
                var val = this.interRecord.get(field.recordKey);
                if (val) {
                    if (!ff.isXType('addressbookcontactpicker')) {
                        ff.startRecord = new ff.recordClass(val);
                    } else {
                        ff.startRecord = val;
                    }
                }
            } else {
                ff.setValue(this.interRecord.get(field.recordKey));
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
        if (checkbox.rendered !== true) {
            this.wrapCheckbox.defer(100, this, [checkbox]);
            return;
        }
        checkbox.getEl().wrap({tag: 'span', 'class': 'tinebase-editmultipledialog-dirtycheck'});
        checkbox.originalValue = null;
        checkbox.setValue(false);
    },
    
    findChangedFields: function() {
        this.changedFields = [];
        Ext.each(this.handleFields, function(field) {
            if (field.formField.edited === true) {
                this.changedFields.push(field);
            }
        }, this);
        
        if (this.editDialog.action_saveAndClose) {
            this.editDialog.action_saveAndClose.setDisabled(! this.changedFields.length);
        }
    },
    
    /**
     * is called when the form is submitted. only fieldvalues with edited=true are committed 
     * @return {Boolean}
     */
    onRecordUpdate: function() {
        if (!this.editDialog.isMultipleValid()) {
            Ext.MessageBox.alert(_('Errors'), _('Please fix the errors noted.'));
            Ext.each(this.handleFields, function(item) {
                if (item.activeError) {
                    if (!item.edited) item.activeError = null;
                }
            });
            return false;
        }
        
        
        this.changedHuman = '<br /><br /><ul>';
        var changes = [];
        
        Ext.each(this.changedFields, function(field) {
            var ff = field.formField,
                renderer = Ext.util.Format.htmlEncode;
                
            var label = ff.fieldLabel ? ff.fieldLabel : ff.boxLabel ? ff.boxLabel : ff.ownerCt.fieldLabel;
                    label = label ? label : ff.ownerCt.title;
                    
            changes.push({name: (field.type == 'relation') ? field.recordKey : ff.getName(), value: ff.getValue()});
                    
            this.changedHuman += '<li><span style="font-weight:bold">' + label + ':</span> ';
            if (ff.isXType('checkbox')) {
                    renderer = Tine.Tinebase.common.booleanRenderer;
                } else if (ff.isXType('tinedurationspinner')) {
                    renderer = Tine.Tinebase.common.minutesRenderer;
                }
                
                this.changedHuman += ff.lastSelectionText ? renderer(ff.lastSelectionText) : renderer(ff.getValue());
                this.changedHuman += '</li>';
        }, this);
        this.changedHuman += '</ul>';
        var filter = this.selectionFilter;
        
        Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to change these {0} records?') + this.changedHuman, this.totalRecordCount),
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
                        if (resp.failcount > 0) {
                            var window = Tine.widgets.dialog.MultipleEditResultSummary.openWindow({
                                response: _result.responseText,
                                appName: this.app.appName,
                                recordClass: this.editDialog.recordClass
                            });
                            window.on('close', function() {
                                this.editDialog.fireEvent('update');
                                this.editDialog.onCancel();
                            }, this);
                        } else {
                            this.editDialog.fireEvent('update');
                            this.editDialog.onCancel();
                        }
                    },
                    scope: this
                });
            }
        }, this);
         return false;
    },
    
    /**
     * fetch records from backend on selectionFilter
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


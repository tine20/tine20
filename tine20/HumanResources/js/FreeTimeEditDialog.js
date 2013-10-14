/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.FreeTimeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>FreeTime Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * Create a new Tine.HumanResources.FreeTimeEditDialog
 */
Tine.HumanResources.FreeTimeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    mode: 'local',
    loadRecord: false,
    /**
     * show private Information (autoset due to rights)
     * @type 
     */
    showPrivateInformation: null,
    
    freetimeType: null,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: Ext.emptyFn,
    
    /**
     * inits the component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources')
        this.showPrivateInformation = (Tine.Tinebase.common.hasRight('edit_private','HumanResources')) ? true : false;
        this.initDatePicker();
        this.mode = 'local';
        
        Tine.HumanResources.FreeTimeEditDialog.superclass.initComponent.call(this);
    },
    
    isValid: function() {
        return Tine.HumanResources.FreeTimeEditDialog.superclass.isValid.call(this);
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        if (Ext.isString(this.record)) {
            this.record = this.recordProxy.recordReader({responseText: this.record});
        }
        this.record.set('employee_id', this.fixedFields.get('employee_id'));
        
        this.accountBox.onRecordLoad(this.record);
        this.datePicker.onRecordLoad(this.record, this.fixedFields.get('employee_id').id);
        
        if (this.record.get('employee_id')) {
            this.window.setTitle(String.format(_('Edit {0} "{1}"'), this.i18nRecordName, this.record.getTitle()));
        }
        
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordLoad.call(this);
        
        var typeString = this.freetimeType == 'SICKNESS' ? 'Sickness Days' : 'Vacation Days';
        
        if (this.record.id) {
            this.accountBox.disable();
            this.window.setTitle(String.format(this.app.i18n._('Edit {0} for {1}'), this.app.i18n._hidden(typeString), this.record.get('employee_id').n_fn));
        } else {
            this.window.setTitle(String.format(this.app.i18n._('Add {0} for {1}'), this.app.i18n._hidden(typeString), this.record.get('employee_id').n_fn));
        }
    },
    
    onApplyChanges: function(closeWindow) {
        // if no day is selected, show message and break saving
        if (! this.datePicker.store.getFirstDay()) {
            var msg = this.freetimeType == 'SICKNESS' ? this.app.i18n._('You have to select at least one day to save this sickness entry.') : this.app.i18n._('You have to select at least one day to save this vacation entry.')
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING,
                title: this.app.i18n._('No day selected'), 
                msg: msg
            });
            
            return false;
        } else {
            Tine.HumanResources.FreeTimeEditDialog.superclass.onApplyChanges.call(this, closeWindow);
        }
    },
    
    /**
     * executed when record gets updated from form
     * @private
     */
    onRecordUpdate: function() {
        var firstDay = this.datePicker.store.getFirstDay();
        
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordUpdate.call(this);
        this.record.set('freedays', this.datePicker.getData());
        this.record.set('type', this.fixedFields.get('type').toLowerCase());
        if (firstDay) {
            this.record.set('firstday_date', new Date(firstDay.get('date')));
        }
    },
    
    /**
     * creates the date picker
     */
    initDatePicker: function() {
        this.datePicker = new Tine.HumanResources.DatePicker({
            recordClass: Tine.HumanResources.Model.FreeDay,
            app: this.app,
            editDialog: this,
            dateProperty: 'date',
            recordsProperty: 'freedays',
            foreignIdProperty: 'freeday_id',
            freetimeType: this.freetimeType
        });
    },
    
    /**
     * validates day length
     * 
     * @param {Float/Integer} value
     * @return {Boolean}
     */
    isDayLengthValid: function(value) {
        return (value <= 1 && value >=0.25);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        this.freetimeType = this.fixedFields.get('type');
        var statusBoxDefaults = {
            fieldLabel: this.app.i18n._('Status'),
            xtype: 'widget-keyfieldcombo',
            app: 'HumanResources',
            name: 'status'
        };
        
        this.statusBox = this.freetimeType == 'SICKNESS' ? 
                Ext.apply({
                    keyFieldName: 'sicknessStatus',
                    value: 'EXCUSED',
                    columnWidth: 1
                }, statusBoxDefaults)
            :   Ext.apply({
                    keyFieldName: 'vacationStatus',
                    value: 'REQUESTED',
                    columnWidth: 2/3
                }, statusBoxDefaults);
        
        var year = new Date();
        thisYear = parseInt(year.format('Y'));
        year = thisYear - 2;
        
        this.accountBox = Tine.widgets.form.RecordPickerManager.get('HumanResources', 'Account', {
            name: 'account_id',
            additionalFilters: [
                {field: 'employee_id', operator: 'AND', value: [
                    { field: ':id', operator: 'equals', value: this.fixedFields.get('employee_id')}
                ]},
                {field: 'year', operator: 'greater', 'value': year }
            ],

            fieldLabel: this.app.i18n._('Year'),
            onRecordLoad: function(record) {
                if (record.get('account_id')) {
                     this.setValue(record.get('account_id'));
                } else {
                    var addFilters = [{field: 'employee_id', operator: 'AND', value: [
                        { field: ':id', operator: 'equals', value: record.get('employee_id')}
                    ]}, {field: 'year', operator:'equals', value: thisYear}];
                    
                    var request = Ext.Ajax.request({
                        url : 'index.php',
                        params : { method : 'HumanResources.searchAccounts', filter: addFilters},
                        success : function(_result, _request) {
                            this.onAccountsLoad(Ext.decode(_result.responseText));
                        },
                        failure : function(exception) {
                            Tine.HumanResources.handleRequestException(exception);
                        },
                        scope: this
                    });
                }
            },
            onAccountsLoad: function(result) {
                this.setValue(thisYear);
            }
        });
        // update calendar if year changes  
        this.accountBox.on('select', function(combo, record, index){
            this.datePicker.loadFeastDays(null, this.fixedFields.get('employee_id').id);
        }, this);
        
        
        var firstRow = [this.statusBox, this.accountBox];
        var freeTimeTypeName = 'Sickness Days';
        if (this.freetimeType == 'VACATION') {
            firstRow.push({columnWidth: 1/3, name: 'remaining_vacation_days', readOnly: true, fieldLabel: this.app.i18n._('Remaining')});
            freeTimeTypeName = 'Vacation Days';
        }

        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            defaults: {
                hideMode: 'offsets'
            },
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n._(freeTimeTypeName),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        autoHeight: true,
                        title: this.app.i18n._(freeTimeTypeName),
                        items: [{
                            xtype: 'columnform',
                            style: { 'float': 'left', width: '50%', 'min-width': '178px' },
                            labelAlign: 'top',
                            formDefaults: {
                                xtype:'textfield',
                                anchor: '100%',
                                labelSeparator: '',
                                allowBlank: false,
                                columnWidth: 1
                            },
                            items: [
                                firstRow,
                                [{
                                        xtype: 'panel',
                                        cls: 'HumanResources x-form-item',
                                        width: 220,
                                        style: {
                                            'float': 'right',
                                            margin: '0 5px 10px 0'
                                        },
                                        items: [{html: '<label style="display:block; margin-bottom: 5px">' + this.app.i18n._('Select Days') + '</label>'}, this.datePicker]
                                    }]
                                ]
                        }]
                    }]
                }, {
                    // activities and tags
                    layout: 'accordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Ext.Panel({
                            title: this.app.i18n._('Description'),
                            iconCls: 'descriptionIcon',
                            layout: 'form',
                            labelAlign: 'top',
                            border: false,
                            items: [{
                                style: 'margin-top: -4px; border 0px;',
                                labelSeparator: '',
                                xtype: 'textarea',
                                name: 'description',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars: false,
                                anchor: '100% 100%',
                                emptyText: _('Enter description'),
                                requiredGrant: 'editGrant'
                            }]
                        })
                    ]
                }]
            }]
        };
    }
});
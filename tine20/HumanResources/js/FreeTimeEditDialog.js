/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
    
    /**
     * show private Information (autoset due to rights)
     * 
     * @type {Boolean}
     */
    showPrivateInformation: null,
    
    /**
     * holds cached getFeastAndFreeDaysQueries
     * 
     * @type {Array}
     */
    calculatedFeastDays: null,
    
    /**
     * either SICKNESS or VACATION
     * 
     * @type {String}
     */
    freetimeType: null,
    
    /**
     * the datepicker holds a calendar to select the dates of vacation or sickness
     * 
     * @type {Tine.HumanResources.DatePicker}
     */
    datePicker: null,
    
    /**
     * the account picker holds the account the (vacation-)days are taken from
     * @type {Tine.Tinebase.widgets.form.RecordPickerComboBox}
     */
    accountPicker: null,
    
    /**
     * two years ago for the accountPicker search
     * 
     * @type {Number}
     */
    twoYearsAgo: null,
    
    /**
     * year on init time
     * 
     * @type {Number}
     */
    startYear: null,
    
    /**
     * holds the current account (by the startYear, if no account is given by the record)
     * 
     * @type {Tine.HumanResources.Model.Account}
     */
    currentAccount: null,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: Ext.emptyFn,
    
    /**
     * holds vacation day records already inserted in the employee by account_id
     * 
     * @type {Object}
     */
    localVacationDays: null,
    
    /**
     * holds already removed sickness days which are not persisted
     * 
     * @type {Object}
     */
    removedSicknessDays: null,
    
    /**
     * holds already removed vacation days which are not persisted
     * 
     * @type {Object}
     */
    removedVacationDays: null,
    
    /**
     * holds sickness day records already inserted in the employee by account_id
     * 
     * @type {Object}
     */
    localSicknessDays: null,
    
    /**
     * inits the component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources');
        this.showPrivateInformation = (Tine.Tinebase.common.hasRight('edit_private','HumanResources')) ? true : false;
        
        this.calculatedFeastDays = [];
        this.localVacationDays = {};
        this.localSicknessDays = {};
        
        // calculate current year and two years ago for the accountPicker search
        var date = new Date();
        this.startYear = parseInt(date.format('Y'));
        this.twoYearsAgo = this.startYear - 2;
        
        Tine.HumanResources.FreeTimeEditDialog.superclass.initComponent.call(this);
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
        
        if (this.record.get('employee_id')) {
            this.window.setTitle(String.format(i18n._('Edit {0} "{1}"'), this.i18nRecordName, this.record.getTitle()));
        }
        
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordLoad.call(this);
        
        if (this.accountPicker) {
            this.accountPicker.onRecordLoad(this.record);
        }
        // set title after record load to determine if it's an update or new record
        var typeString = this.freetimeType == 'SICKNESS' ? 'Sickness Days' : 'Vacation Days';
        if (this.record.id) {
            if (this.accountPicker) {
                this.accountPicker.disable();
            }
            this.window.setTitle(String.format(this.app.i18n._('Edit {0} for {1}'), this.app.i18n._hidden(typeString), this.record.get('employee_id').n_fn));
        } else {
            this.window.setTitle(String.format(this.app.i18n._('Add {0} for {1}'),  this.app.i18n._hidden(typeString), this.record.get('employee_id').n_fn));
            this.statusPicker.setValue((this.freetimeType == 'SICKNESS') ? 'EXCUSED' : 'ACCEPTED');
        }
    },
    
    /**
     * just break if at least one day is selected, otherwise close the window
     * 
     * @param {Boolean} closeWindow
     * @return {Boolean}
     */
    onApplyChanges: function(closeWindow) {
        // if no day is selected, show message and break saving
        if (! this.datePicker.store.getFirstDay()) {
            var msg = this.freetimeType == 'SICKNESS'
                ? this.app.i18n._('You have to select at least one day to save this sickness entry.')
                : this.app.i18n._('You have to select at least one day to save this vacation entry.');
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
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordUpdate.call(this);
        
        var firstDay = this.datePicker.store.getFirstDay();
        var lastDay  = this.datePicker.store.getLastDay();
        
        // add in json by year if sickness record
        if (! this.accountPicker) {
            this.record.set('account_id', null);
        } else {
            this.record.set('account_id', this.currentAccount.data);
        }
        
        this.record.set('freedays',      this.datePicker.getData());
        this.record.set('type',          this.fixedFields.get('type').toLowerCase());
        this.record.set('firstday_date', new Date(firstDay.get('date')));
        this.record.set('lastday_date',  new Date(lastDay.get('date')));
        this.record.set('days_count',    this.datePicker.store.getCount());
    },
    
    /**
     * creates the date picker
     */
    initDatePicker: function() {
        var date, account = null;
        if (this.record.id && (account = this.record.get('account_id'))) {
            var year = account.year;
        } else {
            var year = this.startYear;
        }
        
        Tine.log.debug('Initializing the date picker. Using year ' + year);
        
        this.datePicker = new Tine.HumanResources.DatePicker({
            accountPickerActive: (this.freetimeType == 'VACATION') ? true : false,
            recordClass: Tine.HumanResources.Model.FreeDay,
            app: this.app,
            editDialog: this,
            dateProperty: 'date',
            recordsProperty: 'freedays',
            foreignIdProperty: 'freeday_id',
            freetimeType: this.freetimeType,
            currentYear: year,
            value: date || new Date(year, 1, 1)
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
     * initializes the account picker
     */
    initAccountPicker: function() {
        Tine.log.debug('Initializing the account picker...');
        var that = this;
        
        this.accountPicker = Tine.widgets.form.RecordPickerManager.get('HumanResources', 'Account', {
            name: 'account_id',
            fieldLabel: this.app.i18n._('Personal account'),
            additionalFilters: [
                {field: 'employee_id', operator: 'AND', value: [
                    { field: ':id', operator: 'equals', value: this.fixedFields.get('employee_id').id}
                ]}
            ],
            
            /**
             * fills the accountPicker on init with the account associated to 
             * the record or current year if the record is new
             * 
             * @param {Tine.HumanResources.Model.Account} record
             */
            onRecordLoad: function(record) {
                
                Tine.log.debug('Called onRecordLoad of the account picker...');
                
                if (record.get('account_id')) {
                    Tine.log.debug('The record has an account attached:', record.get('account_id'));
                    
                    that.currentAccount = new Tine.HumanResources.Model.Account(record.get('account_id'));
                    that.datePicker.loadFeastDays(false, true, null, that.currentAccount.get('year'));
                    return;
                }
                
                Tine.log.debug('The record is new, so no account has been attached. So we search one for the year ' + this.startYear);
                
                var addFilters = [{field: 'employee_id', operator: 'AND', value: [
                    { field: ':id', operator: 'equals', value: record.get('employee_id').id}
                ]}, {field: 'year', operator:'equals', value: this.startYear}];
                
                var request = Ext.Ajax.request({
                    url : 'index.php',
                    params : { method : 'HumanResources.searchAccounts', filter: addFilters},
                    success : function(_result, _request) {
                        var result = Ext.decode(_result.responseText);
                        this.store.loadData(result);
                        var rr = result.results;
                        for (var index = 0; index < rr.length; index++) {
                            if (rr[index].year == that.startYear) {
                                this.setValue(rr[index]);
                                that.currentAccount = new Tine.HumanResources.Model.Account(rr[index]);
                                that.datePicker.loadFeastDays(false, true, null, that.currentAccount.get('year'));
                            }
                        }
                    },
                    failure : function(exception) {
                        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                    },
                    scope: this
                });
            }
        });
        
        // update remaining days on account change
        this.accountPicker.on('select', function(combo, record, index) {
            var year = record.get('year');
            
            Tine.log.debug('On select event handler called. Using year ' + year);
            
            that.currentAccount = record;
            // if not cached, fetch from server
            if (! that.calculatedFeastDays[year]) {
                Tine.log.debug('No cached feast days found. Creating request...');
                var request = Ext.Ajax.request({
                    url : 'index.php',
                    params : { 
                        method : 'HumanResources.getFeastAndFreeDays',
                        _employeeId: this.fixedFields.get('employee_id').id,
                        _year: year
                    },
                    success : function(_result, _request) {
                        var response = Ext.decode(_result.responseText);
                        // cache result
                        this.calculatedFeastDays[year] = response.results;
                        this.onAccountLoad(response.results);
                        // update date picker
                        this.datePicker.onFeastDaysLoad(response, false, null, year);
                    },
                    failure : function(exception) {
                        Tine.Tinebase.ExceptionHandler.handleRequestException(exception, this.onFeastDaysLoadFailureCallback, this);
                    },
                    scope: that
                });
            } else {
                Tine.log.debug('Cached feast days found for year ' + year);
                that.onAccountLoad(this.calculatedFeastDays[year]);
            }
        }, this);
    },
    
    /**
     * initializes the status picker
     */
    initStatusPicker: function() {
        this.freetimeType = this.fixedFields.get('type');
        
        Tine.log.debug('Initializing status picker with type "' + this.freetimeType + '"');
        
        var statusPickerDefaults = {
            fieldLabel: this.app.i18n._('Status'),
            xtype: 'widget-keyfieldcombo',
            app: 'HumanResources',
            name: 'status'
        };
        if (this.freetimeType != 'VACATION') {
            this.statusPicker = new Tine.Tinebase.widgets.keyfield.ComboBox(
                Ext.apply({
                    keyFieldName: 'sicknessStatus',
                    value: 'EXCUSED',
                    columnWidth: 1
                }, statusPickerDefaults)
            );
        } else {
            this.statusPicker = new Tine.Tinebase.widgets.keyfield.ComboBox(
                Ext.apply({
                    keyFieldName: 'vacationStatus',
                    value: 'REQUESTED',
                    columnWidth: 2/3
                }, statusPickerDefaults)
            );
        }
    },
    
    /**
     * 
     * @param {} calculated
     */
    onAccountLoad: function(calculated) {
        var remaining = calculated.allVacation - this.getDaysToSubstract();
        
        Tine.log.debug('Called onAccountLoad. Calculated ' + remaining + ' remaining feast days.');
        
        this.form.findField('remaining_vacation_days').setValue(remaining);
    },
    
    /**
     * if loading feast and freedays failes
     */
    onFeastDaysLoadFailureCallback: function() {
    },
    
    /**
     * calculates the amount of days to substract for the remaining_vacation_days field
     * 
     * @return {Number}
     */
    getDaysToSubstract: function() {
        var substractDays = 0;
        // find out local free days to substract. this is by account only
        var accountId = this.currentAccount.get('id');
        
        if (this.localVacationDays.hasOwnProperty(accountId)) {
            substractDays = substractDays + this.localVacationDays[accountId].length;
        }
        
        return substractDays;
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
        this.initDatePicker();
        if (this.freetimeType == 'VACATION') {
            this.initAccountPicker();
        }
        this.initStatusPicker();
        
        var firstRow = [this.statusPicker];
        
        if (this.freetimeType == 'VACATION') {
            firstRow.push(this.accountPicker);
            firstRow.push({columnWidth: 1/3, name: 'remaining_vacation_days', readOnly: true, fieldLabel: this.app.i18n._('Remaining')});
            var freeTimeTypeName = 'Vacation Days';
        } else {
            var freeTimeTypeName = 'Sickness Days';
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
                                emptyText: this.app.i18n._('Enter description'),
                                requiredGrant: 'editGrant'
                            }]
                        })
                    ]
                }]
            }]
        };
    }
});
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Timetracker');

/**
 * Timetracker Edit Dialog
 */
Tine.Timetracker.TimesheetEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'TimesheetEditWindow_',
    appName: 'Timetracker',
    modelName: 'Timesheet',
    recordClass: Tine.Timetracker.Model.Timesheet,
    recordProxy: Tine.Timetracker.timesheetBackend,
    tbarItems: null,
    evalGrants: false,
    useInvoice: false,
    displayNotes: true,
    context: { 'skipClosedCheck': false },

    windowWidth: 800,
    windowHeight: 530,
    
    factor: '0',
    factorChanged: false,

    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function(record) {
        this.onTimeaccountUpdate();
        Tine.Timetracker.TimesheetEditDialog.superclass.updateToolbars.call(this, record, 'timeaccount_id');
    },

    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }

        if (! this.record.id) {
            // @todo: this should be handled by default values
            this.record.set('account_id', Tine.Tinebase.registry.get('currentAccount'));
            this.record.set('start_date', new Date());
        }

        Tine.Timetracker.TimesheetEditDialog.superclass.onRecordLoad.call(this);

        // TODO get timeaccount from filter if set
        var timeaccount = this.record.get('timeaccount_id');
        if (timeaccount) {
            this.onTimeaccountUpdate(null, new Tine.Timetracker.Model.Timeaccount(timeaccount));
        }
    },
    
    onTimeaccountSelect: function(field, timeaccount) {
        this.onTimeaccountUpdate(field, timeaccount);
        //set factor from timeaccount except it was manually changed before
        if (!this.factorChanged) {
            this.factor = timeaccount.data.accounting_time_factor;
        }
        this.getForm().findField('accounting_time_factor').setValue(this.factor);
        
        this.calculateAccountingTime();
    },

    /**
     * this gets called when initializing and if a new timeaccount is chosen
     * 
     * @param {} field
     * @param {} timeaccount
     */
    onTimeaccountUpdate: function(field, timeaccount) {
        // check for manage_timeaccounts right
        var manageRight = Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts');
        
        var notBillable = false;
        var notClearable = false;

        // TODO timeaccount.get('account_grants') contains [Object object] -> why is that so? this should be fixed
        var grants = this.record.get('timeaccount_id')
            ? this.record.get('timeaccount_id').account_grants
            : (timeaccount && timeaccount.get('container_id') && timeaccount.get('container_id').account_grants
                ? timeaccount.get('container_id').account_grants
                :  {});
        
        if (grants) {
            var setDisabled = ! (grants.bookAllGrant || grants.adminGrant || manageRight);
            var accountField = this.getForm().findField('account_id');
            accountField.setDisabled(setDisabled);
            // set account id to the current user, if he doesn't have the right to edit other users timesheets
            if (setDisabled) {
                if (this.copyRecord && (this.record.get('account_id') != Tine.Tinebase.registry.get('currentAccount').accountId)) {
                    accountField.setValue(Tine.Tinebase.registry.get('currentAccount'));
                }
            }
            notBillable = ! (grants.manageBillableGrant || grants.adminGrant || manageRight);
            notClearable = ! (grants.adminGrant || manageRight);
            this.getForm().findField('billed_in').setDisabled(! (grants.adminGrant || manageRight));
        }

        if (timeaccount && timeaccount.data) {
            notBillable = notBillable || timeaccount.data.is_billable == "0" || timeaccount.get('is_billable') == "0";
            
            // clearable depends on timeaccount is_billable as well (changed by ps / 2009-09-01, behaviour was inconsistent)
            notClearable = notClearable || timeaccount.data.is_billable == "0" || timeaccount.get('is_billable') == "0";

            if (timeaccount.data.is_billable == "0" || timeaccount.get('is_billable') == "0") {
                this.getForm().findField('is_billable').setValue(false);
            }
            
            //Always reset is_billable to true on copy timesheet (only if Timaccount is billable of course)
            if (this.copyRecord && (timeaccount.data.is_billable == "1" || timeaccount.get('is_billable') == "1")) {
                this.getForm().findField('is_billable').setValue(true);
            }

            this.getForm().findField('timeaccount_description').setValue(timeaccount.data.description);
        }

        this.getForm().findField('is_billable').setDisabled(notBillable);
        this.disableBillableFields(notBillable)
        this.getForm().findField('is_cleared').setDisabled(notClearable);
        this.disableClearedFields(notClearable);


        if (this.record.id == 0 && timeaccount) {
            // set is_billable for new records according to the timeaccount setting
            this.getForm().findField('is_billable').setValue(timeaccount.data.is_billable);
        }
    },
    
    /**
     * Always set is_billable if timeaccount is billable. This is needed for copied sheets where the
     * original is set to not billable
     */
    onAfterRecordLoad: function() {
        Tine.Timetracker.TimesheetEditDialog.superclass.onAfterRecordLoad.call(this);
        if (this.record.id == 0 && this.record.get('timeaccount_id') && this.record.get('timeaccount_id').is_billable) {
            this.getForm().findField('is_billable').setValue(this.record.get('timeaccount_id').is_billable);
        }
        this.factor = this.getForm().findField('accounting_time_factor').getValue()
        var focusFieldName = this.record.get('timeaccount_id') ? 'duration' : 'timeaccount_id',
            focusField = this.getForm().findField(focusFieldName);

        focusField.focus(true, 250);
    },

    /**
     * this gets called when initializing and if cleared checkbox is changed
     *
     * @param {} field
     * @param {} newValue
     *
     * @todo    add prompt later?
     */
    onClearedUpdate: function(field, checked) {
        if (!this.useMultiple) {
            this.getForm().findField('billed_in').setDisabled(! checked);
            if (this.useInvoice) {
                this.getForm().findField('invoice_id').setDisabled(! checked);
            }
        }
    },

    initComponent: function() {
        var salesApp = Tine.Tinebase.appMgr.get('Sales');
        this.useInvoice = Tine.Tinebase.appMgr.get('Sales')
            && salesApp.featureEnabled('invoicesModule')
            && Tine.Tinebase.common.hasRight('manage', 'Sales', 'invoices')
            && Tine.Sales.Model.Invoice;
        
        Tine.Timetracker.TimesheetEditDialog.superclass.initComponent.call(this);
    },

    /**
     * overwrites the isValid method on multipleEdit
     */
    isMultipleValid: function() {
        var valid = true;
        var keys = ['timeaccount_id', 'description', 'account_id'];
        Ext.each(keys, function(key) {
            var field = this.getForm().findField(key);
            if(field.edited && ! field.validate()) {
                field.markInvalid();
                valid = false;
            }
        }, this);
        return valid;
    },

    calculateAccountingTime: function() {
        var factor = this.getForm().findField('accounting_time_factor').getValue(),
            duration = this.getForm().findField('duration').getValue(),
            accountingTime = Math.round(factor * duration);
        if (factor != this.factor) {
            this.factor = factor;
            this.factorChanged = true;
        }
        this.getForm().findField('accounting_time').setValue(accountingTime);
    },

    calculateFactor: function() {
        var duration = this.getForm().findField('duration').getValue(),
            accountingTime = this.getForm().findField('accounting_time').getValue(),
            factor = accountingTime / duration;
        if (factor != this.factor) {
            this.factor = factor;
            this.factorChanged = true;
        }
        this.getForm().findField('accounting_time_factor').setValue(factor);
    },
    
    onCheckBillable: function(field, checked) {
        if (!this.useMultiple) {
            if (!checked) {
                this.getForm().findField('accounting_time_factor').setValue(0);
                this.getForm().findField('accounting_time').setValue(0);
                this.disableBillableFields(true);
                this.disableClearedFields(true);
                this.getForm().findField('is_cleared').setDisabled(true);

            } else {
                this.getForm().findField('accounting_time_factor').setValue(this.factor);
                this.calculateAccountingTime();
                this.disableBillableFields(false);
                this.disableClearedFields(false);
                this.getForm().findField('is_cleared').setDisabled(false);
            }
        }
    },
    
    disableBillableFields: function(disable) {
        this.getForm().findField('accounting_time_factor').setDisabled(disable);
        this.getForm().findField('accounting_time').setDisabled(disable);
    },

    disableClearedFields: function(disable) {
        this.getForm().findField('billed_in').setDisabled(disable);
        if (this.useInvoice) {
            this.getForm().findField('invoice_id').setDisabled(disable);
        }
    },

    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initialization is done.
     */
    getFormItems: function() {
        var _ = window.lodash,
            fieldManager = _.bind(
                Tine.widgets.form.FieldManager.get, 
                Tine.widgets.form.FieldManager, 
                this.appName, 
                this.modelName, 
                _, 
                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            items:[{
                title: this.app.i18n.ngettext('Timesheet', 'Timesheets', 1),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype: 'textfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: .25
                        },
                        items: [[
                            fieldManager('timeaccount_id', {
                                disabled: this.record.get('workingtime_is_cleared') ? true : false,
                                columnWidth: 1,
                                listeners: {
                                    scope: this,
                                    select: this.onTimeaccountSelect
                                },
                                lazyInit: false
                            })
                        ], [
                            fieldManager('duration', {
                                disabled: this.record.get('workingtime_is_cleared') ? true : false,
                                selectOnFocus: true,
                                allowBlank: false,
                                allowNegative: false,
                                enableKeyEvents: true,
                                listeners: {
                                    scope: this,
                                    blur: this.calculateAccountingTime,
                                    spin: this.calculateAccountingTime,
                                }
                            }),
                            fieldManager('start_date', {disabled: this.record.get('workingtime_is_cleared') ? true : false}), 
                            fieldManager('start_time', {disabled: this.record.get('workingtime_is_cleared') ? true : false}),
                            fieldManager('end_time', {disabled: this.record.get('workingtime_is_cleared') ? true : false}),
                        ], [
                            fieldManager('description', {
                                disabled: this.record.get('workingtime_is_cleared') ? true : false,
                                columnWidth: 1,
                                allowBlank: false,
                                height: 150}),
                            ]
                        ]
                    }, {
                        layout: 'hbox',
                        height: 160,
                        layoutConfig: {
                            align: 'stretch',
                            pack: 'start'
                        },
                        items: [{
                            flex: 1,
                            xtype: 'fieldset',
                            layout: 'hfit',
                            margins: '0 5 10 5',
                            title: this.app.i18n._('Accounting'),
                            items: [{
                                xtype: 'columnform',
                                labelAlign: 'top',
                                formDefaults: {
                                    xtype: 'textfield',
                                    anchor: '100%',
                                    labelSeparator: '',
                                    columnWidth: .25
                                },
                                items: [[
                                    fieldManager('is_billable', {disabled: (this.useMultiple) ? false : true,
                                        listeners: {
                                            scope: this,
                                            check: this.onCheckBillable
                                        }}),
                                    fieldManager('accounting_time_factor', {decimalSeparator: ',', listeners: {
                                        scope: this,
                                        change: this.calculateAccountingTime
                                    }}),
                                    fieldManager('accounting_time', {listeners: {
                                            scope: this,
                                            blur: this.calculateFactor,
                                            spin: this.calculateFactor
                                        }}),
                                    fieldManager('need_for_clarification'),
                                ], [
                                    fieldManager('is_cleared', {listeners: {
                                        scope: this,
                                        check: this.onClearedUpdate
                                    }}),
                                    fieldManager('billed_in', {disabled: this.record.get('workingtime_is_cleared') ? true : false}),
                                    this.useInvoice ? fieldManager('invoice_id') : {},
                                ], [
                                    fieldManager('workingtime_is_cleared', {disabled: true}),
                                    fieldManager('workingtime_cleared_in', {disabled: true, columnWidth: .5}),
                                ]]
                            }]
                        }]
                    }, {
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype: 'textfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: 1
                        },
                        items: [[
                            fieldManager('account_id')
                        ]]
                    }]
                }, {
                    // activities and tags
                    layout: 'ux.multiaccordion',
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
                        new Tine.widgets.tags.TagPanel({
                            app: 'Timetracker',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, {
                    title: this.app.i18n._('Timeaccount'),
                    autoScroll: true,
                    border: false,
                    frame: true,
                    layout: 'border',
                    items: [{
                        region: 'center',
                        layout: 'fit',
                        height: 400,
                        flex: 1,
                        border: false,
                        style: 'padding-bottom: 5px;',
                        items: [{
                            xtype: 'textarea',
                            name: 'timeaccount_description',
                            grow: false,
                            preventScrollbars: false,
                            fieldLabel: this.app.i18n._('Description'),
                            readOnly: true
                        }]
                    }]
                }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (! this.copyRecord) ? this.record.id : null,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
    
    /**
     * returns additional save params
     *
     * @returns {{checkBusyConflicts: boolean}}
     */
    getAdditionalSaveParams: function() {
        return {
            context: this.context
        };
    },
    
    /**
     * show error if request fails
     * 
     * @param {} response
     * @param {} request
     * @private
     */
    onRequestFailed: function(response, request) {
        this.saving = false;
        
        if (response.code && response.code == 902) {
            // deadline exception
            Ext.MessageBox.alert(
                this.app.i18n._('Failed'), 
                String.format(this.app.i18n._('Could not save {0}.'), this.i18nRecordName) 
                    + ' ( ' + this.app.i18n._('Booking deadline for this Timeaccount has been exceeded.') /* + ' ' + response.message  */ + ')'
            );
        } else if (response.code && response.code == 444) {
            //Time Account is closed
            if(Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts')) {
                this.onClosedWarning.apply(this, arguments);
            } else {
                Ext.MessageBox.alert(
                    this.app.i18n._('Closed Timeaccount Warning!'), 
                    String.format(this.app.i18n._('The selected Time Account is already closed.'))
                );
            }
        } else {
            // call default exception handler
            Tine.Tinebase.ExceptionHandler.handleRequestException(response);
        }
        this.hideLoadMask();
    },
    
    onClosedWarning: function() {
        Ext.Msg.confirm(this.app.i18n._('Closed Timeaccount Warning!'),
            this.app.i18n._('The selected Time Account is already closed. Do you wish to continue anyway?'),
            function(btn) {
                if (btn == 'yes') {
                    this.context = { 'skipClosedCheck': true };
                    this.onApplyChanges(true);
                }
            }, this);
    }
});

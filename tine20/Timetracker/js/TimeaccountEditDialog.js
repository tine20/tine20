/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Timetracker');

Tine.Timetracker.TimeaccountEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'TimeaccountEditWindow_',
    appName: 'Timetracker',
    modelName: 'Timeaccount',
    recordClass: 'Tine.Timetracker.Model.Timeaccount',
    // recordProxy: Tine.Timetracker.timeaccountBackend,
    useInvoice: false,
    displayNotes: true,
    showContainerSelector: false,
    
    windowWidth: 800,
    windowHeight: 500,

    /**
     * don't eval grants as TAs have very special grants and we only show the dialog to users with MANAGE right
     */
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function() {
    },
    
    initComponent: function() {
        var salesApp = Tine.Tinebase.appMgr.get('Sales');
        this.useInvoice = salesApp
            && salesApp.featureEnabled('invoicesModule')
            && Tine.Tinebase.common.hasRight('manage', 'Sales', 'invoices')
            && Tine.Sales.Model.Invoice;
        Tine.Timetracker.TimeaccountEditDialog.superclass.initComponent.call(this);
    },

    isMultipleValid: function() {
        return true;
    },

    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        
        var secondRow = [{
            fieldLabel: this.app.i18n._('Unit'),
            name: 'price_unit'
        }, {
            xtype: 'extuxmoneyfield',
            fieldLabel: this.app.i18n._('Unit Price'),
            name: 'price',
            allowNegative: false
        }, {
            columnWidth: 1/3,
            xtype: 'numberfield',
            fieldLabel: this.app.i18n._('Factor'),
            name: 'accounting_time_factor',
            allowNegative: false,
            decimalSeparator: ',',
            value: 0
        }, {
            xtype: 'numberfield',
            fieldLabel: this.app.i18n._('Budget'),
            name: 'budget',
            allowNegative: false,
            decimalSeparator: ',',
            value: 0
        }, {
            fieldLabel: this.app.i18n._('Status'),
            name: 'is_open',
            xtype: 'combo',
            mode: 'local',
            forceSelection: true,
            triggerAction: 'all',
            store: [[0, this.app.i18n._('closed')], [1, this.app.i18n._('open')]]
        }, [
            Tine.widgets.form.FieldManager.get(
                this.appName,
                this.modelName,
                'status',
                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                {
                    id: 'status',
                    listeners: {
                        scope: this,
                        select: this.onBilledChange.createDelegate(this)
                    } 

                }
            )
        ]];
        
        secondRow.push({
            columnWidth: 1/3,
            disabled: false,
            fieldLabel: this.app.i18n._('Cleared In'),
            name: 'billed_in'
        });
        
        secondRow.push([
            Tine.widgets.form.FieldManager.get(
                this.appName,
                this.modelName,
                'deadline',
                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                {
                    id: 'deadline',
                }
            )
        ]);
        
        secondRow.push({
            xtype: 'extuxclearabledatefield',
            name: 'cleared_at',
            fieldLabel: this.app.i18n._('Cleared at')
        });
        
        secondRow.push({
            xtype: 'tinerelationpickercombo',
            fieldLabel: this.app.i18n._('Cost Center'),
            editDialog: this,
            allowBlank: true,
            app: 'Tinebase',
            recordClass: Tine.Tinebase.Model.CostCenter,
            relationType: 'COST_CENTER',
            relationDegree: 'sibling',
            modelUnique: true
        });

        var lastRow = [{
            columnWidth: 1/3,
            editDialog: this,
            xtype: 'tinerelationpickercombo',
            fieldLabel: this.app.i18n._('Responsible Person'),
            allowBlank: true,
            app: 'Addressbook',
            recordClass: Tine.Addressbook.Model.Contact,
            relationType: 'RESPONSIBLE',
            relationDegree: 'sibling',
            modelUnique: true
        }, {
            hideLabel: true,
            boxLabel: this.app.i18n._('Timesheets are billable'),
            name: 'is_billable',
            xtype: 'checkbox',
            columnWidth: 1/3
        }];
        
        if (this.useInvoice) {
            lastRow.push(Tine.widgets.form.RecordPickerManager.get('Sales', 'Invoice', {
                columnWidth: 1/3,
                disabled: true,
                fieldLabel: this.app.i18n._('Invoice'),
                name: 'invoice_id'
            }));
        }
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
                title: this.app.i18n.ngettext('Time Account', 'Time Accounts', 1),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: .333
                    },
                    items: [[{
                        fieldLabel: this.app.i18n._('Number'),
                        name: 'number',
                        allowBlank: false,
                        maxLength: 127
                        }, {
                        columnWidth: .666,
                        fieldLabel: this.app.i18n._('Title'),
                        name: 'title',
                        maxLength: 255,
                        allowBlank: false
                        }], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Description'),
                        xtype: 'textarea',
                        name: 'description',
                        height: 150
                        }], secondRow, lastRow] 
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
                    })]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (! this.copyRecord) ? this.record.id : null,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
    
    /**
     * is called is billed field changes
     */
    onBilledChange: function(combo, record, index) {
        if (combo.getValue() == 'billed') {
            if (! this.getForm().findField('cleared_at').getValue()) {
                this.getForm().findField('cleared_at').setValue(new Date());
            }
        }
    },
    
    doCopyRecord: function() {
        Tine.Timetracker.TimeaccountEditDialog.superclass.doCopyRecord.call(this);
        
        this.record.set('status', 'not yet billed');
        this.record.set('is_open', 1 );
    }
});

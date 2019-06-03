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
 * @class       Tine.HumanResources.EmployeeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Employee Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.EmployeeEditDialog
 */
Tine.HumanResources.EmployeeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    evalGrants: false,
    
    windowWidth: 800,
    windowHeight: 670,
    
    /**
     * show private Information (autoset due to rights)
     * 
     * @type {Boolean}
     */
    showPrivateInformation: null,
    /**
     * inits the component
     */
    initComponent: function() {
        this.showPrivateInformation = (Tine.Tinebase.common.hasRight('edit_private','HumanResources')) ? true : false;
        this.useSales = Tine.Tinebase.appMgr.get('Sales') ? true : false;
        Tine.HumanResources.EmployeeEditDialog.superclass.initComponent.call(this);
        this.on('updateDependent', function() {
            this.disableFreetimes();
        }, this);
    },

    /**
     * updates the display name on change of n_given or n_fanily
     */
    updateDisplayName: function() {
        var nfn = this.getForm().findField('n_given').getValue() + (this.getForm().findField('n_family').getValue() ? ' ' + this.getForm().findField('n_family').getValue() : '');
        this.getForm().findField('n_fn').setValue(nfn);
    },
    
    /**
     * checks if the freetime grids should be disabled
     * 
     * @return {Boolean}
     */
    checkDisableFreetimes: function() {
        // if user is not allowed to see private information, disable the grids
        if (! this.showPrivateInformation) {
            return true;
        }
        
        if (! this.record) {
            return true;
        }
        var c = this.record.get('contracts');
        
        if (Ext.isArray(c) && c.length > 0) {
            // any of the contracts has an id
            for (var index = 0; index < c.length; index++) {
                if (c[index].id) return false;
            }
        }
        
        return true;
    },
    
    /**
     * disable freetime gridpanels if neccessary
     */
    disableFreetimes: function() {
        if (this.checkDisableFreetimes()) {
            this.vacationGridPanel.disable();
            this.sicknessGridPanel.disable();
        } else {
            this.vacationGridPanel.enable();
            this.sicknessGridPanel.enable();
        }
    },
    
    onAfterRecordLoad: function() {
        Tine.HumanResources.EmployeeEditDialog.superclass.onAfterRecordLoad.call(this);
        this.disableFreetimes();
        if (this.record.get('id') && (! Ext.isObject(this.record.get('account_id')))) {
            var f = this.getForm().findField('account_id');
            f.disable();
            f.setRawValue(this.app.i18n._('Account is disabled or deleted!'));
        }
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
        var formFieldDefaults = {
            xtype:'textfield',
            anchor: '100%',
            labelSeparator: '',
            columnWidth: .333
        };
        
        var firstRow = [
            Tine.widgets.form.FieldManager.get(
            this.appName,
            this.modelName,
            'supervisor_id',
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
            {
                allowLinkingItself: false,

            }
        )];
            
        if (this.useSales) {
            firstRow.push(Tine.widgets.form.RecordPickerManager.get('Sales', 'Division', {
                    name: 'division_id',
                    fieldLabel: this.app.i18n._('Division'),
                    allowBlank: true
            }));
        }
        
        firstRow.push({
            name: 'health_insurance',
            fieldLabel: this.app.i18n._('Health Insurance'),
            allowBlank: true,
            maxLength: 128
        });

        this.contractGridPanel = new Tine.HumanResources.ContractGridPanel({
            app: this.app,
            editDialog: this,
            disabled: ! this.showPrivateInformation,
            frame: false,
            border: true,
            autoScroll: true,
            layout: 'border',
            hideColumns: ['employee_id']
        });
        
        this.vacationGridPanel = new Tine.HumanResources.FreeTimeGridPanel({
            app: this.app,
            editDialog: this,
            disabled: this.checkDisableFreetimes(),
            frame: false,
            border: true,
            autoScroll: true,
            layout: 'border',
            freetimeType: 'VACATION',
            editDialogRecordProperty: 'vacation',
            hideColumns: ['employee_id']
        });
        this.sicknessGridPanel = new Tine.HumanResources.FreeTimeGridPanel({
            app: this.app,
            editDialog: this,
            disabled: this.checkDisableFreetimes(),
            frame: false,
            border: true,
            autoScroll: true,
            layout: 'border',
            freetimeType: 'SICKNESS',
            editDialogRecordProperty: 'sickness',
            hideColumns: ['employee_id']
        });
            
        var tabs = [{
            title: this.app.i18n._('Employee'),
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
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Employee'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: formFieldDefaults,
                        items: [[
                                Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'number',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                {
                                    columnWidth: .125,
                                    maxValue: 999999999
                                }
                            ), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'account_id',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                {
                                    columnWidth: .380,
                                    ref: '../../../../../../../contactPicker',
                                    userOnly: true,
                                    useAccountRecord: true,
                                    blurOnSelect: true,
                                    listeners: {
                                        scope: this,
                                        blur: function() {
                                            if (this.contactPicker.selectedRecord) {
                                                this.contactButton.enable();
                                            } else {
                                                this.contactButton.disable();
                                            }
                                        }
                                    }
                                }
                            ), {
                               columnWidth: .045,
                               xtype:'button',
                               ref: '../../../../../../../contactButton',
                               iconCls: 'applyContactData',
                               tooltip: Ext.util.Format.htmlEncode(this.app.i18n._('Apply contact data on form')),
                               disabled: (this.record && Ext.isObject(this.record.get('account_id'))) ? false : true,
                               fieldLabel: '&nbsp;',
                               lazyLoading: false,
                               listeners: {
                                    scope: this,
                                    click: function() {
                                        var sr = this.contactPicker.selectedRecord || new Tine.Addressbook.Model.Contact(this.record.data.account_id.contact_id);
                                        if (sr) {
                                            Ext.each(['n_fn', 'title', 'salutation', 'n_given', 'n_family'], function(f) {
                                                this.form.findField(f).setValue(sr.get(f));
                                            }, this);
                                            
                                            if (this.showPrivateInformation) {
                                                this.form.findField('bank_account_holder').setValue(sr.get('n_fn'));
                                                Ext.each(['countryname', 'locality', 'postalcode', 'region', 'street', 'street2'], function(f){
                                                    this.form.findField(f).setValue(sr.get('adr_two_' + f));
                                                }, this);
                                                
                                                Ext.each(['email', 'tel_home', 'tel_cell', 'bday'], function(f){
                                                    this.form.findField(f).setValue(sr.get(f));
                                                }, this);
                                            }
                                        }
                                    }
                               }
                            }, Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'n_fn',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                {
                                    columnWidth: .450,
                                    disabled: true
                                }
                            )], [
                            new Tine.Tinebase.widgets.keyfield.ComboBox({
                                fieldLabel: this.app.i18n._('Salutation'),
                                name: 'salutation',
                                app: 'Addressbook',
                                keyFieldName: 'contactSalutation',
                                value: '',
                                columnWidth: .25
                            }), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'title',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                {
                                    columnWidth: .25,
                                }
                            ),  Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'n_given',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                {
                                    columnWidth: .25,
                                    listeners: {
                                        scope: this,
                                        change: this.updateDisplayName
                                    }
                                }
                            ), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'n_family',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                {
                                    columnWidth: .25,
                                    listeners: {
                                        scope: this,
                                        change: this.updateDisplayName
                                    }
                                }
                            )], [
                                Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'dfcom_id',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                {
                                    columnWidth: 1
                                }
                            )]
                        ]
                    }]
                }, {
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Personal Information'),
                    disabled: ! this.showPrivateInformation,
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: Ext.apply(Ext.decode(Ext.encode(formFieldDefaults)), {disabled: ! this.showPrivateInformation, readOnly: ! this.showPrivateInformation}),
                        items: [
                            [{
                                xtype: 'widget-countrycombo',
                                name: 'countryname',
                                fieldLabel: this.app.i18n._('Country')
                            }, Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'locality',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            ), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'postalcode',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            )], [Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'region',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            ), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'street',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            ), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'street2',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            )], [Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'email',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            ), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'tel_home',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            ), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'tel_cell',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            )], [{
                                xtype: 'extuxclearabledatefield',
                                name: 'bday',
                                fieldLabel: this.app.i18n._('Birthday')
                            }
                        ]]
                    }]
                }, {
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Internal Information'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: Ext.apply(Ext.decode(Ext.encode(formFieldDefaults)), {}),
                        items: [ firstRow
                            , [Tine.widgets.form.FieldManager.get(
                                    this.appName,
                                    this.modelName,
                                    'employment_begin',
                                    Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                    {
                                        columnWidth: .5,
                                    }
                                ),
                                Tine.widgets.form.FieldManager.get(
                                    this.appName,
                                    this.modelName,
                                    'employment_end',
                                    Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                    {
                                        columnWidth: .5,
                                    }
                                ), Tine.widgets.form.FieldManager.get(
                                    this.appName,
                                    this.modelName,
                                    'profession',
                                    Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                    {
                                        columnWidth: .5,
                                    }
                                ),
                                Tine.widgets.form.FieldManager.get(
                                    this.appName,
                                    this.modelName,
                                    'position',
                                    Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                    {
                                        columnWidth: .5,
                                    }
                                )
                                
                            ]
                        ]
                    }]
                }, {
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Banking Information'),
                    disabled: ! this.showPrivateInformation,
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: Ext.apply(Ext.decode(Ext.encode(formFieldDefaults)), {disabled: ! this.showPrivateInformation, readOnly: ! this.showPrivateInformation}),
                        items: [
                            [Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'bank_account_holder',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            ), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'bank_account_number',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            ), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'bank_name',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            )], [
                                Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'bank_code_number',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            ), Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'iban',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            ),Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'bic',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
                            )
                        ]]
                    }]
                }
                
                ]
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
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'HumanResources',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })
                ]
            }]
        }];
        
        if (this.useSales) {
            this.costCenterGridPanel = new Tine.HumanResources.CostCenterGridPanel({
                app: this.app,
                editDialog: this,
                disabled: ! this.showPrivateInformation
            });
            tabs.push(this.costCenterGridPanel);
        }
        
        tabs = tabs.concat([
            this.contractGridPanel,
            this.vacationGridPanel,
            this.sicknessGridPanel,
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: 'HumanResources_Model_Employee'
            })
        ]);
        
        return {
            xtype: 'tabpanel',
            defaults: {
                hideMode: 'offsets'
            },
            border: false,
            plain: true,
            activeTab: 0,
            items: tabs
        };
    }
});

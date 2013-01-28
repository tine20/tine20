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
    windowNamePrefix: 'EmployeeEditWindow_',
    appName: 'HumanResources',
    recordClass: Tine.HumanResources.Model.Employee,
    recordProxy: Tine.HumanResources.employeeBackend,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    
    /**
     * use the sales app
     * 
     * @type {Boolean}
     */
    useSales: null,
    
    showContainerSelector: false,
    
    /**
     * holds window instances dependent to this dialog
     * @type {Array}
     */
    openSubPanels: null,
    
    /**
     * show private Information (autoset due to rights)
     * 
     * @type {Boolean}
     */
    showPrivateInformation: null,

    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: function() {
    },

    /**
     * inits the component
     */
    initComponent: function() {
        this.showPrivateInformation = (Tine.Tinebase.common.hasRight('edit_private','HumanResources')) ? true : false;
        this.useSales = Tine.Tinebase.appMgr.get('Sales') ? true : false;
        this.openSubPanels = [];
        Tine.HumanResources.EmployeeEditDialog.superclass.initComponent.call(this);
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
        this.contractGridPanel.onRecordLoad();
        Tine.HumanResources.EmployeeEditDialog.superclass.onRecordLoad.call(this);
    },

    /**
     * updates the display name on change of n_given or n_fanily
     */
    updateDisplayName: function() {
        var nfn = this.getForm().findField('n_given').getValue() + (this.getForm().findField('n_family').getValue() ? ' ' + this.getForm().findField('n_family').getValue() : '');
        this.getForm().findField('n_fn').setValue(nfn);
    },
    
    /**
     * closes open subpanels on cancel
     */
    onCancel: function() {
        if (this.openSubPanels.length) {
            Ext.each(this.openSubPanels, function(window) {
                window.purgeListeners();
                window.close();
            }, this);
        }
        Tine.HumanResources.EmployeeEditDialog.superclass.onCancel.call(this);
    },
    
    /**
     * show message if there are some subpanels
     */
    onSaveAndClose: function() {
        if (this.openSubPanels.length) {
            Ext.MessageBox.show({
                title: _('Dependent Windows'), 
                msg: _('There are still some windows active dependent to this one. Please save or cancel them before closing this one!'),
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING
            });
            return;
        }
        Tine.HumanResources.EmployeeEditDialog.superclass.onSaveAndClose.call(this);
    },
    
    /**
     * executed when record gets updated from form
     * @private
     */
    onRecordUpdate: function() {
        var contracts = [];
        this.contractGridPanel.store.query().each(function(contract) {
            contracts.push(contract.data);
        }, this);
        
        this.record.set('contracts', contracts);
        
        if (this.useSales) {
            var costcenters = [];
            this.costCenterGridPanel.store.query().each(function(c) {
                costcenters.push(c.data);
            }, this);
            this.record.set('costcenters', costcenters);
        }
        
        Tine.HumanResources.EmployeeEditDialog.superclass.onRecordUpdate.call(this);
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
            Tine.widgets.form.RecordPickerManager.get('HumanResources', 'Employee', {
                    allowLinkingItself: false,
                    name: 'supervisor_id',
                    fieldLabel: this.app.i18n._('Supervisor'),
                    editDialog: this,
                    allowBlank: true
            })];
            
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
            allowBlank: true
        });
        
        this.contractGridPanel = new Tine.HumanResources.ContractGridPanel({
            app: this.app,
            editDialog: this,
            disabled: ! this.showPrivateInformation
        });
        
        this.freetimeGridPanel = new Tine.HumanResources.FreeTimeGridPanel({
            disabled: (this.record && this.record.id) ? false : true,
            app: this.app,
            editDialog: this,
            title: this.app.i18n.ngettext('Free Time', 'Free Times', 2),
            frame: true,
            border: true,
            autoScroll: true,
            layout: 'border'
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
                        items: [[{
                                fieldLabel: this.app.i18n._('Number'),
                                name: 'number',
                                allowBlank: false,
                                columnWidth: .125,
                                xtype: 'numberfield'
                            }, 
                                Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                    userOnly: true,
                                    useAccountRecord: true,
                                    blurOnSelect: true,
                                    name: 'account_id',
                                    fieldLabel: this.app.i18n._('Account'),
                                    columnWidth: .380,
                                    ref: '../../../../../../../contactPicker',
                                    allowBlank: true,
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
                                }), {
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
                                            
                                            if(this.showPrivateInformation) {
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
                            }, {
                                columnWidth: .450,
                                allowBlank: false,
                                fieldLabel: this.app.i18n._('Full Name'),
                                name: 'n_fn',
                                disabled: true
                            }], [
                            new Tine.Tinebase.widgets.keyfield.ComboBox({
                                fieldLabel: this.app.i18n._('Salutation'),
                                name: 'salutation',
                                app: 'Addressbook',
                                keyFieldName: 'contactSalutation',
                                value: '',
                                columnWidth: .25
                            }), {
                                columnWidth: .25,
                                fieldLabel: this.app.i18n._('Title'),
                                name: 'title'
                            }, {
                                columnWidth: .25,
                                fieldLabel: this.app.i18n._('First Name'),
                                name: 'n_given',
                                allowBlank: false,
                                listeners: {
                                    scope: this,
                                    blur: this.updateDisplayName
                                }
                            }, {
                                columnWidth: .25,
                                fieldLabel: this.app.i18n._('Last Name'),
                                name: 'n_family',
                                allowBlank: false,
                                listeners: {
                                    scope: this,
                                    blur: this.updateDisplayName
                                }
                            }]
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
                            }, {
                                name: 'locality',
                                fieldLabel: this.app.i18n._('Locality')
                            }, {
                                name: 'postalcode',
                                fieldLabel: this.app.i18n._('Postalcode')
                            }], [{
                                name: 'region',
                                fieldLabel: this.app.i18n._('Region')
                            }, {
                                name: 'street',
                                fieldLabel: this.app.i18n._('Street')
                            }, {
                                name: 'street2',
                                fieldLabel: this.app.i18n._('Street2')
                            }], [{
                                name: 'email',
                                fieldLabel: this.app.i18n._('E-Mail')
                            }, {
                                name: 'tel_home',
                                fieldLabel: this.app.i18n._('Telephone Number')
                            }, {
                                name: 'tel_cell',
                                fieldLabel: this.app.i18n._('Cell Phone Number')
                            }], [{
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
                            , [{
                                xtype: 'extuxclearabledatefield',
                                name: 'employment_begin',
                                fieldLabel: this.app.i18n._('Employment begin')
                            }, {
                                xtype: 'extuxclearabledatefield',
                                name: 'employment_end',
                                allowBlank: true,
                                fieldLabel: this.app.i18n._('Employment end')
                            }, {
                                name: 'profession',
                                fieldLabel: this.app.i18n._('Profession')
                            }
                        ]]
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
                            [{
                                name: 'bank_account_holder',
                                fieldLabel: this.app.i18n._('Account Holder')
                            }, {
                                name: 'bank_account_number',
                                fieldLabel: this.app.i18n._('Account Number')
                            }, {
                                name: 'bank_name',
                                fieldLabel: this.app.i18n._('Bank Name')
                            }], [{
                                name: 'bank_code_number',
                                fieldLabel: this.app.i18n._('Code Number')
                            }
                        ]]
                    }]
                }
                
                ]
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
                    }),
                    new Tine.widgets.activities.ActivitiesPanel({
                        app: 'HumanResources',
                        showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
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
            this.freetimeGridPanel,
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })
        ]);
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            activeTab: 0,
            border: false,
            items: tabs
        };
    }
});

/**
 * HumanResources Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.HumanResources.EmployeeEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 620,
        name: Tine.HumanResources.EmployeeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.HumanResources.EmployeeEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

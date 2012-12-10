/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * Contract edit dialog
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ContractEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Contract Edit Dialog</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.ContractGridPanel
 */
Tine.Sales.ContractEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @private
     */
    labelAlign: 'side',
    
    /**
     * @private
     */
    windowNamePrefix: 'ContractEditWindow_',
    appName: 'Sales',
    recordClass: Tine.Sales.Model.Contract,
    recordProxy: Tine.Sales.contractBackend,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    /**
     * if true, number will be readOnly and will be generated automatically
     * @type {Boolean} autoGenerateNumber
     */
    autoGenerateNumber: null,
    /**
     * how should the number be validated text/integer possible
     * @type {String} validateNumber
     */
    validateNumber: null,
    
    initComponent: function() {
        this.autoGenerateNumber = (Tine.Sales.registry.get('config').contractNumberGeneration.value == 'auto') ? true : false;
        this.validateNumber = Tine.Sales.registry.get('config').contractNumberValidation.value;
        Tine.Sales.ContractEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * reqests all data needed in this dialog
     */
    requestData: function() {
        this.loadRequest = Ext.Ajax.request({
            scope: this,
            success: function(response) {
                this.record = this.recordProxy.recordReader(response);
                this.onRecordLoad();
            },
            params: {
                method: 'Sales.getContract',
                id: this.record.id
            }
        });
    },
    
    /**
     * called on multiple edit
     * @return {Boolean}
     */
    isMultipleValid: function() {
        return true;
    },
    
    /**
     * extra validation for the number field, calls parent
     * @return {Boolean}
     */
    isValid: function() {
        var valid = Tine.Sales.ContractEditDialog.superclass.isValid.call(this);
        var isValid = this.autoGenerateNumber ? true : (this.validateNumber == 'integer') ? Ext.isNumber(Ext.num(this.getForm().findField('number').getValue())) : true;
        if(!isValid) {
            this.getForm().findField('number').markInvalid(this.app.i18n._('Please use a decimal number here!'));
        }
        return isValid && valid;
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            layoutOnTabChange: true,
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            items:[
                {
                title: this.app.i18n.n_('Contract', 'Contract', 1),
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
                        columnWidth: .25,
                        fieldLabel: this.app.i18n._('Number'),
                        name: 'number',
                        multiEditable: false,
                        readOnly: this.autoGenerateNumber,
                        allowBlank: this.autoGenerateNumber
                    },{
                        columnWidth: .75,
                        fieldLabel: this.app.i18n._('Title'),
                        name: 'title',
                        allowBlank: false
                    }], [{
                            columnWidth: .5,
                            xtype: 'tinerelationpickercombo',
                            fieldLabel: this.app.i18n._('Contract Contact'),
                            editDialog: this,
                            allowBlank: true,
                            app: 'Addressbook',
                            recordClass: Tine.Addressbook.Model.Contact,
                            relationType: 'CUSTOMER',
                            relationDegree: 'sibling',
                            modelUnique: true
                        }, {
                            columnWidth: .5,
                            editDialog: this,
                            xtype: 'tinerelationpickercombo',
                            fieldLabel: this.app.i18n._('Account Manager'),
                            allowBlank: true,
                            app: 'Addressbook',
                            recordClass: Tine.Addressbook.Model.Contact,
                            relationType: 'RESPONSIBLE',
                            relationDegree: 'sibling',
                            modelUnique: true
                        }/*, {
                            name: 'customer',
                            fieldLabel: this.app.i18n._('Company')
                        }*/],[
                        
                        new Tine.Tinebase.widgets.keyfield.ComboBox({
                                    app: 'Sales',
                                    keyFieldName: 'contractStatus',
                                    fieldLabel: this.app.i18n._('Status'),
                                    name: 'status'
                                }),
                                
                        new Tine.Tinebase.widgets.keyfield.ComboBox({
                                    app: 'Sales',
                                    keyFieldName: 'contractCleared',
                                    fieldLabel: this.app.i18n._('Cleared'),
                                    name: 'cleared'
                                }),
                        {
                            fieldLabel: this.app.i18n._('Cleared in'),
                            name: 'cleared_in',
                            xtype: 'textfield'
                        }
                    ], [{
                            columnWidth: 1,
                            fieldLabel: this.app.i18n._('Description'),
                            emptyText: this.app.i18n._('Enter description...'),
                            name: 'description',
                            xtype: 'textarea',
                            height: 200
                    }]] 
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
                        new Tine.widgets.activities.ActivitiesPanel({
                            app: 'Sales',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'Sales',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});

/**
 * Sales Edit Popup
 */
Tine.Sales.ContractEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Sales.ContractEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Sales.ContractEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

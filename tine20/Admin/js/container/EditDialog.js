/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
/*global Ext, Tine*/
require('widgets/form/ApplicationPickerCombo');

Ext.ns('Tine.Admin.container');

/**
 * @namespace   Tine.Admin.container
 * @class       Tine.Admin.ContainerEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Container Edit Dialog</p>
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Admin.ContainerEditDialog
 * 
 * TODO add note for personal containers (note is sent to container owner)
 */
Tine.Admin.ContainerEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'containerEditWindow_',
    appName: 'Admin',
    recordClass: Tine.Admin.Model.Container,
    recordProxy: Tine.Admin.containerBackend,
    evalGrants: false,
    modelStore: null,
    
    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function () {
        Tine.Admin.ContainerEditDialog.superclass.onRecordLoad.apply(this, arguments);
        
        // load grants store if editing record
        if (this.record && this.record.id) {
            this.grantsGrid.getStore().loadData({
                results:    this.record.get('account_grants'),
                totalcount: this.record.get('account_grants').length
            });
        }
    },    
    
    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function () {
        Tine.Admin.ContainerEditDialog.superclass.onRecordUpdate.apply(this, arguments);
        
        // get grants from grants grid
        this.record.set('account_grants', '');
        var grants = [];
        this.grantsGrid.getStore().each(function(grant){
            grants.push(grant.data);
        });
        this.record.set('account_grants', grants);
    },
    
    /**
     * create grants store + grid
     * 
     * @return {Tine.widgets.container.GrantsGrid}
     */
    initGrantsGrid: function () {
        this.grantsGrid = new Tine.widgets.container.GrantsGrid({
            flex: 1,
            grantContainer: this.record.data,
            alwaysShowAdminGrant: true,
            showHidden: true
        });
        
        return this.grantsGrid;
    },

    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            defaults: {
                hideMode: 'offsets'
            },
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            items:[
                {
                    title: this.i18nRecordName,
                    autoScroll: true,
                    border: false,
                    frame: true,
                    layout: 'border',
                    defaults: { autoScroll: true },
                    items: [{
                        region: 'center',
                        layout: 'fit',
                        items: this.getContainerFormItems()
                    }].concat(this.getEastPanel())
                }, new Tine.widgets.activities.ActivitiesTabPanel({
                    app: this.appName,
                    record_id: this.record.id,
                    record_model: 'Tinebase_Model_Container'
                })
            ]
        };
    },

    /**
     * returns dialog
     */
    getContainerFormItems: function () {
        this.modelStore = new Ext.data.ArrayStore({
            idIndex: 0,
            fields: [{name: 'value'}, {name: 'name'}]
        });

        return {
            layout: 'vbox',
            layoutConfig: {
                align: 'stretch',
                pack: 'start'
            },
            border: false,
            items: [{
                xtype: 'columnform',
                border: false,
                autoHeight: true,
                items: [[{
                    columnWidth: 0.2,
                    fieldLabel: this.app.i18n._('Name'), 
                    name: 'name',
                    allowBlank: false,
                    maxLength: 255
                }, {
                    xtype: 'tw-app-picker',
                    readOnly: this.record.id != 0,
                    columnWidth: 0.2,
                    name: 'application_id',
                    fieldLabel: this.app.i18n._('Application'),
                    anchor: '100%',
                    allowBlank: false,
                    listeners: {
                        scope: this,
                        'select': function (combo, rec) {
                            this.modelStore.loadData(this.getApplicationModels(rec, false));
                            Ext.getCmp('modelCombo').setValue('');
                        }
                    }
                },
                {
                    xtype: 'combo',
                    readOnly: this.record.id != 0,
                    store: this.modelStore,
                    columnWidth: 0.2,
                    name: 'model',
                    displayField: 'name',
                    valueField: 'value',
                    fieldLabel: this.app.i18n._('Model'),
                    mode: 'local',
                    anchor: '100%',
                    allowBlank: false,
                    forceSelection: true,
                    editable: false,
                    id: 'modelCombo'
                }, {
                    xtype: 'combo',
                    columnWidth: 0.2,
                    name: 'type',
                    fieldLabel: this.app.i18n._('Type'),
                    store: [['personal', this.app.i18n._('personal')], ['shared', this.app.i18n._('shared')]],
                    listeners: {
                        scope: this,
                        select: function (combo, record) {
                            this.getForm().findField('note').setDisabled(record.data.field1 === 'shared');
                        }
                    },
                    mode: 'local',
                    anchor: '100%',
                    allowBlank: false,
                    forceSelection: true
                }, {
                    columnWidth: 0.1,
                    fieldLabel: this.app.i18n._('Order'),
                    name: 'order',
                    value: 0
                }, {
                    xtype: 'colorfield',
                    columnWidth: 0.1,
                    fieldLabel: this.app.i18n._('Color'),
                    name: 'color'
                }],[{
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Container Hierarchy/Name'),
                    allowBlank: false,
                    columnWidth: 1,
                    name: 'hierarchy',
                    allowBlank: true
                }]]
            }, 
                this.initGrantsGrid(), {
                    emptyText: this.app.i18n._('Note for Owner'),
                    disabled: this.record.get('type') == 'shared',
                    xtype: 'textarea',
                    border: false,
                    autoHeight: true,
                    name: 'note'
                }
               ]
        };
    },
    
    onSaveAndClose: function() {
        if (this.record.data.model == 'Tine.Calendar.Model.Resource') {
            Ext.MessageBox.alert(this.app.i18n._('Info'), this.app.i18n._('Please create Resources in Addressbook or CoreData.'),function (){this.window.close()},this);
        } else {
            Tine.Admin.ContainerEditDialog.superclass.onSaveAndClose.apply(this, arguments);
        }
    }
});



/**
 * Container Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Admin.ContainerEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 1024,
        height: 500,
        name: Tine.Admin.ContainerEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Admin.ContainerEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

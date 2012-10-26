/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Timetracker');

Tine.Timetracker.TimeaccountEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'TimeaccountEditWindow_',
    appName: 'Timetracker',
    recordClass: Tine.Timetracker.Model.Timeaccount,
    recordProxy: Tine.Timetracker.timeaccountBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function() {

    },
    
    onRecordLoad: function() {
        // make sure grants grid is initialised
        this.getGrantsGrid();
        
        var grants = this.record.get('grants') || [];
        this.grantsStore.loadData({results: grants});
        Tine.Timetracker.TimeaccountEditDialog.superclass.onRecordLoad.call(this);
        
    },
    
    onRecordUpdate: function() {
        Tine.Timetracker.TimeaccountEditDialog.superclass.onRecordUpdate.call(this);
        this.record.set('grants', '');
        
        var grants = [];
        this.grantsStore.each(function(_record){
            grants.push(_record.data);
        });
        
        this.record.set('grants', grants);
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
            xtype: 'numberfield',
            fieldLabel: this.app.i18n._('Unit Price'),
            name: 'price',
            allowNegative: false
            //decimalSeparator: ','
        }, {
            fieldLabel: this.app.i18n._('Budget'),
            name: 'budget'
        }, {
            fieldLabel: this.app.i18n._('Status'),
            name: 'is_open',
            xtype: 'combo',
            mode: 'local',
            forceSelection: true,
            triggerAction: 'all',
            store: [[0, this.app.i18n._('closed')], [1, this.app.i18n._('open')]]
        }, {
            fieldLabel: this.app.i18n._('Billed'),
            name: 'status',
            xtype: 'combo',
            mode: 'local',
            forceSelection: true,
            triggerAction: 'all',
            value: 'not yet billed',
            store: [
                ['not yet billed', this.app.i18n._('not yet billed')], 
                ['to bill', this.app.i18n._('to bill')],
                ['billed', this.app.i18n._('billed')]
            ]
        }, {
            //disabled: true,
            //emptyText: this.app.i18n._('not cleared yet...'),
            fieldLabel: this.app.i18n._('Cleared In'),
            name: 'billed_in',
            xtype: 'textfield'
        }, {
            fieldLabel: this.app.i18n._('Booking deadline'),
            name: 'deadline',
            xtype: 'combo',
            mode: 'local',
            forceSelection: true,
            triggerAction: 'all',
            value: 'none',
            store: [
                ['none', this.app.i18n._('none')], 
                ['lastweek', this.app.i18n._('last week')]
            ]
        }];
        
        if(Tine.Tinebase.appMgr.get('Sales')) {
            secondRow.push({
                xtype: 'tinerelationpickercombo',
                fieldLabel: this.app.i18n._('Cost Center'),
                editDialog: this,
                allowBlank: true,
                app: 'Sales',
                recordClass: Tine.Sales.Model.CostCenter,
                relationType: 'COST_CENTER',
                relationDegree: 'sibling',
                modelUnique: true
            });
        }
        
        secondRow.push({
            hideLabel: true,
            boxLabel: this.app.i18n._('Timesheets are billable'),
            name: 'is_billable',
            xtype: 'checkbox',
            columnWidth: .666
        })
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
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
                        allowBlank: false
                        }, {
                        columnWidth: .666,
                        fieldLabel: this.app.i18n._('Title'),
                        name: 'title',
                        allowBlank: false
                        }], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Description'),
                        xtype: 'textarea',
                        name: 'description',
                        height: 150
                        }], secondRow] 
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
                    items: [/*new Ext.Panel({
                        // @todo generalise!
                        title: this.app.i18n._('Description'),
                        iconCls: 'descriptionIcon',
                        layout: 'form',
                        labelAlign: 'top',
                        border: false,
                        items: [{
                            style: 'margin-top: -4px; border 0px;',
                            labelSeparator: '',
                            xtype:'textarea',
                            name: 'description',
                            hideLabel: true,
                            grow: false,
                            preventScrollbars:false,
                            anchor:'100% 100%',
                            emptyText: this.app.i18n._('Enter description')                            
                        }]
                    }),*/
                    new Tine.widgets.activities.ActivitiesPanel({
                        app: 'Timetracker',
                        showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'Timetracker',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })]
                }]
            },{
                title: this.app.i18n._('Access'),
                layout: 'fit',
                items: [this.getGrantsGrid()]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (! this.copyRecord) ? this.record.id : null,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
    
    getGrantsGrid: function() {
        if (! this.grantsGrid) {
            this.grantsStore =  new Ext.data.JsonStore({
                root: 'results',
                totalProperty: 'totalcount',
                // use account_id here because that simplifies the adding of new records with the search comboboxes
                id: 'account_id',
                fields: Tine.Timetracker.Model.TimeaccountGrant
            });
            
            var columns = [
                new Ext.ux.grid.CheckColumn({
                    header: this.app.i18n._('Book Own'),
                    dataIndex: 'bookOwnGrant',
                    tooltip: _('The grant to add Timesheets to this Timeaccount'),
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header: this.app.i18n._('View All'),
                    tooltip: _('The grant to view Timesheets of other users'),
                    dataIndex: 'viewAllGrant',
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header: this.app.i18n._('Book All'),
                    tooltip: _('The grant to add Timesheets for other users'),
                    dataIndex: 'bookAllGrant',
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header:this.app.i18n. _('Manage Clearing'),
                    tooltip: _('The grant to manage clearing of Timesheets'),
                    dataIndex: 'manageBillableGrant',
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header:this.app.i18n. _('Export'),
                    tooltip: _('The grant to export Timesheets of Timeaccount'),
                    dataIndex: 'exportGrant',
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header: this.app.i18n._('Manage All'),
                    tooltip: _('Includes all other grants'),
                    dataIndex: 'adminGrant',
                    width: 55
                })
            ];
            
            this.grantsGrid = new Tine.widgets.account.PickerGridPanel({
                selectType: 'both',
                title:  this.app.i18n._('Permissions'),
                store: this.grantsStore,
                hasAccountPrefix: true,
                configColumns: columns,
                selectAnyone: false,
                selectTypeDefault: 'group',
                recordClass: Tine.Tinebase.Model.Grant
            });
        }
        return this.grantsGrid;
    }
});

/**
 * Timetracker Edit Popup
 */
Tine.Timetracker.TimeaccountEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 500,
        name: Tine.Timetracker.TimeaccountEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Timetracker.TimeaccountEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

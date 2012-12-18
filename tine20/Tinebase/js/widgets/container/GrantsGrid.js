/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets', 'Tine.widgets.container');

/**
 * Container Grants grid
 * 
 * @namespace   Tine.widgets.container
 * @class       Tine.widgets.container.GrantsDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @constructor
 * @param {Object} config The configuration options.
 */
Tine.widgets.container.GrantsGrid = Ext.extend(Tine.widgets.account.PickerGridPanel, {

    /**
     * @cfg {Array} data of Tine.Tinebase.Model.Container
     * Container to manage grants for
     */
    grantContainer: null,
    
    /**
     * @cfg {Boolean}
     * always show the admin grant (default: false)
     */
    alwaysShowAdminGrant: false,
    
    /**
     * Tine.widgets.account.PickerGridPanel config values
     */
    selectType: 'both',
    selectTypeDefault: 'group',
    hasAccountPrefix: true,
    recordClass: Tine.Tinebase.Model.Grant,
    
    readGrantTitle: 'Read', // _('Read')
    readGrantDescription: 'The grant to read records of this container', // _('The grant to read records of this container')
    addGrantTitle: 'Add', // _('Add')
    addGrantDescription: 'The grant to add records to this container', // _('The grant to add records to this container')
    editGrantTitle: 'Edit', // _('Edit')
    editGrantDescription: 'The grant to edit records in this container', // _('The grant to edit records in this container')
    deleteGrantTitle: 'Delete', // _('Delete')
    deleteGrantDescription: 'The grant to delete records in this container', // _('The grant to delete records in this container')
    exportGrantTitle: 'Export', // _('Export')
    exportGrantDescription: 'The grant to export records from this container', // _('The grant to export records from this container')
    syncGrantTitle: 'Sync', // _('Sync')
    syncGrantDescription: 'The grant to synchronise records with this container', // _('The grant to synchronise records with this container')
    adminGrantTitle: 'Admin', // _('Admin')
    adminGrantDescription: 'The grant to administrate this container', // _('The grant to administrate this container')
    
    freebusyGrantTitle: 'Free Busy', // _('Free Busy')
    freebusyGrantDescription: 'The grant to access free busy information of events in this calendar', // _('The grant to access free busy information of events in this calendar')
    privateGrantTitle: 'Private', // _('Private')
    privateGrantDescription: 'The grant to access records marked as private in this container', // _('The grant to access records marked as private in this container')
    
    
    /**
     * @private
     */
    initComponent: function () {
        this.initColumns();
        
        this.recordDefaults = {
            readGrant: true,
            exportGrant: true,
            syncGrant: true
        };
        
        Tine.widgets.container.GrantsGrid.superclass.initComponent.call(this);
        
        this.getStore().on('update', this.onStoreUpdate, this);
        
        this.getStore().on('load', function(store) {
            store.each(function(r) {this.onStoreUpdate(store, r)}, this);
        }, this);
    },

    onStoreUpdate: function(store, record, operation) {
        if (this.alwaysShowAdminGrant || (this.grantContainer && this.grantContainer.type == 'shared')) {
            if (record.get('adminGrant')) {
                // set all grants and mask other checkboxes
                Ext.each(this.getColumnModel().columns, function(col, colIdx) {
                    var matches;
                    if ((matches = col.dataIndex.match(/^([a-z]+)Grant$/)) && matches[1] != 'admin') {
//                          //Ext.fly(this.getView().getCell(store.indexOf(record), colIdx)).mask('test');
                        record.set(col.dataIndex, true);
                    }
                }, this);
                
            } else {
                // make sure grants are not masked
            }
        }
    },
    
    /**
     * init grid columns
     */
    initColumns: function() {
        var grants = ['read', 'add', 'edit', 'delete', 'export', 'sync'];
        
        if (this.alwaysShowAdminGrant || (this.grantContainer && this.grantContainer.type == 'shared')) {
            grants.push('admin');
        }

        if (this.grantContainer) {
            // @todo move this to cal app when apps can cope with their own grant models
            if (this.grantContainer.application_id && this.grantContainer.application_id.name) {
                var isCalendar = (this.grantContainer.application_id.name == 'Calendar');
            } else {
                var calApp = Tine.Tinebase.appMgr.get('Calendar'),
                    calId = calApp ? calApp.id : 'none',
                    isCalendar = this.grantContainer.application_id === calId;
            }
            if (this.grantContainer.type == 'personal' && isCalendar) {
                grants.push('freebusy');
            }
            
            if (this.grantContainer.type == 'personal' && this.grantContainer.capabilites_private) {
                grants.push('private');
            }
        }
        
        this.configColumns = [];
        
        Ext.each(grants, function(grant) {
            this.configColumns.push(new Ext.ux.grid.CheckColumn({
                header: _(this[grant + 'GrantTitle']),
                tooltip: _(this[grant + 'GrantDescription']),
                dataIndex: grant + 'Grant',
                width: 55
            }));
        }, this);
    }
});

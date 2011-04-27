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
     * @cfg {Tine.Tinebase.container.models.container}
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
    
    /**
     * @private
     */
    initComponent: function () {
        this.initColumns();
        
        Tine.widgets.container.GrantsGrid.superclass.initComponent.call(this);
    },
    
    initColumns: function() {
        this.configColumns = [
            new Ext.ux.grid.CheckColumn({
                header: _('Read'),
                tooltip: _('The grant to read records of this container'),
                dataIndex: 'readGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Add'),
                tooltip: _('The grant to add records to this container'),
                dataIndex: 'addGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Edit'),
                tooltip: _('The grant to edit records in this container'),
                dataIndex: 'editGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Delete'),
                tooltip: _('The grant to delete records in this container'),
                dataIndex: 'deleteGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Export'),
                tooltip: _('The grant to export records from this container'),
                dataIndex: 'exportGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Sync'),
                tooltip: _('The grant to synchronise records with this container'),
                dataIndex: 'syncGrant',
                width: 55
            })
        ];
        
        if (this.alwaysShowAdminGrant || (this.grantContainer && this.grantContainer.type == 'shared')) {
            this.configColumns.push(new Ext.ux.grid.CheckColumn({
                header: _('Admin'),
                tooltip: _('The grant to administrate this container'),
                dataIndex: 'adminGrant',
                width: 55
            }));
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
                this.configColumns.push(new Ext.ux.grid.CheckColumn({
                    header: _('Free Busy'),
                    tooltip: _('The grant to access free busy information of events in this calendar'),
                    dataIndex: 'freebusyGrant',
                    width: 55
                }));
            }
            
            if (this.grantContainer.type == 'personal' && this.grantContainer.capabilites_private) {
                this.configColumns.push(new Ext.ux.grid.CheckColumn({
                    header: _('Private'),
                    tooltip: _('The grant to access records marked as private in this container'),
                    dataIndex: 'privateGrant',
                    width: 55
                }));
            }
        }
    }
});

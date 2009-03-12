/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
 Ext.ns('Tine', 'Tine.Setup');

Tine.Setup.ApplicationGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    recordClass: Tine.Setup.Model.Application,
    recordProxy: Tine.Setup.ApplicationBackend,
    
    evalGrants: false,
    defaultSortInfo: {field: 'name', dir: 'ASC'},
    
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'name'
    },
    
    initComponent: function() {
                
        this.gridConfig.columns = this.getColumns();
        //this.actionToolbarItems = this.getToolbarItems();
        //this.initDetailsPanel();
        
        Tine.Setup.ApplicationGridPanel.superclass.initComponent.call(this);
    },
    
    getColumns: function() {
        return  [
            {id: 'name',            width: 350, sortable: true, dataIndex: 'name',            header: this.app.i18n._("Name")}, 
            {id: 'status',          width: 70,  sortable: true, dataIndex: 'status',          header: this.app.i18n._("Enabled"),       renderer: this.enabledRenderer}, 
            {id: 'order',           width: 50,  sortable: true, dataIndex: 'order',           header: this.app.i18n._("Order")},
            {id: 'version',         width: 70,  sortable: true, dataIndex: 'version',         header: this.app.i18n._("Installed Version")},
            {id: 'current_version', width: 70,  sortable: true, dataIndex: 'current_version', header: this.app.i18n._("Available Version")},
            {id: 'install_status',  width: 70,  sortable: true, dataIndex: 'install_status',  header: this.app.i18n._("Status"),        renderer: this.upgradeStatusRenderer.createDelegate(this)},
            {id: 'depends',         width: 150,  sortable: true, dataIndex: 'depends',        header: this.app.i18n._("Depends on")}
        ];
    },
    
    initActions: function() {
        this.action_installApplications = new Ext.Action({
            text: this.app.i18n._('Install application'),
            handler: this.onAlterApplications,
            actionType: 'install',
            iconCls: 'setup_action_install',
            disabled: true,
            scope: this
        });
        
        this.action_uninstallApplications = new Ext.Action({
            text: this.app.i18n._('Uninstall application'),
            handler: this.onAlterApplications,
            actionType: 'uninstall',
            iconCls: 'setup_action_uninstall',
            disabled: true,
            scope: this
        });
        
        this.action_updateApplications = new Ext.Action({
            text: this.app.i18n._('Update application'),
            handler: this.onAlterApplications,
            actionType: 'update',
            iconCls: 'setup_action_update',
            disabled: true,
            scope: this
        });
        
        this.actions = [
            this.action_installApplications,
            this.action_uninstallApplications,
            this.action_updateApplications
        ];
        
        this.actionToolbar = new Ext.Toolbar({
            split: false,
            height: 26,
            items: this.actions.concat(this.actionToolbarItems)
        });
        
        this.contextMenu = new Ext.menu.Menu({
            items: this.actions.concat(this.contextMenuItems)
        });
        
    },
    
    initGrid: function() {
        Tine.Setup.ApplicationGridPanel.superclass.initGrid.call(this);
        this.selectionModel.purgeListeners();
        
        this.selectionModel.on('selectionchange', this.onSelectionChange, this);
        
    },
    
    onSelectionChange: function(sm) {
        var apps = sm.getSelections();
        var disabled = sm.getCount() == 0;
        
        var nIn = disabled, nUp = disabled, nUn = disabled;
        
        for(var i=0; i<apps.length; i++) {
            var status = apps[i].get('install_status');
            nIn = nIn || status == 'uptodate' || status == 'updateable';
            nUp = nUp || status == 'uptodate' || status == 'uninstalled';
            nUn = nUn || status == 'uninstalled';
        }
        
        this.action_installApplications.setDisabled(nIn);
        this.action_uninstallApplications.setDisabled(nUn);
        this.action_updateApplications.setDisabled(nUp);
    },
    
    onAlterApplications: function(btn, e) {
        var appNames = [];
        var apps = this.selectionModel.getSelections();
        
        for(var i=0; i<apps.length; i++) {
            appNames.push(apps[i].get('name'));
        }
        
        Ext.Ajax.request({
            scope: this,
            params: {
                method: 'Setup.' + btn.actionType + 'Applications',
                applicationNames: Ext.util.JSON.encode(appNames)
            },
            success: function() {
                this.store.load();
            },
            fail: function() {
                Ext.Msg.alert(this.app.i18n._('Shit'), this.app.i18n._('Where are the backup tapes'));
            }
        });
    },
    
    enabledRenderer: function(value) {
        return Tine.Tinebase.common.booleanRenderer(value == 'enabled');
    },
    
    upgradeStatusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    }
});
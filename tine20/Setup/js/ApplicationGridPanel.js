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
            {id: 'depends',         width: 150, sortable: true, dataIndex: 'depends',         header: this.app.i18n._("Depends on")}
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

    /**
     * on render
     * 
     * @param {} ct
     * @param {} position
     * 
     * TODO: select all rows and display modal box with 'install all apps' button
     */
    onRender: function(ct, position) {
        Tine.Setup.ApplicationGridPanel.superclass.onRender.call(this, ct, position);

        //this.selectionModel.selectAll.defer(500, this);
        //this.selectionModel.selectAll();
        //console.log(this.selectionModel);
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

        if (btn.actionType == 'uninstall') {
            // get user confirmation before uninstall
            Ext.Msg.confirm(this.app.i18n._('uninstall'), this.app.i18n._('Do you really want to uninstall the application(s)?'), function(confirmbtn, value) {
                if (confirmbtn == 'yes') {
                    this.alterApps(btn.actionType);
                }
            }, this);
        } else {
            this.alterApps(btn.actionType);
        }
    },
    
    /**
     * alter applications
     * 
     * @param {} type (uninstall/install/update)
     */
    alterApps: function(type) {

        var appNames = [];
        var apps = this.selectionModel.getSelections();
        
        for(var i=0; i<apps.length; i++) {
            appNames.push(apps[i].get('name'));
        }
        
        var msg = this.app.i18n.n_('Updating Application "{0}".', 'Updating {0} Applications.', appNames.length);
        msg = String.format(msg, appNames.length == 1 ? appNames[0] : appNames.length ) + ' ' + this.app.i18n._('This may take a while');
        
        
        var longLoadMask = new Ext.LoadMask(this.grid.getEl(), {
            msg: msg,
            removeMask: true
        });
        longLoadMask.show();
        
        Ext.Ajax.request({
            scope: this,
            params: {
                method: 'Setup.' + type + 'Applications',
                applicationNames: Ext.util.JSON.encode(appNames)
            },
            success: function() {
                this.store.load();
                longLoadMask.hide();
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
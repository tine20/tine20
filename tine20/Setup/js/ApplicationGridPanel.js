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

Tine.Setup.GridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    recordClass: Tine.Setup.Model.Application,
    recordProxy: Tine.Setup.ApplicationBackend,
    
    evalGrants: false,
    
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
                
        this.gridConfig.columns = this.getColumns();
        //this.actionToolbarItems = this.getToolbarItems();
        //this.initDetailsPanel();
        
        Tine.Setup.GridPanel.superclass.initComponent.call(this);
    },
    
    getColumns: function() {
        return  [
            {id: 'name',            width: 400, sortable: true, dataIndex: 'name',            header: this.app.i18n._("Name")}, 
            {id: 'status',          width: 70,  sortable: true, dataIndex: 'status',          header: this.app.i18n._("Enabled"),       renderer: this.enabledRenderer}, 
            {id: 'order',           width: 50,  sortable: true, dataIndex: 'order',           header: this.app.i18n._("Order")},
            {id: 'current_version', width: 70,  sortable: true, dataIndex: 'current_version', header: this.app.i18n._("Current ersion")},
            {id: 'version',         width: 70,  sortable: true, dataIndex: 'version',         header: this.app.i18n._("Version")},
            {id: 'upgrade_status',  width: 70,  sortable: true, dataIndex: 'version',         header: this.app.i18n._("Status"),        renderer: this.upgradeStatusRenderer.createDelegate(this)}
        ];
    },
    
    initActions: function() {
        this.actionToolbar = new Ext.Toolbar({});
        this.contextMenu = new Ext.menu.Menu({});
    },
    
    enabledRenderer: function(value) {
        return Tine.Tinebase.common.booleanRenderer(value == 'enabled');
    },
    
    upgradeStatusRenderer: function() {
        var app = arguments[2];
        
        if (app.get('current_version') == app.get('version')) {
            return this.app.i18n._('up to date');
        } else {
            return this.app.i18n._('please update');
        }
    }
});
/**
 * Tine 2.0
 * 
 * @package     Erp
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        activate different gridpanels if subapp from treepanel is clicked
 */
 
Ext.namespace('Tine.Erp');

// default mainscreen
Tine.Erp.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
	/*
    show: function() {
        if(this.fireEvent("beforeshow", this) !== false){
            this.setTreePanel();
            this.setContentPanel();
            this.setToolbar();
            this.updateMainToolbar();
            
            this.fireEvent('show', this);
        }
        return this;
    },*/
    setContentPanel: function() {
        if(!this.gridPanel) {
            var plugins = [];
            if (typeof(this.treePanel.getFilterPlugin) == 'function') {
                plugins.push(this.treePanel.getFilterPlugin());
            }
            
            this.gridPanel = new Tine[this.app.appName].ContractGridPanel({
                app: this.app,
                plugins: plugins
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
        this.gridPanel.store.load();
    }    
});

Tine.Erp.TreePanel = Ext.extend(Ext.tree.TreePanel,{
    initComponent: function() {
    	this.root = new Ext.tree.TreeNode({
            text: this.app.i18n._('Contracts'),
            cls: 'treemain',
            allowDrag: false,
            allowDrop: true,
            id: 'root',
            icon: false
        });
    	Tine.Erp.TreePanel.superclass.initComponent.call(this);
	}
});
    
// Contract model
Tine.Erp.ContractArray = Tine.Tinebase.Model.genericFields.concat([
    // contract only fields
    { name: 'id' },
    { name: 'number' },
    { name: 'title' },
    { name: 'description' },
    { name: 'status' },
    // tine 2.0 notes field
    { name: 'notes'},
    // linked contacts/accounts
    { name: 'customers'},
    { name: 'accounts'}
]);

/**
 * Contract record definition
 */
Tine.Erp.Contract = Tine.Tinebase.data.Record.create(Tine.Erp.ContractArray, {
    appName: 'Erp',
    modelName: 'Contract',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Contract', 'Contracts', n);
    recordName: 'Contracts',
    recordsName: 'Contracts',
    containerProperty: 'container_id',
    // ngettext('contracts list', 'contracts lists', n);
    containerName: 'contracts list',
    containersName: 'contracts lists'
});
Tine.Erp.Contract.getDefaultData = function() { 
    return {
        container_id: Tine.Erp.registry.get('DefaultContainer')
    };
};

/**
 * default contracts backend
 */
Tine.Erp.JsonBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Erp',
    modelName: 'Contract',
    recordClass: Tine.Erp.Contract
});

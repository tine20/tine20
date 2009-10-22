/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         activate different gridpanels if subapp from treepanel is clicked
 */
 
Ext.namespace('Tine.Sales');

/**
 * @namespace Tine.Sales
 * @class Tine.Sales.MainScreen
 * @extends Tine.Tinebase.widgets.app.MainScreen
 * MainScreen of the Sales Application <br>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * @constructor
 * Constructs mainscreen of the Sales application
 */
Tine.Sales.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
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

/**
 * @namespace Tine.Sales
 * @class Tine.Sales.TreePanel
 * @extends Ext.tree.TreePanel
 */ 
Tine.Sales.TreePanel = Ext.extend(Ext.tree.TreePanel,{
    initComponent: function() {
    	this.root = new Ext.tree.TreeNode({
            text: this.app.i18n._('Contracts'),
            cls: 'treemain',
            allowDrag: false,
            allowDrop: true,
            id: 'root',
            icon: false
        });
    	Tine.Sales.TreePanel.superclass.initComponent.call(this);
	}
});
    
/**
 * default contracts backend
 */
Tine.Sales.contractBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Sales',
    modelName: 'Contract',
    recordClass: Tine.Sales.Contract
});

/**
 * @namespace Tine.Sales
 * @class Tine.Sales.productBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Product Backend
 */ 
Tine.Sales.productBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Sales',
    modelName: 'Product',
    recordClass: Tine.Sales.Model.Product
});

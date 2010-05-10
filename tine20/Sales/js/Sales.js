/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * @namespace Tine.Sales
 * @class Tine.Sales.MainScreen
 * @extends Tine.widgets.MainScreen
 * MainScreen of the Sales Application <br>
 * <pre>
 * TODO         generalize this
 * </pre>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * @constructor
 * Constructs mainscreen of the Sales application
 */
Tine.Sales.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    
    activeContentType: 'Product',
    
    showCenterPanel: function() {
        
        // which content panel?
        var type = this.activeContentType;
        
        if (! this[type + 'GridPanel']) {
            this[type + 'GridPanel'] = new Tine[this.app.appName][type + 'GridPanel']({
                app: this.app,
                plugins: [this.getWestPanel().getContainerTreePanel().getFilterPlugin()]
            });
            
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this[type + 'GridPanel'], true);
        this[type + 'GridPanel'].store.load();
    },
    
    getCenterPanel: function() {
        // which content panel?
        
        // we always return product grid panel as a quick hack for saving filters
        return this['Product' + 'GridPanel'];
    },
    
    /**
     * sets toolbar in mainscreen
     */
    showNorthPanel: function() {
        var type = this.activeContentType;
        
        if (! this[type + 'ActionToolbar']) {
            this[type + 'ActionToolbar'] = this[type + 'GridPanel'].actionToolbar;
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this[type + 'ActionToolbar'], true);
    }
});

/**
 * @namespace Tine.Sales
 * @class Tine.Sales.TreePanel
 * @extends Tine.widgets.persistentfilter.PickerPanel
 * 
 * <pre>
 * TODO         generalize this
 * </pre>
 */ 
Tine.Sales.TreePanel = Ext.extend(Tine.widgets.persistentfilter.PickerPanel,{
    
    filter: [{field: 'model', operator: 'equals', value: 'Sales_Model_ProductFilter'}],
    
    initComponent: function() {
        
        this.filterMountId = 'Product';
        
        this.root = {
            id: 'root',
            leaf: false,
            expanded: true,
            children: [{
                text: this.app.i18n._('Products'),
                id: 'Product',
                iconCls: 'SalesProduct',
                expanded: true,
                children: [{
                    text: this.app.i18n._('All Products'),
                    id: 'allproducts',
                    leaf: true
                }]
            }, {
                text: this.app.i18n._('Contracts'),
                id : 'Contract',
                iconCls: 'SalesContracts',
                expanded: true,
                children: [{
                    text: this.app.i18n._('All Contracts'),
                    id: 'allcontracts',
                    leaf: true,
                    containerType: Tine.Tinebase.container.TYPE_SHARED,
                    container: Tine.Sales.registry.get('DefaultContainer')
                }]
            }]
        };
        
        this.initContextMenu();
        
        Tine.Sales.TreePanel.superclass.initComponent.call(this);
        
        this.on('click', function(node) {
            if (node.attributes.isPersistentFilter != true) {
                var contentType = node.getPath().split('/')[2];
                
                this.app.getMainScreen().activeContentType = contentType;
                this.app.getMainScreen().show();
            }
        }, this);
        
        this.on('contextmenu', function(node, event){
            this.ctxNode = node;
            if (node.id == 'allcontracts') {
                this.contextMenu.showAt(event.getXY());
            }
        }, this);
    },
    
    /**
     * @private
     */
    initContextMenu: function() {
        this.contextMenu = Tine.widgets.tree.ContextMenu.getMenu({
            nodeName: this.app.i18n._('All Contracts'),
            actions: ['grants'],
            scope: this,
            backend: 'Tinebase_Container',
            backendModel: 'Container'
        });
    },
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.Sales.TreePanel.superclass.afterRender.call(this);
        var type = this.app.getMainScreen().activeContentType;

        this.expandPath('/root/' + type + '/allproducts');
        this.selectPath('/root/' + type + '/allproducts');
    },
    
    /**
     * returns a filter plugin to be used in a grid
     * 
     * TODO     can we remove that?
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                    return [
                    ];
                }
            });
        }
        
        return this.filterPlugin;
    }
});
    
/**
 * default contracts backend
 */
Tine.Sales.contractBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Sales',
    modelName: 'Contract',
    recordClass: Tine.Sales.Model.Contract
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

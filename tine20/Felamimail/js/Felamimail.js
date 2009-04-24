/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Felamimail.js 7176 2009-03-05 12:26:08Z p.schuele@metaways.de $
 *
 * @todo        add context menu
 *              - add/rename/delete folders
 *              - change account settings
 *              - add new accounts
 * @todo        add multiple accounts
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * folder tree panel
 * 
 * @class Tine.Felamimail.TreePanel
 * @extends Ext.tree.TreePanel
 * 
 * @todo do we need something like this: http://examples.extjs.eu/?ex=treestate
 */
Tine.Felamimail.TreePanel = Ext.extend(Ext.tree.TreePanel, {
	
    /**
     * @cfg {application}
     */
    app: null,
    
	rootVisible: true,
	autoScroll: true,
    id: 'felamimail-tree',
	
    initComponent: function() {
    	
        this.loader = new Tine.Felamimail.TreeLoader({
            app: this.app
        });

        // set the root node
        this.root = new Ext.tree.AsyncTreeNode({
            text: 'default',
            globalName: '',
            backendId: 'default',
            draggable: false,
            allowDrop: false,
            expanded: false,
            leaf: false,
            id: '/'
            //iconCls: 'FelamimailMessage'
        });
                
        /*
        this.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/');
                _panel.selectPath('/');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);
        */

        /*
        treePanel.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            //_node.select();
            //_node.getOwnerTree().fireEvent('click', _node);
            //console.log(_node.attributes.contextMenuClass);
        });
        */
    	        
    	Tine.Felamimail.TreePanel.superclass.initComponent.call(this);
        
        this.on('click', function(node) {
            node.expand();
            console.log(node.attributes.globalName);
            this.expandPath('/' + node.attributes.globalName);
            this.selectPath('/' + node.attributes.globalName);
            this.filterPlugin.onFilterChange();
        }, this);
	},
        
    /**
     * @private
     */
    afterRender: function() {
        Tine.Felamimail.TreePanel.superclass.afterRender.call(this);
        /*
        var type = this.app.getMainScreen().activeContentType;

        this.expandPath('/root/' + type + '/allrecords');
        this.selectPath('/root/' + type + '/allrecords');
        */
    },
    
    /**
     * returns a filter plugin to be used in a grid
     * 
     * @todo add backend id
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                	console.log(scope);
                	var node = scope.getSelectionModel().getSelectedNode();
                    var nodeAttributes = (node) ? node.attributes : {};
                    return [
                        {field: 'folder',       operator: 'equals', value: nodeAttributes.globalName ? nodeAttributes.globalName : '' },
                        {field: 'backendId',    operator: 'equals', value: nodeAttributes.backendId  ? nodeAttributes.backendId : 'default' }
                    ];
                }
            });
        }
        
        return this.filterPlugin;
    }
});

Tine.Felamimail.TreeLoader = Ext.extend(Tine.widgets.data.TreeLoader, {
	
    method: 'Felamimail.searchFolders',

    /**
     * @private
     */
    initComponent: function() {
        this.filter = [];
        
        Tine.Felamimail.TreeLoader.superclass.initComponent.call(this);
    },
    
    /**
     * request data
     * 
     * @param {} node
     * @param {} callback
     * @private
     */
    requestData: function(node, callback){
    	// add globalName to filter
    	this.filter = [
            {field: 'backendId', operator: 'equals', value: node.attributes.backendId},
            {field: 'globalName', operator: 'equals', value: node.attributes.globalName}
        ];
    	
    	Tine.Felamimail.TreeLoader.superclass.requestData.call(this, node, callback);
    },
        
    /**
     * @private
     * 
     * @todo generalize this?
     */
    createNode: function(attr) {
    	node = {
    		id: attr.localName, // attr.globalName?
    		leaf: (attr.hasChildren != 1),
    		text: attr.localName,
    		globalName: attr.globalName,
    		backendId: attr.backendId
    	};
        return Tine.widgets.grid.PersistentFilterLoader.superclass.createNode.call(this, node);
    }
	
});

/**
 * default message backend
 */
Tine.Felamimail.recordBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Felamimail',
    modelName: 'Message',
    recordClass: Tine.Felamimail.Model.Message
});

/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Felamimail.js 7176 2009-03-05 12:26:08Z p.schuele@metaways.de $
 *
 * @todo        make it work!
 * @todo        add multiple accounts
 * @todo        use generic tree panel?
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
    id: 'felamimail-tree',
	
    initComponent: function() {
    	
        this.loader = new Tine.Felamimail.TreeLoader({
            app: this.app
        });

        console.log('init tree panel');
        
        /*
        this.loader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.backendId    = _node.attributes.backendId;
            _loader.baseParams.folderName   = _node.attributes.folderName;
        }, this);
        */
        
        // set the root node
        //var treeRoot = new Ext.tree.TreeNode({
        this.root = new Ext.tree.AsyncTreeNode({
            text: 'default',
            draggable: false,
            allowDrop: false,
            folderName: '',
            globalName: '',
            backendId: 'default',
            expanded: false,
            id: '/'
        });
        
        /*
        this.root = new Ext.tree.TreeNode({
            text: 'default',
            id: '/'        	
        });
        */
        
        /*
        for(var i=0; i<Tine.Felamimail.initialTree.length; i++) {
            treeRoot.appendChild(new Ext.tree.AsyncTreeNode(Tine.Felamimail.initialTree[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
            Tine.Felamimail.Email.show(_node);
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/root');
                _panel.selectPath('/root/account1');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);

        treePanel.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            //_node.select();
            //_node.getOwnerTree().fireEvent('click', _node);
            //console.log(_node.attributes.contextMenuClass);
        });
        */
    	        
    	Tine.Felamimail.TreePanel.superclass.initComponent.call(this);
        
    	/*
        this.on('click', function(node) {
            if (node.attributes.isPersistentFilter != true) {
                var contentType = node.getPath().split('/')[2];
                
                this.app.getMainScreen().activeContentType = contentType;
                this.app.getMainScreen().show();
            }
        }, this);
        */
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
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                getValue: function() {
                    //var nodeAttributes = scope.getSelectionModel().getSelectedNode().attributes || {};
                    return [
                        //{field: 'containerType', operator: 'equals', value: nodeAttributes.containerType ? nodeAttributes.containerType : 'all' },
                        //{field: 'container',     operator: 'equals', value: nodeAttributes.container ? nodeAttributes.container.id : null       },
                        //{field: 'owner',         operator: 'equals', value: nodeAttributes.owner ? nodeAttributes.owner.backendId : null        }
                    ];
                }
            });
        }
        
        return this.filterPlugin;
    }
});

Tine.Felamimail.TreeLoader = Ext.extend(Ext.tree.TreeLoader, {
	
    method: 'Felamimail.searchFolders',

    /**
     * @private
     */
    initComponent: function() {
        this.filter = [
            {field: 'model', operator: 'equals', value: model}
        ];
        
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
    	// @todo add node to filter
    	console.log(node);
    	
    	Tine.Felamimail.TreeLoader.superclass.requestData.call(this, node, callback);
    },
        
    /**
     * @private
     * 
     * @todo generalize this?
     */
    createNode: function(attr) {
    	/*
        var isPersistentFilter = !!attr.model && !!attr.filters,
        node = isPersistentFilter ? {
            isPersistentFilter: isPersistentFilter,
            text: attr.name,
            id: attr.id,
            leaf: attr.leaf === false ? attr.leaf : true,
            filter: attr
        } : attr;
        */
    	node = {
    		id: attr.localName,
    		leaf: (attr.hasChildren == 1),
    		text: attr.localName
    		//-- add more
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

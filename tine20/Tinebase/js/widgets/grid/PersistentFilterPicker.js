/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.widgets', 'Tine.widgets.grid');

Tine.widgets.grid.PersistentFilterPicker = Ext.extend(Ext.tree.TreePanel, {
    
    /**
     * @cfg {application}
     */
    app: null,
    
    autoScroll: true,
    border: false,
    
    initComponent: function() {
        this.root = {
            id: '/',
            name: _('My saved filters'),
            leaf: false,
            expanded: false
        };
        
        this.loader = new Tine.widgets.grid.PersistentFilterLoader({
            app: this.app
        });
        
        Tine.widgets.grid.PersistentFilterPicker.superclass.initComponent.call(this);
        
        this.on('click', function(node) {
            if (node.isLeaf()) {
                node.select();
                this.onFilterSelect();
            } else {
                node.expand();
                return false;
            }
        }, this);
        
        this.on('contextmenu', this.onContextMenu, this);
    },
    
    /**
     * load grid from saved filter
     * 
     * NOTE: As all filter plugins add their data on the stores beforeload event
     *       we need a litte hack to only present a filterid.
     *       
     *       When a filter is selected, we register ourselve as latest beforeload,
     *       remove all filter data and paste our filter id. To ensure we are
     *       always the last listener, we directly remove the listener afterwards
     */
    onFilterSelect: function() {
        var store = this.app.getMainScreen().getContentPanel().store;
        store.on('beforeload', this.storeOnBeforeload, this);
        store.load();
        this.app.getMainScreen().getTreePanel().activate(0);
    },
    
    storeOnBeforeload: function(store, options) {
        options.params.filter = this.getSelectionModel().getSelectedNode().id;
        store.un('beforeload', this.storeOnBeforeload, this);
    },
    
    onContextMenu: function(node, e) {
        var menu = new Ext.menu.Menu({
            items: [{
                text: _('Delete Filter'),
                iconCls: 'action_delete',
                scope: this,
                handler: function() {
                    Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to delete the Filter "{0}"?'), node.text), function(_btn){
                        if ( _btn == 'yes') {
                            Ext.MessageBox.wait(_('Please wait'), String.format(_('Deleting Filter "{0}"' ), this.containerName , node.text));
                            
                            Ext.Ajax.request({
                                params: {
                                    method: 'Tinebase_PersistentFilter.delete',
                                    filterId: node.id
                                },
                                scope: this,
                                success: function(){
                                    node.unselect();
                                    node.remove();
                                    Ext.MessageBox.hide();
                                }
                            });
                        }
                    }, this);
                }
            }]
        });
        menu.showAt(e.getXY());
    }
    
});

Tine.widgets.grid.PersistentFilterLoader = Ext.extend(Ext.tree.TreeLoader, {
    /**
     * @cfg {Number} how many chars of the containername to display
     */
    displayLength: 25,
    
    /**
     * @cfg {application}
     */
    app: null,
    
    // trick parent
    url: true,
    
    /**
     * @private
     */
    requestData: function(node, callback){
        var gridPanel = this.app.getMainScreen().getContentPanel();
        
        var model = gridPanel.store.reader.recordType.getMeta('appName') + '_Model_' + gridPanel.store.reader.recordType.getMeta('modelName') + 'Filter';
        
        if(this.fireEvent("beforeload", this, node, callback) !== false){
            
            this.transId = Ext.Ajax.request({
                params: {
                    method: 'Tinebase_PersistentFilter.search',
                    filter: Ext.util.JSON.encode([
                        {field: 'model', operator: 'equals', value: model}
                    ])
                },
                success: this.handleResponse,
                failure: this.handleFailure,
                scope: this,
                argument: {callback: callback, node: node}
            });
        } else {
            // if the load is cancelled, make sure we notify
            // the node that we are done
            if(typeof callback == "function"){
                callback();
            }
        }
    },
    
    processResponse : function(response, node, callback){
        var data = Ext.util.JSON.decode(response.responseText);
        var o = data.results;
        
        try {
            node.beginUpdate();
            for(var i = 0, len = o.length; i < len; i++){
                var n = this.createNode(o[i]);
                if(n){
                    node.appendChild(n);
                }
            }
            node.endUpdate();
            if(typeof callback == "function"){
                callback(this, node);
            }
        }catch(e){
            this.handleFailure(response);
        }
    },
    
    /**
     * @private
     */
    createNode: function(attr) {
        node = {
            text: attr.name,
            id: attr.id,
            //cls: 'file',
            leaf: attr.leaf === false ? attr.leaf : true,
            filter: attr
        };
        
        return Tine.widgets.grid.PersistentFilterLoader.superclass.createNode.call(this, node);
    }
 });

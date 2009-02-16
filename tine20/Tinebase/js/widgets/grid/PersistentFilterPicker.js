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
     * @cfg {Tine.widgets.app.GridPanel}
     */
    gridPanel: null,
    
    autoScroll: true,
    border: false,
    
    initComponent: function() {
        this.root = {
            id: '/',
            text: _('My saved filters'),
            leaf: false,
            expanded: false
        };
        
        this.loader = new Tine.widgets.grid.PersistentFilterLoader({
            gridPanel: this.gridPanel
        });
        
        this.listeners = {
            scope: this,
            click: function(node) {
                node.select();
                this.onFilterSelect();
            }
        };
        
        Tine.widgets.grid.PersistentFilterPicker.superclass.initComponent.call(this);
    },
    
    onFilterSelect: function() {
        
    }
    
});

Tine.widgets.grid.PersistentFilterLoader = Ext.extend(Ext.tree.TreeLoader, {
    /**
     * @cfg {Number} how many chars of the containername to display
     */
    displayLength: 25,
    
    /**
     * @cfg {Tine.widgets.app.GridPanel}
     */
    gridPanel: null,
    
    /**
     * @private
     */
    load: function(node, callback) {
        var model = this.gridPanel.store.reader.recordType.getMeta('appName') + '_Model_' + this.gridPanel.store.reader.recordType.getMeta('modelName');
        
        Ext.Ajax.request({
            params: {
                method: 'Tinebase_PersistentFilter.search',
                filter: Ext.util.JSON.encode([
                    {field: 'model', operator: 'equals', value: model}
                ])
            },
            success: function() {
                console.log(arguments);
                callback.call(this);
            }
        });
    },
    
    /**
     * @private
     *
    createNode: function(attr) {
        
        // map attributes from Tinebase_Container to attrs from ExtJS
        if (attr.name) {
            if (!attr.account_grants.account_id){
                // temporary workaround, for a Zend_Json::encode problem
                attr.account_grants = Ext.util.JSON.decode(attr.account_grants);
            }
            attr = {
                containerType: 'singleContainer',
                container: attr,
                text: attr.name,
                id: attr.id,
                cls: 'file',
                leaf: true
            };
        } else if (attr.accountDisplayName) {
            attr = {
                containerType: Tine.Tinebase.container.TYPE_PERSONAL,
                text: attr.accountDisplayName,
                id: attr.accountId,
                cls: 'folder',
                leaf: false,
                owner: attr
            };
        }
                
        attr.qtip = Ext.util.Format.htmlEncode(attr.text);
        attr.text = Ext.util.Format.htmlEncode(Ext.util.Format.ellipsis(attr.text, this.displayLength));
        
        // apply baseAttrs, nice idea Corey!
        if(this.baseAttrs){
            Ext.applyIf(attr, this.baseAttrs);
        }
        if(this.applyLoader !== false){
            attr.loader = this;
        }
        if(typeof attr.uiProvider == 'string'){
           attr.uiProvider = this.uiProviders[attr.uiProvider] || eval(attr.uiProvider);
        }
        return(attr.leaf ?
                        new Ext.tree.TreeNode(attr) :
                        new Ext.tree.AsyncTreeNode(attr));
    }*/
 });

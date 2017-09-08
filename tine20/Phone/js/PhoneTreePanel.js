
Ext.namespace('Tine.Phone');

Tine.Phone.PhoneTreePanel = Ext.extend(Ext.tree.TreePanel, {

    // private
    id: 'phone-tree',
    rootVisible: false,
    border: false,
    collapsible: true,
    baseCls: 'ux-arrowcollapse',
    
    // state stuff
    stateful: true,
    stateId: 'phone-phonetreepanel',
    stateEvents: ['collapse', 'expand', 'click'],
    
    /**
     * holds the users' phones
     * @type {Ext.data.JsonStore}
     */
    store: null,
    
    /**
     * last selected node
     * 
     * @type {Ext.tree.TreeNode} 
     */
    ctxNode: null,
    
    /**
     * initializes the component
     */
    initComponent: function() {
        Tine.Phone.PhoneTreePanel.superclass.initComponent.call(this, arguments);
        
        this.setRootNode(new Ext.tree.TreeNode({
            cls: 'treemain',
            allowDrag: false,
            allowDrop: true,
            id: 'phone-root',
            icon: false
        }));
        
        // create context menu
        var contextMenu = new Ext.menu.Menu({
            items: [
                this.grid.action_editPhoneSettings
            ],
            listeners: {
                scope: this,
                hide: function() {this.ctxNode = null;}
            }
        });
        
        this.on('contextmenu', function(node, event){
            this.ctxNode = node;
            if (node.id != 'phone-root') {
                contextMenu.showAt(event.getXY());
            }
        }, this);

        this.on('click', function(node, event) {
            this.onNodeClick(node, event);
        }, this);
        
        this.store = this.app.userPhonesStore;
    },
        
    /**
     * is called on single click on an node of the phoneTreePanel
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {} event
     */
    onNodeClick: function(node, event) {
        this.ctxNode = node;
        this.grid.store.load();
    },
    
    /**
     * returns the currently selected node
     * 
     * @return {Ext.tree.TreeNode}
     */
    getActiveNode: function() {
        return this.ctxNode || this.getSelectionModel().getSelectedNode();
    },
    
    /**
     * updates the phone tree panel after an crud action and on load
     */
    updateTree: function() {
        // remove all children first
        var rootNode = this.getRootNode();
        rootNode.eachChild(function(child) {
            this.removeChild(child);
        });

        // add phones from store to tree menu
        this.store.each(function(record) {
            var label = (record.data.description == '') 
               ? record.data.macaddress 
               : Ext.util.Format.ellipsis(Ext.util.Format.htmlEncode(record.data.description), 30);
            var node = new Ext.tree.TreeNode({
                id: record.id,
                record: record,
                text: label,
                iconCls: 'PhoneIconCls',
                qtip: Ext.util.Format.htmlEncode(record.data.description),
                leaf: true
            });
            rootNode.appendChild(node);
        }, this);
    },

    /**
     * @see Ext.Component
     */
    getState: function() {
        var root = this.getRootNode();
        
        var state = {
            selected: this.grid.ctxNode ? this.grid.ctxNode.id : null
        };
        
        return state;
    },
    
    
    // private
    initState : function(){
        if(Ext.state.Manager){
            var id = this.getStateId();
            if(id){
                var state = Ext.state.Manager.get(id);
                if (state) {
                    if (this.fireEvent('beforestaterestore', this, state) !== false){
                        this.applyState(Ext.apply({}, state));
                        this.fireEvent('staterestore', this, state);
                    }
                } else {
                    (function(){
                        var root = this.getRootNode();
                        if (root.firstChild) {
                            root.firstChild.select();
                        }
                    }).defer(100, this);
                }
            }
        }
    },
    
    /**
     * applies state to cmp
     * 
     * @param {Object} state
     */
    applyState: function(state) {
        (function() {
            var node = state.selected ? this.getNodeById(state.selected) : this.getRootNode().firstChild;
            if (node) {
                node.select();
            }
        }).defer(100, this);
    }
});
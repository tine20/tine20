/*
 * Tine 2.0
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.RequestTracker');

/**
 * @class Tine.RequestTracker.TreePanel
 * @extends Ext.tree.TreePanel
 * @constructor
 */
Tine.RequestTracker.TreePanel = Ext.extend(Ext.tree.TreePanel, {
    border: false,
    
    initComponent: function() {
        this.root = {
            id: 'queues',
            text: this.app.i18n._('All Queues'),
            leaf: false,
            expanded: true
        };
        
        this.loader = new Ext.tree.TreeLoader({
            url: 'index.php',
            baseParams: {
                jsonKey: Tine.Tinebase.registry.get('jsonKey'),
                requestType: 'JSON',
                method: 'RequestTracker.searchQueues'
            },
            baseAttrs: {
                leaf: true,
                iconCls: 'x-tree-node-icon'
            }
        });
        Tine.RequestTracker.TreePanel.superclass.initComponent.call(this);
    },
    
    onRender: function(ct, position) {
        Tine.RequestTracker.TreePanel.superclass.onRender.call(this, ct, position);
        this.getRootNode().on('expand', function(rn){rn.select()});
    },
    
    /**
     * returns a filter plugin to be used in a grid
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            var scope = this;
            this.filterPlugin = new Tine.widgets.grid.FilterPlugin({
                
                /**
                 * gets value of this container filter
                 */
                getValue: function() {
                    var value = [];
                    var node = scope.getSelectionModel().getSelectedNode();
                    if (node && node.id !== 'queues') {
                        value.push({
                            field: 'queue',
                            operator: 'equals',
                            value: node.id
                        });
                    }
                    
                    return value;
                },
                
                /**
                 * sets the selected container (node) of this tree
                 * 
                 * @param {Array} all filters
                 */
                setValue: function(filters) {
                    for (var i=0; i<filters.length; i++) {
                        if (filters[i].field == 'Queue') {
                            console.log(filters[i].value);
                        }
                    }
                }
            });
            
            this.on('click', function(node){
                this.filterPlugin.onFilterChange();
            }, this);
        }
        
        return this.filterPlugin;
    }
});

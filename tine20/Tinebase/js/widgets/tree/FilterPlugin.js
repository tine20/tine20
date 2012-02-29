/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */
Ext.ns('Tine.widgets', 'Tine.widgets.tree');

/**
 * filter plugin for container tree
 * 
 * @namespace Tine.widgets.tree
 * @class     Tine.widgets.tree.FilterPlugin
 * @extends   Tine.widgets.grid.FilterPlugin
 */
Tine.widgets.tree.FilterPlugin = Ext.extend(Tine.widgets.grid.FilterPlugin, {
    /**
     * @cfg {Tree Panel} treePanel (required)
     */
    treePanel: null,
    
    /**
     * @cfg field
     * @type String
     */
    field: 'container_id',

    /**
     * @cfg nodeAttributeField
     * @type String
     */
    nodeAttributeField: 'container',
    
    /**
     * @cfg singleNodeOperator
     * @type String
     */
    singleNodeOperator: 'equals',
    
    /**
     * @cfg selectNodes
     * @type Boolean
     */
    selectNodes: true,
    
    /**
     * get container filter object
     * 
     * @return {Object}
     */
    getFilter: function() {
        var filter = {field: this.field},
            sm = this.treePanel.getSelectionModel(),
            multiSelection = typeof sm.getSelectedNodes == 'function',
            selection = multiSelection ? sm.getSelectedNodes() : [sm.getSelectedNode()];
        
        filter.operator = multiSelection ? 'in' : this.singleNodeOperator;
            
        var values = [];
        Ext.each(selection, function(node) {
            if (node) {
                values.push(node.attributes[this.nodeAttributeField]);
            }
        }, this);
        
        filter.value = Ext.isEmpty(values) ? '' : filter.operator === 'in' ? values : values[0];
        return filter;
    },
    
    /**
     * gets value of this container filter
     */
    getValue: function() {
        // only return values if gridFilter mode
        if (this.treePanel.filterMode !== 'gridFilter') {
            return null;
        }
        
        return this.getFilter();
    },
    
    /**
     * sets the selected container (node) of this tree
     * 
     * @param {Array} all filters
     */
    setValue: function(filters) {
        if (! this.selectNodes) {
            return null;
        }

        var sm = this.treePanel.getSelectionModel();
        sm.clearSelections(true);
        
        // use first OR panel in case of filterPanel
        Ext.each(filters, function(filterData) {
            if (filterData.condition && filterData.condition == 'OR') {
                filters = filterData.filters[0].filters;
                return false;
            }
        }, this);
        
        Ext.each(filters, function(filter) {
            if (filter.field !== this.field || Ext.isEmpty(filter.value)) {
                return;
            }
            
            this.lastFocusEl = document.activeElement;
            this.treePanel.getSelectionModel().suspendEvents();
            this.selectValue(filter.value);
        }, this);
    },
    
    /**
     * select tree node(s)
     * 
     * @param {String} value
     */
    selectValue: function(value) {
        var values = Ext.isArray(value) ? value : [value];
        Ext.each(values, function(value, idx) {
            if (Ext.isString(value) && ! value.path) {
                value = values[idx] = {path: value};
            }
            
            var treePath = this.treePanel.getTreePath(value.path);
            this.selectPath.call(this.treePanel, treePath, null, function() {
                // mark this expansion as done and check if all are done
                value.isExpanded = true;
                var allValuesExpanded = true;
                Ext.each(values, function(v) {
                    allValuesExpanded &= v.isExpanded;
                }, this);
                
                if (allValuesExpanded) {
                    this.treePanel.getSelectionModel().resumeEvents();
                    try {
                        if (this.lastFocusEl) {
                            Ext.fly(this.lastFocusEl).focus(10);
                        }
                    } catch (e) {}
                }
            }.createDelegate(this), true);
        }, this);
    },
    
    /**
     * Selects the node in this tree at the specified path. A path can be retrieved from a node with {@link Ext.data.Node#getPath}
     * @param {String} path
     * @param {String} attr (optional) The attribute used in the path (see {@link Ext.data.Node#getPath} for more info)
     * @param {Function} callback (optional) The callback to call when the selection is complete. The callback will be called with
     * (bSuccess, oSelNode) where bSuccess is if the selection was successful and oSelNode is the selected node.
     */
    selectPath : function(path, attr, callback, keep){
        attr = attr || 'id';
        var keys = path.split(this.pathSeparator),
            v = keys.pop();
        if(keys.length > 1){
            var f = function(success, node){
                if(success && node){
                    var n = node.findChild(attr, v);
                    if(n){
                        n.getOwnerTree().getSelectionModel().select(n, false, keep);
                        if(callback){
                            callback(true, n);
                        }
                    }else if(callback){
                        callback(false, n);
                    }
                }else{
                    if(callback){
                        callback(false, n);
                    }
                }
            };
            this.expandPath(keys.join(this.pathSeparator), attr, f);
        }else{
            this.root.select();
            if(callback){
                callback(true, this.root);
            }
        }
    }
});

/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */
Ext.ns('Tine.Filemanager');

/**
 * filter plugin for container tree
 * 
 * @namespace Tine.widgets.tree
 * @class     Tine.Filemanager.PathFilterPlugin
 * @extends   Tine.widgets.grid.FilterPlugin
 */
Tine.Filemanager.PathFilterPlugin = Ext.extend(Tine.widgets.tree.FilterPlugin, {
    
    /**
     * select tree node(s)
     * 
     * @param {String} value
     */
    selectValue: function(value) {

        var values = Ext.isArray(value) ? value : [value];
        Ext.each(values, function(value) {
            var treePath = '/';
     
            treePath = this.treePanel.getTreePath(value);
            
            this.selectPath.call(this.treePanel, treePath, null, function() {
                // mark this expansion as done and check if all are done
                value.isExpanded = true;
                var allValuesExpanded = true;
                Ext.each(values, function(v) {
                    allValuesExpanded &= v.isExpanded;
                }, this);
                
                if (allValuesExpanded) {
                    this.treePanel.getSelectionModel().resumeEvents();
                }
            }.createDelegate(this), true);
        }, this);
    }
   
});

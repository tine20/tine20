/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class     Tine.Calendar.CalendarSelectTreePanel
 * @extends   Tine.widgets.container.TreePanel
 * 
 * Main Calendar Select Panel
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Tine.Calendar.CalendarSelectTreePanel = Ext.extend(Tine.widgets.container.TreePanel, {
    //stateEvents: ['expandnode', 'collapsenode', 'checkchange'],
    //stateful: true,
    //stateId: 'cal-calendartree-containers',
    recordClass: Tine.Calendar.Model.Event,
    
    initComponent: function() {
        this.selModel = new Ext.ux.tree.CheckboxSelectionModel({
            activateLeafNodesOnly : true,
            optimizeSelection: true
        });
        
        this.loader = new Tine.widgets.container.TreeLoader({
            appName: this.appName,
            displayLength: this.displayLength,
            
            /**
             * draw colored bullets before cal icon
             */
            inspectCreateNode: function(attr) {
                attr.listeners = {
                    append: function(tree, node, appendedNode, index) {
                        if (appendedNode.attributes.containerType == 'singleContainer') {
                            var container = appendedNode.attributes.container;
                            // dynamically initialize colorMgr if needed
                            if (! Tine.Calendar.colorMgr) {
                                Tine.Calendar.colorMgr = new Tine.Calendar.ColorManager({});
                            }
                            var colorSet = Tine.Calendar.colorMgr.getColor(container);
                            appendedNode.ui.render = appendedNode.ui.render.createSequence(function() {
                                //Ext.DomHelper.insertAfter(this.iconNode, {tag: 'span', html: '&nbsp;&bull;&nbsp', style: {color: colorSet.color}})
                                Ext.DomHelper.insertAfter(this.iconNode, {tag: 'span', html: '&nbsp;&#9673;&nbsp', style: {color: colorSet.color}})
                                //Ext.DomHelper.insertAfter(this.iconNode, {tag: 'span', html: '&nbsp;&#x2b24;&nbsp', style: {color: colorSet.color}})
                            }, appendedNode.ui);
                        }
                    }
                };
            }
            
        });
        
        this.supr().initComponent.call(this);
    },
    
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);

        //Ext.each(this.expandPaths, function(path) {
        //    this.expandPath(path);
        //    console.log(path);
        //}, this);
        
        this.selectPath('/root/all/user');
    },
    
    applyState: function(state) {
        this.expandPaths = state;
    },
    
    /**
     * returns a filter plugin to be used in a grid
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            this.filterPlugin = new Tine.widgets.container.TreeFilterPlugin({
                scope: this,
                node2Filter: function(node) {
                    if (node.attributes.containerType.match(/^resource/)) {
                        
                    } else {
                        return Tine.widgets.container.TreeFilterPlugin.prototype.node2Filter.call(this, node);
                    }
                }
            });
            
            this.getSelectionModel().on('selectionchange', function(sm, node){
                this.filterPlugin.onFilterChange();
            }, this);
        }
        
        return this.filterPlugin;
    },
    
    getState: function() {
        var checkedPaths = [];
        Ext.each(this.getChecked(), function(node) {
            checkedPaths.push(node.getPath());
        }, this);
        
        return checkedPaths;
    }
});
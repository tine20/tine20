/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.FilterPanel = Ext.extend(Tine.widgets.grid.PersistentFilterPicker, {
    filter: [{field: 'model', operator: 'equals', value: 'Calendar_Model_EventFilter'}],
    
    storeOnBeforeload: function(store, options) {
        options.params.filter = this.getSelectionModel().getSelectedNode().attributes.filters;
        
        var cp = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
        var period = cp.getCalendarPanel(cp.activeView).getTopToolbar().getPeriod();
        
        // remove all existing period filters
        Ext.each(options.params.filter, function(filter) {
            if (filter.field === 'period') {
                options.params.filter.remove(filter);
                return false;
            }
        }, this);
        
        options.params.filter.push({field: 'period', operator: 'within', value: period});
        
        store.un('beforeload', this.storeOnBeforeload, this);
    }
});

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
    ddGroup: 'cal-event',
    // disabled for the moment, as ftb can't deal with n containers yet
    //allowMultiSelection: true,
    filterMode: 'filterToolbar',
    
    initComponent: function() {
        this.filterPlugin = new Tine.widgets.container.TreeFilterPlugin({
            treePanel: this,
            /**
             * overwritten to deal with calendars special filter approach
             * 
             * @return {Ext.Panel}
             */
            getGridPanel: function() {
                return Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
            }
        });
        
        this.supr().initComponent.call(this);
    },
    
//    initComponent: function() {
//        this.selModel = new Ext.tree.MultiSelectionModel({});
//        /*
//        this.selModel = new Ext.ux.tree.CheckboxSelectionModel({
//            activateLeafNodesOnly : true,
//            optimizeSelection: true
//        });
//        */
//        
//        /*
//        // inject resources tree node
//        this.extraItems = [{
//            text: String.format(this.app.i18n._('Resources {0}'), this.containersName),
//            cls: 'file',
//            id: 'allResources',
//            children: null,
//            leaf: null
//        }];
//        */
//        
//        this.supr().initComponent.call(this);
//        
//        //this.loader.processResponse = this.processResponse.createDelegate(this);
//    },
//    
    /*
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);

        this.selectContainerPath('/personal/' + Tine.Tinebase.registry.get('currentAccount').accountId);
    },
    */
    /*
    applyState: function(state) {
        this.expandPaths = state;
    },
    */
    
    /**
     * returns a filter plugin to be used in a grid
     *
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            this.filterPlugin = new Tine.widgets.container.TreeFilterPlugin({
                treePanel: this,
                node2Filter: function(node) {
                    var id = node.attributes.id;
                    
                    if (id.match(/resource/i)) {
                        if (id == 'allResources') {
                            return {field: 'attender', operator: 'specialNode', value: 'allResources'};
                        } else {
                            var rid = node.attributes.id.split('_')[1];
                            return {field: 'attender', operator: 'equals', value: {user_type: 'resource', user_id: rid}};
                        }
                        
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
    */
    
    /*
    getState: function() {
        var checkedPaths = [];
        Ext.each(this.getChecked(), function(node) {
            checkedPaths.push(node.getPath());
        }, this);
        
        return checkedPaths;
    },
    */
    
    /**
     * adopt attr
     * 
     * @param {Object} attr
     */
    onBeforeCreateNode: function(attr) {
        this.supr().onBeforeCreateNode.apply(this, arguments);
        
        if (attr.container) {
            attr.container.capabilites_private = true;
        }
        
        /*
        if (attr.id && attr.id.match(/resource/i)) {
            // don't add colors to resources yet
            return;
        }
        */
        
        attr.listeners = {
            append: function(tree, node, appendedNode, index) {
                if (appendedNode.leaf) {
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
    
    /**
     * returns params for async request
     * 
     * @param {Ext.tree.TreeNode} node
     * @return {Object}
     *
    onBeforeLoad: function(node) {
        if (node.attributes.id.match(/resource/i)) {
            return {
                method: 'Calendar.searchResources',
                filter: [{field: 'name', operator: 'contains', value: ''}]
            };
        }
        
        return this.supr().onBeforeLoad.apply(this, arguments);
    },
    */
    
    /*
    processResponse: function(response, node, callback, scope) {
        if (node.attributes.id.match(/resource/i)) {
            var o = response.responseData = response.responseData || Ext.decode(response.responseText);
            Ext.each(o.results, function(resource) {
                // fake grants
                resource.account_grants = {
                    account_id: Tine.Tinebase.registry.get('currentAccount').accountId,
                    readGrant: true
                };
                resource.leaf = true;
                
                // prefix id
                resource.id = 'resource_' + resource.id;
            });
        }
        
        return Tine.widgets.tree.Loader.prototype.processResponse.apply(this.loader, arguments);
    }
    */
});

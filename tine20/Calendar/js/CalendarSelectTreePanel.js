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
        
        this.loader = new Tine.Calendar.CalendarSelectTreeLoader({
            appName: this.appName
        });
        
        // inject resources tree node
        this.extraItems = [{
            text: String.format(this.app.i18n._('Resources {0}'), this.containersName),
            cls: 'file',
            id: 'allResources',
            children: null,
            leaf: null
        }];
        
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
    
    getState: function() {
        var checkedPaths = [];
        Ext.each(this.getChecked(), function(node) {
            checkedPaths.push(node.getPath());
        }, this);
        
        return checkedPaths;
    }
});


/**
 * @namespace   Tine.Calendar.Calendar
 * @class       Tine.Calendar.CalendarSelectTreeLoader
 * @extends     Tine.widgets.container.TreeLoader
 */
Tine.Calendar.CalendarSelectTreeLoader = Ext.extend(Tine.widgets.container.TreeLoader, {
    
    /**
     * draw colored bullets before cal icon
     */
    inspectCreateNode: function(attr) {
        if (attr.id.match(/resource/i)) {
            // don't add colors to resources yet
            return;
        }
        
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
    },
    
    onBeforeLoad: function(loader, node) {
        // route resources requests to calendar json frontend
        if (node.attributes.id.match(/resource/i)) {
            loader.baseParams.method = 'Calendar.searchResources';
            loader.baseParams.filter = [{field: 'name', operator: 'contains', value: ''}];
            loader.baseParams.paging = {};
        } else {
            Tine.Calendar.CalendarSelectTreeLoader.superclass.onBeforeLoad.call(this, loader, node);
        }
    },
    
    processResponse: function(response, node, callback, scope) {
        // convert resources responses into old treeLoader structure
        var json = response.responseText;
        var o = response.responseData || Ext.decode(json);
        if (o.totalcount) {
            
            Ext.each(o.results, function(resource) {
                // fake grants
                resource.account_grants = {
                    account_id: Tine.Tinebase.registry.get('currentAccount').accountId,
                    readGrant: true
                };
                
                // prefix id
                resource.id = 'resource_' + resource.id;
            });
            
            // take results part as response only
            response.responseData = o.results;
        }
        
        return Tine.Calendar.CalendarSelectTreeLoader.superclass.processResponse.apply(this, arguments);
    }
});
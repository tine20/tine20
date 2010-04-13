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
        options.params.filter = this.getSelectionModel().getSelectedNode().attributes.filter.filters;
        
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
});

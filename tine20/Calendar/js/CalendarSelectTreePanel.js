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
    },
    
    /**
     * called when events are droped on a calendar node
     * 
     * NOTE: atm. event panels only allow d&d for single events
     * 
     * @private
     * @param  {Ext.Event} dropEvent
     * @return {Boolean}
     */
    onBeforeNodeDrop: function(dropEvent) {
        var containerData = dropEvent.target.attributes,
            selection = dropEvent.data.selections,
            abort = false;
        
        // @todo move this to dragOver
        if (! containerData.account_grants.addGrant) {
            abort = true;
        }
        
        Ext.each(selection, function(event) {
            // origin container will be moved if user is organizer of event
            // otherwise only displycontainer will be moved
            
            // rethink: if the user has deleteGrant to orign calendar we also 
            // could move orign without the user being organizer...
            // we might need to have a look if the user 'sees' orign or display cal
            
            abort = true;
        }, this);
        
        if (abort) {
            return false;
        }
        
        // send request
    }
});

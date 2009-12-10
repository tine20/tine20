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
 * @class     Tine.Calendar.MainScreenLeftPanel
 * @extends   Ext.Panel
 * 
 * Left Calendar Panel including Tree and DatePicker<br>
 * @todo add d&d support to tree (change calendar)
 * @todo why the hack is the strech option not working???
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Tine.Calendar.MainScreenLeftPanel = Ext.extend(Ext.Panel, {
    border: false,
    //layout: 'vbox',
    //align: 'stretch',
    layout: 'border',
    cls: 'cal-tree',
    defaults: {
        border: false
    },
    
    initComponent: function() {
        
        this.calSelector = new Tine.Calendar.CalendarSelectTreePanel({
            region: 'center',
            width: 200,
            app: Tine.Tinebase.appMgr.get('Calendar')
        });
        
        this.calSelector.getSelectionModel().on('selectionchange', function(sm, node) {
            var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
            if (contentPanel) {
                contentPanel.refresh();
            }
        }, this);
        
        this.items = [this.calSelector, /*{
            xtype:'spacer',
            flex:1
        },*/ {
            region: 'south',
            split: true,
            collapsible: true,
            collapseMode: 'mini',
            height: 190,
            cls: 'cal-datepicker-background',
            layout: 'hbox',
            layoutConfig: {
                align:'middle'
            },
            defaults: {border: false},
            items: [{
                flex: 1
            }, new Ext.DatePicker({
                flex: 0,
                width: 200,
                id :'cal-mainscreen-minical',
                plugins: [new Ext.ux.DatePickerWeekPlugin({
                    weekHeaderString: Tine.Tinebase.appMgr.get('Calendar').i18n._('WK'),
                    inspectMonthPickerClick: function(btn, e) {
                        if (e.getTarget('button')) {
                            var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
                            contentPanel.changeView('month', this.activeDate);
                            
                            return false;
                        }
                    }
                })],
                listeners: {
                    scope: this, 
                    select: function(picker, value, weekNumber) {
                        var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
                        contentPanel.changeView(weekNumber ? 'week' : 'day', value);
                    },
                    render2: function(picker) {
                        // fix height of minipicker panel (south panel)
                        var layout = this.layout;
                        layout.south.el.setHeight(picker.el.getHeight());
                        layout.layout();
                    }
                }
            }), {flex: 1}]
        }];   
        Tine.Calendar.MainScreenLeftPanel.superclass.initComponent.call(this);
    },
    
    /**
     * return calendar selector tree
     * 
     * @return {Tine.widgets.container.TreePanel}
     */
    getCalSelector: function() {
        return this.calSelector;
    },
    
    /**
     * returns a calendar to take for an add event action
     * 
     * @return {Tine.Model.Container}
     */
    getAddCalendar: function() {
        var selections = this.getCalSelector().getSelectionModel().getSelectedNodes();
        
        var addCalendar = Tine.Calendar.registry.get('defaultCalendar');
        
        //active calendar
        var activeNode = this.getCalSelector().getSelectionModel().getActiveNode();
        if (activeNode && this.getCalSelector().hasGrant(activeNode, 'addGrant')) {
            return activeNode.attributes.container;
        }
        
        //first container with add grant
        Ext.each(selections, function(node){
            if (this.getCalSelector().hasGrant(node, 'addGrant')) {
                addCalendar = node.attributes.container;
                return false;
            }
        }, this);
        
        return addCalendar
    }
});

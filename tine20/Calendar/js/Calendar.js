/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: CalendarPanel.js 7900 2009-04-30 07:23:02Z c.weiss@metaways.de $
 */

Ext.namespace('Tine.Calendar');

/**
 * update app icon to reflect the current date
 */
Ext.onReady(function(){
    Ext.util.CSS.updateRule('.CalendarIconCls', 'background-image', 'url(../../images/view-calendar-day-' + new Date().getDate() + '.png)');
});

/**
 * default mainscreen
 * 
 * @type Tine.Tinebase.widgets.app.MainScreen
 */
Tine.Calendar.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
    
    /**
     * sets content panel in mainscreen
     */
    setContentPanel: function() {
        if (! this.contentPanel) {
            this.contentPanel = new Tine.Calendar.MainScreenCenterPanel({
            
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.contentPanel, true);
    },
    
    getContentPanel: function() {
        return this.contentPanel;
    },
    
    /**
     * sets toolbar in mainscreen
     */
    setToolbar: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                items: [/*{
                    selectedView: 'day',
                    selectedViewMultiplier: 1,
                    text: 'day view',
                    iconCls: 'cal-day-view',
                    xtype: 'tbbtnlockedtoggle',
                    //handler: changeView,
                    enableToggle: true,
                    toggleGroup: 'Calendar_Toolbar_tgViews'
                }, {
                    selectedView: 'day',
                    selectedViewMultiplier: 4,
                    text: '4 days view',
                    iconCls: 'cal-upcoming-days-view',
                    xtype: 'tbbtnlockedtoggle',
                    //handler: changeView,
                    enableToggle: true,
                    toggleGroup: 'Calendar_Toolbar_tgViews'
                }, {
                    selectedView: 'day',
                    selectedViewMultiplier: 7,
                    text: 'week view',
                    iconCls: 'cal-week-view',
                    xtype: 'tbbtnlockedtoggle',
                    //handler: changeView,
                    enableToggle: true,
                    toggleGroup: 'Calendar_Toolbar_tgViews'
                }, {
                    selectedView: 'month',
                    selectedViewMultiplier: 1,
                    text: 'month view',
                    iconCls: 'cal-month-view',
                    xtype: 'tbbtnlockedtoggle',
                    //handler: changeView,
                    enableToggle: true,
                    toggleGroup: 'Calendar_Toolbar_tgViews'
                }*/]
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this.actionToolbar, true);
    }
});

Tine.Calendar.TreePanel = Ext.extend(Ext.Panel, {
    border: false,
    layout: 'border',
    cls: 'cal-tree',
    defaults: {
        border: false
    },
    initComponent: function() {
        this.items = [{
            region: 'center',
            html: ''
        }, {
            split: true,
            region: 'south',
            collapseMode: 'mini',
            collapsible: true,
            height: 190,
            items: new Ext.DatePicker({
                plugins: [new Ext.ux.DatePickerWeekPlugin()],
                listeners: {
                    scope: this, 
                    select: function(picker, value, weekNumber) {
                        var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getContentPanel();
                        contentPanel.changeView(weekNumber ? 'week' : 'day', value);
                    }
                }
            })
        }];   
        Tine.Calendar.TreePanel.superclass.initComponent.call(this);
    }

});


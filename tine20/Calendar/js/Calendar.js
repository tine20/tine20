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
    
    dvp: null,
    
    /**
     * sets content panel in mainscreen
     */
    setContentPanel: function() {
        if (! this.dvp) {
            this.dvp = new Tine.Calendar.CalendarPanel({
                //title: 'my lovely calendar',
                view: new Tine.Calendar.DaysView({}),
                store: new Ext.data.JsonStore({
                    id: id,
                    fields: Tine.Calendar.EventArray,
                    data: [
                        {id : '1', summary: 'Breakfast', color: '#FD0000', dtstart: '2009-05-07 08:00:00', dtend: '2009-05-07 09:00:00'},
                        {id : '2', summary: 'Lunch', color: '#FD0000', dtstart: '2009-05-07 13:00:00', dtend: '2009-05-07 15:00:00'},
                        {id : '3', summary: 'Supper', color: '#FD0000', dtstart: '2009-05-07 18:00:00', dtend: '2009-05-07 19:00:00'},
                        {id : '4', summary: 'test', color: '#FD0000', dtstart: '2009-05-08 09:00:00', dtend: '2009-05-08 12:00:00'},
                        {id : '5', summary: 'test', color: '#FD0000', dtstart: '2009-05-08 09:00:00', dtend: '2009-05-08 10:00:00'},
                        {id : '6', summary: 'test', color: '#FD0000', dtstart: '2009-05-08 10:00:00', dtend: '2009-05-08 11:00:00'},
                        {id : '7', summary: 'test', color: '#FD0000', dtstart: '2009-05-08 15:00:00', dtend: '2009-05-08 18:00:00'},
                        {id : '8', summary: 'test', color: '#FD0000', dtstart: '2009-05-08 15:45:00', dtend: '2009-05-08 17:30:00'},
                        {id : '9', summary: 'test', color: '#FD0000', dtstart: '2009-05-08 16:30:00', dtend: '2009-05-08 19:30:00'}
                    ]
                })
            })
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.dvp, true);
    },
    
    /**
     * sets toolbar in mainscreen
     */
    setToolbar: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                items: [{
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
                }]
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this.actionToolbar, true);
    }
});

Tine.Calendar.TreePanel = Ext.Panel;


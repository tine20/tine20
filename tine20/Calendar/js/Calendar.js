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
                view: new Tine.Calendar.DaysView({})
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


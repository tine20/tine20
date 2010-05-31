/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

/**
 * Calendar west panel
 * 
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.MainScreenWestPanel
 * @extends     Tine.widgets.mainscreen.WestPanel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @constructor
 * @xtype       tine.calendar.mainscreenwestpanel
 */
Tine.Calendar.MainScreenWestPanel = Ext.extend(Tine.widgets.mainscreen.WestPanel, {
    
    containerTreePanelClassName: 'CalendarSelectTreePanel',
    cls: 'cal-tree',
    
    getAdditionalItems: function() {
        return [Ext.apply({
            title: this.app.i18n._('Mini Calendar'),
            forceLayout: true,
            border: false,
            layout: 'hbox',
            layoutConfig: {
                align:'middle'
            },
            defaults: {border: false},
            items: [{
                flex: 1
            }, this.getDatePicker(), {
                flex: 1
            }]
        }, this.defaults)];
    },
    
    getDatePicker: function() {
        if (! this.datePicker) {
            this.datePicker = new Ext.DatePicker({
                width: 200,
                id :'cal-mainscreen-minical',
                plugins: [new Ext.ux.DatePickerWeekPlugin({
                    weekHeaderString: Tine.Tinebase.appMgr.get('Calendar').i18n._('WK'),
                    inspectMonthPickerClick: function(btn, e) {
                        if (e.getTarget('button')) {
                            var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getCenterPanel();
                            contentPanel.changeView('month', this.activeDate);
                            
                            return false;
                        }
                    }
                })],
                listeners: {
                    scope: this, 
                    select: function(picker, value, weekNumber) {
                        var contentPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getCenterPanel();
                        contentPanel.changeView(weekNumber ? 'week' : 'day', value);
                    }
                }
            });
        }
        
        return this.datePicker;
    }
});

Ext.reg('tine.calendar.mainscreenwestpanel', Tine.Calendar.MainScreenWestPanel);
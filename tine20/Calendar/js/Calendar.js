/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.Application
 * @extends     Tine.Tinebase.Application
 * Calendar Application Object <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id: AttendeeGridPanel.js 9749 2009-08-05 09:08:34Z c.weiss@metaways.de $
 */
Tine.Calendar.Application = Ext.extend(Tine.Tinebase.Application, {
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.ngettext('Calendar', 'Calendars', 1);
    }
});

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.MainScreen
 * @extends Tine.widgets.MainScreen
 * MainScreen of the Calendar Application <br>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AttendeeGridPanel.js 9749 2009-08-05 09:08:34Z c.weiss@metaways.de $
 * @constructor
 * Constructs mainscreen of the calendar application
 */
Tine.Calendar.MainScreen = function(config) {
    Ext.apply(this, config);
    Tine.Calendar.colorMgr = new Tine.Calendar.ColorManager({});
    
    Tine.Calendar.MainScreen.superclass.constructor.call(this);
}

Ext.extend(Tine.Calendar.MainScreen, Tine.widgets.MainScreen, {
    
    containerTreePanelClassName: 'CalendarSelectTreePanel',
    
    /**
     * returns left panel aka tree panel in other apps
     */
    getWestPanel: function() {
        
        if (! this.westPanel) {
            var orgWestPanel = this.supr().getWestPanel.apply(this, arguments);
            
            this.westPanel = new Ext.Panel({
                cls: 'cal-tree',
                border: false,
                layout: 'border',
                items: [{
                    region: 'center',
                    border: false,
                    layout: 'hfit',
                    items: orgWestPanel
                }, {
                    region: 'south',
                    border: false,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
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
                            },
                            render2: function(picker) {
                                // fix height of minipicker panel (south panel)
                                var layout = this.layout;
                                layout.south.el.setHeight(picker.el.getHeight());
                                layout.layout();
                            }
                        }
                    }), {flex: 1}]
                }]
            });
        }
        
        return this.westPanel;
    },
    
    
    /**
     * Get content panel of calendar application
     * 
     * @return {Tine.Calendar.MainScreenCenterPanel}
     */
    getCenterPanel: function() {
        if (! this.contentPanel) {
            this.contentPanel = new Tine.Calendar.MainScreenCenterPanel({
                detailsPanel: new Tine.Calendar.EventDetailsPanel()
            });
        }
        
        return this.contentPanel;
    },
    
    /**
     * Set toolbar panel in Tinebase.MainScreen
     */
    showNorthPanel: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = this.contentPanel.getActionToolbar();
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this.actionToolbar, true);
    }
});


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
    
    Tine.Calendar.MainScreen.superclass.constructor.apply(this, arguments);
}

Ext.extend(Tine.Calendar.MainScreen, Tine.widgets.MainScreen, {
    
    
    westPanelXType: 'tine.calendar.mainscreenwestpanel',
    
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


/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.Application
 * @extends     Tine.Tinebase.Application
 * Calendar Application Object <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * auto hook text i18n._('New Event')
     */
    addButtonText: 'New Event',
    
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.ngettext('Calendar', 'Calendars', 1);
    },
    
    /**
     * returns iconCls of this application
     * 
     * @param {String} target
     * @return {String}
     */
    getIconCls: function(target) {
        switch(target){
            case 'PreferencesTreePanel':
            return 'PreferencesTreePanel-CalendarIconCls';
            break;
        default:
            return 'CalendarIconCls';
            break;
        }
    },
    
    init: function() {
        this.updateIcon();
        Tine.Calendar.Application.superclass.init.apply(this.arguments);
        
        new Tine.Calendar.AddressbookGridPanelHook({app: this});
        
        if (Tine.Felamimail) {
            Tine.Felamimail.MimeDisplayManager.register('text/calendar', Tine.Calendar.iMIPDetailsPanel);
        }

        var subscription = postal.subscribe({
            channel  : "thirdparty",
            topic    : "data.changed",
            callback : function(data, envelope) {
                Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getCenterPanel().autoRefreshTask.delay(0);
            }
        });
    },

    registerCoreData: function() {
        Tine.CoreData.Manager.registerGrid('cal_resources', Tine.Calendar.ResourceGridPanel, {
            ownActionToolbar: false,
        });
    },

    updateIcon: function() {
        var imageUrl = Tine.Tinebase.common.getUrl('full') + '/images/view-calendar-day-' + new Date().getDate() + '.png';
        Ext.util.CSS.updateRule('.CalendarIconCls', 'background-image', 'url(' + imageUrl + ')');

    },

    routes: {
        'showEvent/(.*)': 'showEvent'
    },

    /**
     * display event in mainscreen
     * @param {String} id
     *
     * example:
     * http://tine20.example.com:10443/#/Calendar/showEvent/89192f9d3ce44ed3681a1b73d5ca491e766c4d62
     */
    showEvent: function(id) {
        var cp = this.getMainScreen().getCenterPanel(),
            activePanel = cp.getCalendarPanel(cp.activeView),
            activeView = activePanel.getView(),
            store = activeView.store;

        cp.initialLoadAfterRender = false;

        Tine.Tinebase.MainScreenPanel.show(this);

        if (cp.loadMask) {
            cp.loadMask.show();
        }

        Tine.Calendar.backend.loadRecord(id, {
            success: function(record) {
                // @TODO timeline view
                store.on('load', function() {
                    // NOTE: the store somehow changes, so refetch it
                    var activePanel = cp.getCalendarPanel(cp.activeView),
                        activeView = activePanel.getView(),
                        sm = activeView.getSelectionModel(),
                        store = activeView.store,
                        event = store.getById(record.get('id'))

                    if (! event) {
                        store.add([record]);
                        event = record;
                    }

                    sm.select.defer(250, sm, [event]);

                }, this, { single: true });

                activeView.updatePeriod({from: record.get('dtstart')});
                cp.selectFavorite();
            },
            failure: function() {
                cp.selectFavorite();
                Ext.Msg.alert(this.i18n._('Event not found'), this.i18n._("The Event was deleted in the meantime or you don't have access rights to it."));
            }
        });
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
 * @constructor
 * Constructs mainscreen of the calendar application
 */
Tine.Calendar.MainScreen = function(config) {
    Ext.apply(this, config);

    var prefs = this.app.getRegistry().get('preferences');
    Ext.DatePicker.prototype.startDay = parseInt((prefs ? prefs.get('firstdayofweek') : 1), 10);

    Tine.Calendar.MainScreen.superclass.constructor.apply(this, arguments);
};

Ext.extend(Tine.Calendar.MainScreen, Tine.widgets.MainScreen, {

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
     * get north panel for given contentType
     *
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getNorthPanel: function(contentType) {
        if (! this.actionToolbar) {
            this.actionToolbar = this.contentPanel.getActionToolbar();
        }
        
        return this.actionToolbar;
    }
});

/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.Calendar');

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
                detailsPanel: new Tine.Calendar.EventDetailsPanel({
                    recordClass: Tine.Calendar.Model.Event
                })
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
            
            if (this.actionToolbar.cls) {
                this.actionToolbar.cls = this.actionToolbar.cls + ' t-contenttype-event';
            } else {
                this.actionToolbar.cls = 't-contenttype-event';
            }
        }

        return this.actionToolbar;
    }
});

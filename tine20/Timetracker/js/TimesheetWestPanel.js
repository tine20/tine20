/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <M.Spahn@bitExpert.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Timetracker');

/**
 * Timetracker TimesheetWestPanel west panel
 * 
 * @namespace   Tine.Timetracker
 * @class       Tine.Timetracker.TimesheetWestPanel
 * @extends     Tine.widgets.mainscreen.TimesheetWestPanel
 * 
 * @author      Michael Spahn <M.Spahn@bitExpert.de>
 */
Tine.Timetracker.TimesheetWestPanel = Ext.extend(Tine.widgets.mainscreen.WestPanel, {

    getAdditionalItems: function() {
        var items = [];

        if (this.app.featureEnabled('featureTimeaccountBookmark')) {
            items.push(new Tine.Timetracker.TimeaccountFavoritesPanel({
                height: 'auto',
                id: 'TimeaccountFavoritesPanel'
            }));
        }

        return items;
    }
});

/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 216 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Timetracker');

/**
 * CoreData west panel
 *
 * @namespace   Tine.Timetracker
 * @class       Tine.Timetracker.TimeaccountWestPanel
 * @extends     Tine.widgets.mainscreen.TimeaccountWestPanel
 *
 * @author      Michael Spahn <M.Spahn@bitExpert.de>
 *
 * @constructor
 * @xtype       tine.timetracker.TimeaccountWestPanel
 */
Tine.Timetracker.TimeaccountWestPanel = Ext.extend(Tine.widgets.mainscreen.WestPanel, {
    hasContainerTreePanel: false,

    getAdditionalItems: function () {
        var items = [];

        // if (this.app.featureEnabled('featureTimeaccountBookmark')) {
        //     items.push(new Tine.Timetracker.TimeaccountFavoritesPanel({
        //         height: 'auto',
        //         id: 'TimeaccountFavoritesPanel'
        //     }));
        // }

        return items;
    }
});

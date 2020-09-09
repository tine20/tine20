/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 216 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.CoreData');

/**
 * CoreData west panel
 * 
 * @namespace   Tine.CoreData
 * @class       Tine.CoreData.WestPanel
 * @extends     Tine.widgets.mainscreen.WestPanel
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @constructor
 * @xtype       tine.calendar.mainscreenwestpanel
 */
Tine.CoreData.WestPanel = Ext.extend(Tine.widgets.mainscreen.WestPanel, {
    
    hasFavoritesPanel: false,
    
    getAdditionalItems: function() {
        return [
            new Tine.CoreData.TreePanel({
                height: 'auto'
            })
        ];
    }
});

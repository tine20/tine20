/*
 * Tine 2.0
 *
 * @package     Bookmark
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Bookmarks');

Tine.Bookmarks.BookmarkGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    initComponent: function () {
        this.supr().initComponent.apply(this, arguments);
    },

    /**
     * grid row doubleclick handler
     *
     */
    onRowDblClick: function(grid, row, e) {
        const record = this.grid.getSelectionModel().getSelected();
        const link = Tine.Tinebase.common.getUrl() + '/Bookmarks/openBookmark/' + record.getId()
        
        window.open(link, '_blank');
    }
});


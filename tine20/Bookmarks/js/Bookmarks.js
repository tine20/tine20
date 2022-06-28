/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

require('../styles/bookmarks.less')
require('./BookmarkGridPanel');

Tine.widgets.grid.RendererManager.register('Bookmarks', 'Bookmark', 'url', (url, metadata, record) => {
    const link = Tine.Tinebase.common.getUrl() + '/Bookmarks/openBookmark/' + record.getId()

    return `<a href="${link}" onclick="javascript:window.open('${link}', '_blank'); return false;">${url}</a>`;
}, Tine.widgets.grid.RendererManager.CATEGORY_DISPLAYPANEL);

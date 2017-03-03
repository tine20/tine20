/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Addressbook');

/**
 * Render given image
 *
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ImageRenderer
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @singleton
 */
Tine.Addressbook.ImageRenderer = function () {
    function getImageUrl(url, width, height, contact) {
        var mtime = contact.last_modified_time || contact.creation_time;
        if (url.match(/&/)) {
            url = Ext.ux.util.ImageURL.prototype.parseURL(url);
            url.width = width;
            url.height = height;
            url.ratiomode = 0;
            url.mtime = Ext.isDate(mtime) ? mtime.getTime() : new Date().getTime();
        }
        return url;
    }

    return {
        renderer: function(jpegphoto, metadata, record) {
            var url = getImageUrl(jpegphoto, 90, 113, record.data);
            return '<img src="' + url + '" />';
        }
    };
}();

Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'image', Tine.Addressbook.ImageRenderer.renderer, 'displayPanel');
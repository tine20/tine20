/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Addressbook');

/**
 * Render given URL as html
 *
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.UrlRenderer
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @singleton
 */
Tine.Addressbook.UrlRenderer = function () {
    return {
        renderer: function(url) {
            return '<a href=' + Tine.Tinebase.EncodingHelper.encode(url, 'href')  + ' target="_blank">' + Tine.Tinebase.EncodingHelper.encode(url, 'shorttext') + '</a>';
        }
    };
}();

Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'url', Tine.Addressbook.UrlRenderer.renderer, 'displayPanel');
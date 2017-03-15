/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Addressbook');

/**
 * Render given MailAddresss
 *
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.MailAddressRenderer
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @singleton
 */
Tine.Addressbook.MailAddressRenderer = function () {
    return {
        renderer: function (email) {
            if (!email) {
                return '';
            }

            email = Tine.Tinebase.EncodingHelper.encode(email);
            var link = (this.felamimail === true) ? '#' : 'mailto:' + email;
            var id = Ext.id() + ':' + email;

            return '<a href="' + link + '" class="tinebase-email-link" id="' + id + '">'
                + Ext.util.Format.ellipsis(email, 18) + '</a>';
        }
    };
}();

Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'email', Tine.Addressbook.MailAddressRenderer.renderer, 'displayPanel');


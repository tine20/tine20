/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Addressbook');

/**
 * Render given addresss
 *
 * You need to pass the record and a config to use this renderer.
 * The config contains a mapping, which fields from the record should have which place in the template.
 * Undefined fields won't be rendered, this keeps it well reusable for all address like purposes.
 *
 * @namespace   Tine.Addressbook
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @singleton
 */
Tine.Addressbook.addressRenderer = function (v, metadata, record, store, a, b, config) {
    var template = new Ext.XTemplate(
        '<tpl for="." class="address">' +
        '<tpl if="street">{street} <br /></tpl>' +
        '<tpl if="street2">{street2} <br /></tpl>' +
        '<tpl if="postalcode">{postalcode}</tpl> <tpl if="locality">{locality}</tpl><br />' +
        '<tpl if="region">{region} <br /></tpl>' +
        '<tpl if="country">{country}</tpl>' +
        '</tpl>');
    template.compile();

    var local = Ext.apply({}, config);
    var keys = Object.keys(local);

    // According to config, resolve the given fields from record
    keys.forEach(function (key) {
        local[key] = Tine.Tinebase.EncodingHelper.encode(record.get(local[key]));

        // Country code to country name
        // @todo: Wouldn't it be cool, if this could be managed by the modelconfig as well?
        if (key === 'country') {
            var countryRenderer = Tine.widgets.grid.RendererManager.get("Addressbook", "Addressbook_Model_Contact", "country", "displayPanel");
            local[key] = countryRenderer(local[key]);
        }
    });

    return template.applyTemplate(local);
};

Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'addressblock', Tine.Addressbook.addressRenderer, 'displayPanel');
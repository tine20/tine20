/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl <c.feitl@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Render given MailAddresss
 *
 * @namespace   Tine.Addressbook
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @singleton
 */
const mailAddressRenderer = function (email) {
    if (!email) {
        return '';
    }

    email = Tine.Tinebase.EncodingHelper.encode(email);
    var link = (this.felamimail === true) ? '#' : 'mailto:' + email;
    var id = Ext.id() + ':' + email;

    return '<a href="' + link + '" class="tinebase-email-link" id="' + id + '">'
        + Ext.util.Format.ellipsis(email, 18) + '</a>';
}
Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'email', mailAddressRenderer, 'displayPanel');

/**
 * Render country name by it's iso code
 *
 * @namespace   Tine.Addressbook
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @singleton
 */
const countryRenderer = function (v) {
    return Locale.getTranslationData('CountryList', v);
}
Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'country', countryRenderer, 'displayPanel');

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
const addressRenderer = function (v, metadata, record, store, a, b, config) {
    var template = new Ext.XTemplate(
        '<tpl for="." class="address">' +
        '<tpl if="street">{street} <br /></tpl>' +
        '<tpl if="street2">{street2} <br /></tpl>' +
        '<tpl if="postalcode">{postalcode}</tpl> <tpl if="locality">{locality}</tpl><br />' +
        '<tpl if="region">{region} <br /></tpl>' +
        '<tpl if="country">{country}</tpl>' +
        '</tpl>');
    template.compile();

    if (!record) {
        return template.applyTemplate({
            street: '',
            street2: '',
            postalcode: '',
            locality: '',
            region: '',
            country: ''
        });
    }

    var local = Ext.apply({}, config);
    var keys = Object.keys(local);

    // According to config, resolve the given fields from record
    keys.forEach(function (key) {
        var value = null;

        if (!record.data) {
            value = window.lodash.get(record, local[key]);
        } else {
            value = window.lodash.get(record.data, local[key]);
        }

        local[key] = Tine.Tinebase.EncodingHelper.encode(value);

        // Country code to country name
        // @todo: Wouldn't it be cool, if this could be managed by the modelconfig as well?
        if (key === 'country') {
            var countryRenderer = Tine.widgets.grid.RendererManager.get("Addressbook", "Addressbook_Model_Contact", "country", "displayPanel");
            local[key] = countryRenderer(local[key]);
        }
    });

    return template.applyTemplate(local);
};

Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'addressblock', addressRenderer, 'displayPanel');

/**
 * Render preferred addresss
 *
 * give the preferred address from contact. (street, postalcode and locality)
 * @namespace   Tine.Addressbook
 * @author      Christian Feitl <c.feitl@metaways.de>
 */
const preferredAddressRender = function (v, metadata, record) {
    let preferredAddress = !!+_.get(record.data, 'preferred_address') || !!+_.get(record, 'preferred_address'),
        adr_street = preferredAddress ? 'adr_two_street' : 'adr_one_street',
        adr_postalcode = preferredAddress ? 'adr_two_postalcode' : 'adr_one_postalcode',
        adr_locality = preferredAddress ? 'adr_two_locality' : 'adr_one_locality',
        contact = record.data ? record.data : record,
        result = ''

    _.each([adr_street, adr_postalcode, adr_locality], function(value) {
        if (contact[value] !== null) {
            result += Ext.util.Format.htmlEncode(_.get(contact, value, ' ')) + ' ';
        }
    });

    return result;
}

Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'addressblock', addressRenderer, 'displayPanel');

/**
 * Render given image
 *
 * @namespace   Tine.Addressbook
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @singleton
 */
const imageRenderer = function (jpegphoto, metadata, record) {
    function getImageUrl(url, width, height, contact) {
        var _ = window.lodash,
            mtime = (_.get(contact, 'data.last_modified_time') || _.get(contact, 'data.creation_time')) || null;
        if (url.match(/&/)) {
            url = Ext.ux.util.ImageURL.prototype.parseURL(url);
            url.width = width;
            url.height = height;
            url.ratiomode = 0;
            url.mtime = Ext.isDate(mtime) ? mtime.getTime() : new Date().getTime();
        }
        return url;
    }

    var url = getImageUrl(jpegphoto, 90, 113, record);

    return '<img src="' + url + '" />';
};

Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'image', imageRenderer, 'displayPanel');

/**
 * Render given URL as html
 *
 * @namespace   Tine.Addressbook
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @singleton
 */
const urlRenderer = function (url) {
    return '<a href=' + Tine.Tinebase.EncodingHelper.encode(url, 'href') + ' target="_blank">' + Tine.Tinebase.EncodingHelper.encode(url, 'shorttext') + '</a>';
};

Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'url', Tine.Addressbook.urlRenderer, 'displayPanel');

const avatarRenderer = function(n_short, metadata, record) {
    const fullName = record ? record.get('n_fileas') : n_short;
    let shortName = record ? record.get('n_short') : n_short;
    if (! shortName && record) {
        let names = _.compact([record.get('n_family'), record.get('n_middle'), record.get('n_given')]);
        if (!names.length && fullName) {
            names = fullName.split(' ');
        }
        if (names.length > 1) {
            shortName = _.map(names, (n) => { return n.substring(0, 1).toUpperCase() }).join('');
        } else {
            shortName = String(names[0]).substring(0, 2);
        }
    }

    const colorSchema = Tine.Calendar.colorMgr.getSchema(record && record.get('color') ? record.get('color') : Tine.Calendar.ColorManager.stringToColour(shortName).substring(1,6));
    return shortName ? `<span class="adb-avatar-wrap" ext:qtip="${fullName}" style="background-color: ${colorSchema.color}; color: ${colorSchema.text}">${shortName}</span>` : '';
}
export {
    mailAddressRenderer,
    countryRenderer,
    addressRenderer,
    imageRenderer,
    preferredAddressRender,
    urlRenderer,
    avatarRenderer
}

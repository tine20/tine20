/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

var DOMParser = require('xmldom').DOMParser;
var XMLSerializer = require('xmldom').XMLSerializer;

module.exports = function(content) {
    this.cacheable && this.cacheable();

    var oParser = new DOMParser(),
        doc = oParser.parseFromString(content.toString(), "application/xml"),
        width = doc.documentElement.getAttribute('width'),
        height = doc.documentElement.getAttribute('height');

    if (! (width && height)) {
        var oSerializer = new XMLSerializer(),
            viewBox = doc.documentElement.getAttribute('viewBox'),
            parts = viewBox.split(' '),
            cWidth = parts[2] - parts[0],
            cHeight = parts[3] - parts[1];

        doc.documentElement.setAttribute('width', cWidth);
        doc.documentElement.setAttribute('height', cHeight);

        return oSerializer.serializeToString(doc);
    }


    return content;
};

module.exports.raw = true;

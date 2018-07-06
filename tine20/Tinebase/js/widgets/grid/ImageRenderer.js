/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.grid');


Tine.widgets.grid.imageRenderer = function(value, metadata, record) {
    var _ = window.lodash,
        result = '',
        style = String(value).match(/icon-set/) ? ('background-image: url(' + value + ') !important;') : '';

    if (value) {
        result = '<div class="action_image tine-grid-row-action-icon" style="'+ style + '" ext:qtip="' +
            Ext.util.Format.htmlEncode('<div style="width:300px; height:300px;"><img src="' + String(value)
                .replace(/width=\d+/, 'width=290')
                .replace(/height=\d+/, 'height=300') +
            '" /></div>') +
        '"/>';
    }

    return result;
};

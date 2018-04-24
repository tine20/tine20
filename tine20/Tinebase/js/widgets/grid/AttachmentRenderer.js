/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.grid');


Tine.widgets.grid.attachmentRenderer = function(value, metadata, record) {
    var _ = window.lodash,
        result = '';

    if (_.isArray(value) && value.length) {
        result = '<div class="action_attach tine-grid-row-action-icon" />';
    }

    return result;
};

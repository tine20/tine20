/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './SelectionDialog';

Ext.ns('Tine.Tinebase.widgets.file');

Tine.Tinebase.widgets.file.locationRenderer = function(location) {
    // @TODO: return invisible html and replace async with visible rendering
}

Tine.Tinebase.widgets.file.locationRenderer.getLocationPlugin = async function(location, pluginConfig) {
    if (! Tine.Tinebase.widgets.file.LocationTypePluginFactory.isRegistered(location.type)) {
        Tine.log.warn(`no LocationTypePlugin for "${location.type}" registered`);
        return location.fileName;
    }

    return await Tine.Tinebase.widgets.file.LocationTypePluginFactory.create(
        location.type, _.get(pluginConfig, location.type, {}));
    
}

Tine.Tinebase.widgets.file.locationRenderer.getLocationHtml = async function(location, pluginConfig) {
    const plugin = await Tine.Tinebase.widgets.file.locationRenderer.getLocationPlugin(location, pluginConfig);

    return `<span cls="file-location-renderer ${plugin.iconCls}">` + plugin.getLocationName(location) + `</span>`;
}

Tine.Tinebase.widgets.file.locationRenderer.getLocationName = async function(location, pluginConfig) {
    const plugin = await Tine.Tinebase.widgets.file.locationRenderer.getLocationPlugin(location, pluginConfig);

    return plugin.getLocationName(location)
}

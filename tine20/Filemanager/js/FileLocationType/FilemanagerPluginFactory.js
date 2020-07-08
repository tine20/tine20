/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager.FileLocationType');

Tine.Filemanager.FileLocationType.FilemanagerPluginFactory = async function(config) {
    return import(/* webpackChunkName: "Filemanager/js/FilemanagerFileLocationPlugin" */ './FilemanagerPlugin').then(() => {
        return new Tine.Filemanager.FileLocationType.FilemanagerPlugin(config);
    });
}

Tine.Tinebase.widgets.file.LocationTypePluginFactory.register('fm_node',
    Tine.Filemanager.FileLocationType.FilemanagerPluginFactory);

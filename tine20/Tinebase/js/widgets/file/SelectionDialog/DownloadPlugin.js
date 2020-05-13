/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('./AbstractPlugin');

Ext.ns('Tine.Tinebase.widgets.file.SelectionDialog');

Tine.Tinebase.widgets.file.SelectionDialog.DownloadPlugin = function(plugin) {
    Ext.apply(this, plugin);
    
    this.name = i18n._('To My Computer');
    
    this.targetForm = new Ext.Panel({
        height: 38,
        border: false,
        frame: true
    });
    
    this.pluginPanel = new Ext.Button(Ext.apply({
        text: i18n._('Download file and save on my local machine.'),
        doAutoWidth: Ext.emptyFn,
        iconCls: 'action_download',
        cls: 'tw-FileSelectionArea',
        handler: this.cmp.onButtonApply,
        scope: this.cmp
    }, _.get(this, 'cmp.pluginConfig.' + this.plugin, {})));

    this.cmp.addPlace(this);
};

Ext.extend(Tine.Tinebase.widgets.file.SelectionDialog.DownloadPlugin, Tine.Tinebase.widgets.file.SelectionDialog.AbstractPlugin, {
    plugin: 'download',
    iconCls: 'action_download',

    getFileList: function() {
        return [];
    },

    manageButtonApply: function(buttonApply) {
        buttonApply.setDisabled(false);
    }
});

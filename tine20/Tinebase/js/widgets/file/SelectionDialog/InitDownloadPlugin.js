/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.file.SelectionDialog');

Tine.Tinebase.widgets.file.SelectionDialog.InitDownloadPlugin = function(config) {
    Ext.apply(this, config);
};

Tine.Tinebase.widgets.file.SelectionDialog.InitDownloadPlugin.prototype = {
    init: function(cmp) {
        this.cmp = cmp;
        if (+Tine.Tinebase.configManager.get('downloadsAllowed')) {
            import(/* webpackChunkName: "Tinebase/js/widgets/file-SelectionDialog-DownloadPlugin" */ './DownloadPlugin').then(() => {
                const plugin = new Tine.Tinebase.widgets.file.SelectionDialog.DownloadPlugin(this);
            });
        }
    }
}

Ext.preg('widgets.file.selectiondialog.download', Tine.Tinebase.widgets.file.SelectionDialog.InitDownloadPlugin);

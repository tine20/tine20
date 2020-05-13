/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.file.SelectionDialog');

Tine.Tinebase.widgets.file.SelectionDialog.InitUploadPlugin = function(config) {
    Ext.apply(this, config);
};

Tine.Tinebase.widgets.file.SelectionDialog.InitUploadPlugin.prototype = {
    init: function(cmp) {
        this.cmp = cmp;
        // @TODO is user allowed/capable to upload local files?
        if (true) {
            import(/* webpackChunkName: "Tinebase/js/widgets/file-SelectionDialog-UploadPlugin" */ './UploadPlugin').then(() => {
                const plugin = new Tine.Tinebase.widgets.file.SelectionDialog.UploadPlugin(this);
            });
        }
    }
}

Ext.preg('widgets.file.selectiondialog.upload', Tine.Tinebase.widgets.file.SelectionDialog.InitUploadPlugin);

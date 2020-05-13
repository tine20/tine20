/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager.FileSelectionDialog');

Tine.Filemanager.FileSelectionDialog.InitFilemanagerPlugin = function(config) {
    Ext.apply(this, config);
};

Tine.Filemanager.FileSelectionDialog.InitFilemanagerPlugin.prototype = {
    init: function(cmp) {
        this.cmp = cmp;
        
        if (Tine.Tinebase.appMgr.isEnabled('Filemanager')) {
            this.app = Tine.Tinebase.appMgr.get('Filemanager');
            import(/* webpackChunkName: "Filemanager/js/FileSelectionDialog-FilemanagerPlugin" */ './FilemanagerPlugin').then(() => {
                const plugin = new Tine.Filemanager.FileSelectionDialog.FilemanagerPlugin(this);
            });
        }
    }
}

Ext.preg('widgets.file.selectiondialog.filemanager', Tine.Filemanager.FileSelectionDialog.InitFilemanagerPlugin);

/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.Application
 * @extends Tine.Tinebase.Application
 */
Tine.Filemanager.Application = Ext.extend(Tine.Tinebase.Application, {
    /**
     * Get translated application title of this application
     *
     * @return {String}
     */
    getTitle : function() {
        return this.i18n.gettext('Filemanager');
    },

    routes: {
        'showNode(.*)': 'showNode'
    },

    /**
     * display file/directory in mainscreen
     * /#/Filemanager/showNode/shared/someFolder/someFile
     */
    showNode: function(path) {
        this.getMainScreen().getCenterPanel().initialLoadAfterRender = false;
        Tine.Tinebase.MainScreenPanel.show(this);

        // if file, show directory file is in
        var dirPath = path;
        if (String(path).match(/\/.*\.[a-zA-Z0-9]+$/)) {
            var pathParts = path.split('/');
            pathParts.pop();
            dirPath = pathParts.join('/');
        }

        (function() {
            var cp = this.getMainScreen().getCenterPanel(),
                grid = cp.getGrid(),
                store = cp.getStore(),
                ftb = cp.filterToolbar,
                highlight = function() {
                    store.un('load', highlight);
                    var sm = grid.getSelectionModel(),
                        idx = store.find('path', path);
                    if (idx) {
                        sm.selectRow(idx);
                    }
                };

            store.on('load', highlight);
            ftb.setValue([{field: 'path', operator: 'equals', value: dirPath}]);
            ftb.onFiltertrigger();
        }).defer(500, this);
    }
});

/*
 * register additional action for genericpickergridpanel
 */
Tine.widgets.relation.MenuItemManager.register('Filemanager', 'Node', {
    text: 'Save locally',   // i18n._('Save locally')
    iconCls: 'action_filemanager_save_all',
    requiredGrant: 'readGrant',
    actionType: 'download',
    allowMultiple: false,
    handler: function(action) {
        var node = action.grid.store.getAt(action.gridIndex).get('related_record');
        Tine.Filemanager.downloadFile(node);
    }
});

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.MainScreen
 * @extends Tine.widgets.MainScreen
 */
Tine.Filemanager.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Node'
});

/**
 * download file into browser
 *
 * @param {String|Tine.Filemanager.Model.Node}
 * @param revision
 * @returns {Ext.ux.file.Download}
 */
Tine.Filemanager.downloadFile = function(path, revision) {
    path = lodash.get(path, 'data.path') || lodash.get(path, 'path') || path;

    return new Ext.ux.file.Download({
        params: {
            method: 'Filemanager.downloadFile',
            requestType: 'HTTP',
            id: '',
            path: path,
            revision: revision
        }
    }).start();
};
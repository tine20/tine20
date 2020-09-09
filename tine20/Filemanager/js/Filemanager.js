/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './FileLocationType/FilemanagerPluginFactory';

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
        // NOTE: decodeURIComponent can't cope with +
        path = Ext.ux.util.urlCoder.decodeURIComponent(path);

        // if file, show directory file is in
        var dirPath = path;
        if (String(path).match(/\/.*\..+$/)) {
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

Tine.Filemanager.NodeFilterPanel = Ext.extend(Tine.widgets.persistentfilter.PickerPanel, {
    app: 'Filemanager',
    contentType: 'Node',
    filter: [{field: 'model', operator: 'equals', value: 'Filemanager_Model_Node'}]
});

/**
 * download file into browser
 *
 * @param {String|Tine.Filemanager.Model.Node}
 * @param revision
 * @param appName
 * @returns {Ext.ux.file.Download}
 *
 * @todo move to Tine.Filemanager.FileRecordBackend
 */
Tine.Filemanager.downloadFile = function(path, revision, appName) {
    var _ = window.lodash;
    appName = appName || 'Filemanager';
    path = _.get(path, 'data.path') || _.get(path, 'path') || path;

    return new Ext.ux.file.Download({
        params: {
            method: appName + '.downloadFile',
            requestType: 'HTTP',
            id: '',
            path: path,
            revision: revision
        }
    }).start();
};


/**
 * download file into browser with base64 (btoa) encoded path
 *
 * @param {String} encodedpath
 * @param revision
 * @param appName
 * @returns {Ext.ux.file.Download}
 *
 * @refactor: we should only need one downloadFile fn
 */
Tine.Filemanager.downloadFileByEncodedPath = function(encodedpath, revision, appName) {
    return Tine.Filemanager.downloadFile(atob(encodedpath), revision, appName);
};

/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './FileLocationType/FilemanagerPluginFactory';
import './DuplicateFileUploadDialog';

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
        'showNode(.*)': 'showNode',
        '(.*)': 'showNode'
    },

    /**
     * display file/directory in mainscreen
     * /#/Filemanager/showNode/shared/someFolder/someFile
     */
    showNode: function(path) {
        const {type, dirname, sanitize} = Tine.Filemanager.Model.Node;
        Tine.Tinebase.MainScreenPanel.show(this);
        path = sanitize(Ext.ux.util.urlCoder.decodeURIComponent(path));

        const isFile = type(path) === 'file';
        const dir = isFile ? dirname(path) : path;
        
        (function() {
            var cp = this.getMainScreen().getCenterPanel(),
                grid = cp.getGrid(),
                store = cp.getStore(),
                ftb = cp.filterToolbar,
                highlight = function() {
                    var sm = grid.getSelectionModel(),
                        idx = store.findExact('path', path);
                    if (idx >= 0) {
                        sm.clearSelections();
                        const row = grid.getView().getRow(idx);
                        Ext.fly(row).highlight('#ffffff', {easing: 'bounceOut', duration: 1, endColor: '#dbecf4'});
                        _.delay(() => { sm.selectRow(idx); }, 1000);
                    }
                };

            store.on('load', highlight, this, {single: true});
            
            const currentValue = ftb.getValue();
            if (! (currentValue.length ===1 && currentValue[0].field === 'path' && currentValue[0].operator === 'equals'
                && sanitize(currentValue[0].value) === dir)) {
                ftb.setValue([{field: 'path', operator: 'equals', value: dir}]);
                ftb.onFiltertrigger();
            }
        }).defer(500, this);
    },

    getRoute(path) {
        this.path = path = path || this.path || Tine.Tinebase.container.getMyFileNodePath();
        this.path.replace('showNode/','')

        const encodedPath = _.map(Tine.Filemanager.Model.Node.sanitize(path).split('/'), Ext.ux.util.urlCoder.encodeURIComponent).join('/');
        return `Filemanager${encodedPath}`;
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

// remove content filters if indexing is not enabled
Tine.Tinebase.appMgr.isInitialised('Filemanager').then(() => {
    if (! Tine.Tinebase.configManager.get('filesystem.index_content', 'Tinebase')) {
        const nodeFilterModels = [
            Tine.widgets.grid.FilterRegistry.get('Filemanager', 'Node'),
            Tine.widgets.grid.FilterRegistry.get('Tinebase', 'Tree_Node')
        ];
        _.each(nodeFilterModels, (filterModel) => {
            _.remove(filterModel, _.find(filterModel, {field: 'content'}));
            _.remove(filterModel, _.find(filterModel, {field: 'isIndexed'}));
        });
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

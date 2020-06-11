/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.widgets.grid');

import '../../Model/ImportExportDefinition';

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.ExportButton
 * @extends     Ext.Button
 * <p>export button</p>
 * @constructor
 */
Tine.widgets.grid.ExportButton = function(config) {
    config = config || {};
    Ext.apply(this, config);
    config.handler = this.doExport.createDelegate(this);
    
    Tine.widgets.grid.ExportButton.superclass.constructor.call(this, config);
};

Ext.extend(Tine.widgets.grid.ExportButton, Ext.Action, {
    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     */
    recordClass: null,

    /**
     * @cfg {Function} getExportOptions
     */
    getExportOptions: null,

    /**
     * @cfg {String} icon class
     */
    iconCls: 'action_export',

    /**
     * @cfg {String} format of export (default: csv)
     */
    format: 'csv',

    /**
     * @cfg {String} definitionId
     */
    definitionId: null,

    /**
     * @cfg {String} export function (for example: Timetracker.exportTimesheets)
     */
    exportFunction: null,

    /**
     * @cfg {Tine.widgets.grid.FilterSelectionModel} sm
     */
    sm: null,
    /**
     * @cfg {Tine.widgets.grid.GridPanel} gridPanel
     * use this alternativly to sm
     */
    gridPanel: null,
    /**
     * @cfg {Boolean} showExportDialog
     */
    showExportDialog: false,

    /**
     * add sub menu with attached/related templates
     *
     * @param action
     * @param grants
     * @param records
     * @param isFilterSelect
     */
    actionUpdater: function(action, grants, records, isFilterSelect) {
        var _ = window.lodash,
            favorite = _.get(action, 'initialConfig.definition.favorite'),
            scope = _.get(action, 'initialConfig.definition.scope'),
            format = _.get(action, 'initialConfig.definition.format'),
            handler = _.get(action, 'initialConfig.handler');

        if (_.isFunction(handler) && favorite == '1' && records.length == 1) {
            var record = records[0],
                attachments = _.get(record, 'data.attachments', []),
                relations = _.get(record, 'data.relations', []),
                relatedFiles = _.map(_.filter(relations, {related_model: 'Filemanager_Model_Node'}), 'related_record'),
                allFiles = _.concat(attachments, relatedFiles),
                menuItems = _.reduce(allFiles, function(result, file) {
                    if (_.endsWith(file.name, format)) {
                        result.push({
                            text: file.name,
                            handler: handler.createDelegate(action, [{template: file.id}])
                        });
                    }
                    return result;
                }, []);

            if (menuItems.length) {
                _.each(action.items, function(item) {
                    item.menu = new Ext.menu.Menu({
                        items: menuItems
                    });
                });
            }
        }
    },

    /**
     * do export
     */
    doExport: async function(options) {
        var _ = window.lodash,
            appName, filter, model,
            count = 1;

        // options could be action (default handler signature)
        options = !options || options.el ? {} : options;

        if (this.gridPanel) {
            // get selection model
            if (!this.sm) {
                this.sm = this.gridPanel.grid.getSelectionModel();
            }

            // return if no rows are selected
            if (this.sm.getCount() === 0) {
                return false;
            }

            this.recordClass = this.recordClass || this.gridPanel.recordClass;
            filter = this.sm.getSelectionFilter();
            count = this.sm.getCount();
            options.sortInfo = this.gridPanel.getStore().sortInfo;
        }

        if (_.isFunction(this.getExportOptions)) {
            _.assign(options, this.getExportOptions());
        }

        appName = this.recordClass.getMeta('appName');
        model = this.recordClass.getMeta('phpClassName');
        this.exportFunction = this.exportFunction || (appName + '.export' + this.recordClass.getMeta('modelName') + 's');

        var exportJob = new Tine.Tinebase.Model.ExportJob({
            scope: this.exportScope,
            filter: filter,
            format: this.format,
            exportFunction: this.exportFunction,
            count: count,
            definitionId: this.definitionId,
            recordsName: this.recordClass.getRecordsName(),
            model: model,
            options: options
        });

        let optionsMissing = false;
        if (this.definitionId) {
            const definition = Tine.Tinebase.Model.ImportExportDefinition.get(appName, this.definitionId);
            optionsMissing = definition.optionsMissing(options);
        }
        
        if (this.showExportDialog || Ext.EventObject.altKey === true || optionsMissing) {
            Tine.widgets.dialog.ExportDialog.openWindow({
                appName: appName,
                record: exportJob
            });
        } else {
            Tine.widgets.exportAction.downloadExport(exportJob);
        }
    }
});


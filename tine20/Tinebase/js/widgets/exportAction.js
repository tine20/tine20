/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine', 'Tine.widgets', 'Tine.widgets.exportAction');

Tine.widgets.exportAction.SCOPE_SINGLE = 'single';
Tine.widgets.exportAction.SCOPE_MULTI = 'multi';
Tine.widgets.exportAction.SCOPE_HIDDEN = 'hidden';
Tine.widgets.exportAction.SCOPE_REPORT = 'report';

/**
 * get all (favorite) export definitions for given model
 *
 * @param {Tine.Tinebase.data.Record} recordClass
 * @param {Boolean} favorites
 * @param {String} scope
 * @return {Collection} exportDefinitionsData
 */
Tine.widgets.exportAction.getExports = function (recordClass, favorites, scope) {
    var _ = window.lodash,
        appName = recordClass.getMeta('appName'),
        phpClassName = recordClass.getMeta('phpClassName'),
        app = Tine.Tinebase.appMgr.get(appName),
        allExportDefinitions = _.get(app.getRegistry().get('exportDefinitions'), 'results', []),
        exportDefinitions = _.filter(allExportDefinitions, {model: phpClassName});

    if (_.isBoolean(favorites)) {
        exportDefinitions = _.filter(exportDefinitions, function(d) {
            if (favorites) {
                return d.favorite === '1';
            } else {
                return d.favorite === '0' || d.favorite === null;
            }
        });
    }

    if (_.isString(scope)) {
        exportDefinitions = _.filter(exportDefinitions, function(d) {
            let definedScope = d.scope === Tine.widgets.exportAction.SCOPE_REPORT ?
                Tine.widgets.exportAction.SCOPE_MULTI : d.scope;
            
            if (definedScope === Tine.widgets.exportAction.SCOPE_HIDDEN) {
                return false;
            }
            
            return !definedScope || definedScope === scope;
        });
    }

    return _.sortBy(exportDefinitions, 'order');
};

Tine.widgets.exportAction.getExportMenuItems = function (recordClass, exportConfig, scope) {
    var _ = window.lodash,
        appName = recordClass.getMeta('appName'),
        app = Tine.Tinebase.appMgr.get(appName),
        exportDefinitions = Tine.widgets.exportAction.getExports(recordClass, true, scope);

    return _.reduce(exportDefinitions, function(items, definition) {
        items.push(new Tine.widgets.grid.ExportButton(_.assign({
            exportScope: scope,
            recordClass: recordClass,
            definition: definition,
            text: app.i18n._hidden(definition.label ? definition.label : definition.name),
            format: '',
            definitionId: definition.id,
            iconCls: definition.icon_class,
            order: definition.order
        }, exportConfig)));

        return items;
    }, []);
};

Tine.widgets.exportAction.getExportButton = function(recordClass, exportConfig, scope, additionalItems) {
    if (!recordClass || !Tine.Tinebase.appMgr.isEnabled(recordClass.getMeta('appName'))) return;

    var _ = window.lodash,
        appName = recordClass.getMeta('appName'),
        recordName = recordClass.getRecordName(),
        recordsName = recordClass.getRecordsName(),
        exportFunction = appName + '.export' + recordClass.getMeta('modelName') + 's',
        allItems = Tine.widgets.exportAction.getExports(recordClass, null, scope),
        menuItems = Tine.widgets.exportAction.getExportMenuItems(recordClass, exportConfig, scope);

    if (allItems.length) {
        menuItems.push(new Tine.widgets.grid.ExportButton(_.assign({
            exportScope: scope,
            recordClass: recordClass,
            text: i18n._('Export as ...'),
            iconCls: 'action_export',
            showExportDialog: true,
            exportFunction: exportFunction,
            order: 1000
        }, exportConfig)));
    }

    menuItems = menuItems.concat(additionalItems || []);

    return menuItems.length ? new Ext.Action({
        requiredGrant: 'exportGrant',
        text: String.format(i18n.ngettext('Export {0}', 'Export {0}', 50), recordsName),
        singularText: String.format(i18n.ngettext('Export {0}', 'Export {0}', 1), recordName),
        pluralText:  String.format(i18n.ngettext('Export {0}', 'Export {0}', 1), recordsName),
        translationObject: i18n,
        iconCls: 'action_export',
        scope: this,
        disabled: true,
        allowMultiple: true,
        menu: {
            items: menuItems
        }
    }) : null;
};

/**
 * execute and download export
 *
 * @param {Tine.Tinebase.Model.ExportJob} exportJob
 */
Tine.widgets.exportAction.downloadExport = function(exportJob) {
    var _ = window.lodash,
        filter = exportJob.get('filter'),
        options = _.assign(exportJob.get('options'), {
            format: exportJob.get('format'),
            definitionId: exportJob.get('definitionId'),
            returnFileLocation: exportJob.get('returnFileLocation')
        });

    if (options.filter) {
        filter = options.filter;
        delete options.filter;
    }
    
    const params = {
        method: exportJob.get('exportFunction'),
        requestType: 'HTTP',
        filter: Ext.util.JSON.encode(filter),
        options: Ext.util.JSON.encode(options)
    };
    
    if (! options.returnFileLocation) {
        new Ext.ux.file.Download({
            params: params
        }).start();
    } else {
        return new Promise((resolve, reject) => {
            const connection = new Ext.data.Connection();
            connection.request({
                params: params,
                success: resolve,
                failure: reject,
                url: 'index.php',
                method: 'POST',
                timeout: 1800000 // 30 minutes - this might take a while
            });
        });
    }
};

/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine', 'Tine.widgets', 'Tine.widgets.importAction');

Tine.widgets.importAction.SCOPE_SINGLE = 'single';
Tine.widgets.importAction.SCOPE_MULTI = 'multi';
Tine.widgets.importAction.SCOPE_HIDDEN = 'hidden';

/**
 * get all (favorite) import definitions for given model
 *
 * @param {Tine.Tinebase.data.Record} recordClass
 * @param {Object} importConfig
 */
Tine.widgets.importAction.getImports = function (recordClass, favorites, scope) {
    var _ = window.lodash,
        appName = recordClass.getMeta('appName'),
        phpClassName = recordClass.getMeta('phpClassName'),
        app = Tine.Tinebase.appMgr.get(appName),
        allImportDefinitions = _.get(app.getRegistry().get('importDefinitions'), 'results', []),
        importDefinitions = _.filter(allImportDefinitions, {model: phpClassName});

    if (_.isBoolean(favorites)) {
        importDefinitions = _.filter(importDefinitions, function(d) {
            if (favorites) {
                return d.favorite === '1';
            } else {
                return d.favorite === '0' || d.favorite === null;
            }
        });
    }

    if (_.isString(scope)) {
        importDefinitions = _.filter(importDefinitions, function(d) {
            return Tine.widgets.importAction.SCOPE_HIDDEN !== d.scope && (d.scope === null || d.scope == "" || d.scope == scope);
        });
    }

    return _.sortBy(importDefinitions, 'order');
};

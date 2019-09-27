/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

require('widgets/form/ApplicationPickerCombo');
Ext.ns('Tine.Tinebase');

/**
 * @namespace Tine.Tinebase
 * @class     Tine.Tinebase.CashBookEditDialog
 * @extends   Tine.widgets.dialog.EditDialog
 */
Tine.Tinebase.ImportExportDefinitionEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    appName: 'Tinebase',
    modelName: 'ImportExportDefinition',

    windowNamePrefix: 'ImportExportDefinitionEditWindow_',
    windowHeight: 600,

    evalGrants: false,
    showContainerSelector: false,

    
    getFormItems: function () {
        var _ = window.lodash,
            fieldManager = _.bind(
                Tine.widgets.form.FieldManager.get,
                Tine.widgets.form.FieldManager,
                this.appName,
                this.modelName,
                _,
                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
        return {
            xtype: 'tabpanel',
                border: false,
            plain:true,
            activeTab: 0,
            plugins: [{
            ptype : 'ux.tabpanelkeyplugin'
        }],
            defaults: {
            hideMode: 'offsets'
        },
            items:[{
                title: this.app.i18n.ngettext('ImportExportDefinition', 'ImportExportDefinitions', 1),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype: 'textfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: 1
                        },
                        items: [[
                            fieldManager('name'),
                            fieldManager('label'),
                            fieldManager('description'),
                            fieldManager('type', {columnWidth: 3/4}),
                            fieldManager('favorite', {columnWidth: 1/4}),
                            fieldManager('application_id', {xtype: 'tw-app-picker'}),
                            fieldManager('model'),
                            fieldManager('plugin'),
                            fieldManager('plugin_options'),
                            fieldManager('format'),
                            fieldManager('filename')
                        ]]
                    }]
                }]
            }]
        }
    }
});
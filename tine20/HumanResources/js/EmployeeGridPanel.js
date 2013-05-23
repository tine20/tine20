/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * Employee grid panel
 * 
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.EmployeeGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Employee Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.EmployeeGridPanel
 */
Tine.HumanResources.EmployeeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * @private
     */
    initActions: function() {
        
        this.actions_exportEmployees = new Ext.Action({
            text: String.format(this.app.i18n.ngettext('Export {0}', 'Export {0}', 50), this.i18nRecordsName),
            singularText: String.format(this.app.i18n.ngettext('Export {0}', 'Export {0}', 1), this.i18nRecordName),
            pluralText:  String.format(this.app.i18n.ngettext('Export {0}', 'Export {0}', 1), this.i18nRecordsName),
            translationObject: this.app.i18n,
            iconCls: 'action_export',
            scope: this,
            disabled: true,
            allowMultiple: true,
            actionUpdater: function(action, grants, records) {
                action.setDisabled(records.length == 0);
            },
            menu: {
                items: [
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as ODS'),
                        format: 'ods',
                        iconCls: 'tinebase-action-export-ods',
                        exportFunction: 'HumanResources.exportEmployees',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as XLS'),
                        format: 'xls',
                        iconCls: 'tinebase-action-export-xls',
                        exportFunction: 'HumanResources.exportEmployees',
                        gridPanel: this
                    })
                ]
            }
        });
        
        this.actionToolbarItems = [this.actions_exportEmployees];
        
        // register actions in updater
        this.actionUpdater.addActions([
            this.actions_exportEmployees
        ]);
        
        Tine.HumanResources.EmployeeGridPanel.superclass.initActions.call(this);
    }
});

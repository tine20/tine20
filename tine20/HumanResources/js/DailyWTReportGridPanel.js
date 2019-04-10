/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

// NOTE: recordClass and some other config is injected by appStarter
Tine.HumanResources.DailyWTReportGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    initComponent: function() {

        Ext.applyIf(this, {
            filterConfig: {},
            defaultFilters: [
                {field: 'date', operator: 'within', value: 'monthThis'}/*,
                {field: 'employee_id', operator: 'equals', value: null}*/
            ]
        });

        // show ftb initially
        Ext.apply(this.filterConfig, {
            quickFilterConfig: {
                detailsToggleBtnConfig: {
                    initialState: {
                        detailsButtonPressed: true
                    }
                }
            }
        });

        Tine.HumanResources.DailyWTReportGridPanel.superclass.initComponent.apply(this, arguments);
    },

    initActions: function() {
        this.action_calculateAllReports = new Ext.Action({
            text: this.app.i18n._('Calculate all Reports'),
            handler: this.onCalculateAllReports,
            iconCls: 'action_create_reports',
            scope: this
        });

        this.actionToolbarItems = [
            Ext.apply(new Ext.Button(this.action_calculateAllReports), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            })
        ];

        Tine.HumanResources.DailyWTReportGridPanel.superclass.initActions.apply(this, arguments);

        this.action_addInNewWindow.setHidden(true);
        this.action_deleteRecord.setHidden(true);
    },

    onCalculateAllReports: function(btn) {
        var me = this,
            apiName = 'calculateAll' + this.recordClass.getMeta('modelName') + 's';

        // NOTE: loading animation not possible with medium btn
        btn.setDisabled(true);
        me.pagingToolbar.refresh.disable();

        Tine.HumanResources[apiName]().finally(function() {
            btn.setDisabled(false);
            me.loadGridData();
        });
    }

});
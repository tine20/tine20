/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

// NOTE: recordClass and some other config is injected by appStarter
Tine.HumanResources.MonthlyWTReportGridPanel = Ext.extend(Tine.HumanResources.DailyWTReportGridPanel, {
    initComponent: function() {
        this.defaultFilters = [
            {field: 'month', operator: 'equals', value: new Date().format('Y-m')}/*,
            {field: 'employee_id', operator: 'equals', value: null}*/
        ];

        Tine.HumanResources.MonthlyWTReportGridPanel.superclass.initComponent.apply(this, arguments);
    },
});
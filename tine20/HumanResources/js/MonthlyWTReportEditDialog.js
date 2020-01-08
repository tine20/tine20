/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

Tine.HumanResources.MonthlyWTReportEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function() {
        Tine.HumanResources.MonthlyWTReportEditDialog.superclass.initComponent.apply(this, arguments);

        // auto recalc after a daily report got changed
        this.getForm().findField('dailywtreports').on('update', this.onApplyChanges.createDelegate(this, [false]));

        // ok btn was not pressed - so let's not autosave it!
        // this.getForm().findField('working_time_correction').on('change', this.onApplyChanges.createDelegate(this, [false]));
    },

    initButtons: function() {
        this.tbarItems = this.tbarItems || [];

        this.action_calculateEmployeeReports = new Ext.Action({
            text: this.app.i18n._('Recalculate'),
            handler: this.onApplyChanges.createDelegate(this, [false]),
            iconCls: 'x-tbar-loading',
            scope: this
        });

        this.actionUpdater.addAction(this.action_calculateEmployeeReports);
        this.tbarItems.push(this.action_calculateEmployeeReports);


        Tine.HumanResources.MonthlyWTReportEditDialog.superclass.initButtons.apply(this, arguments);
    }
});


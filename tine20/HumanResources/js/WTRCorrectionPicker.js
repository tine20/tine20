/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.HumanResources');

class WTRCorrectionPicker extends Tine.widgets.grid.PickerGridPanel {
    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources');

        this.recordClass = 'HumanResources.WTRCorrection';
        this.fieldLabel = this.app.i18n._('Working time corrections');
        this.recordName = this.app.i18n._('Correction');
        // this.hideHeaders = true;
        this.isFormField = true;
        this.enableTbar = false;
        this.enableBbar = true;
        this.allowCreateNew = true;
        this.deleteOnServer = true;

        this.columns = ['correction', 'title', 'creation_time', 'status'];
        this.autoExpandColumn = 'title';

        this.editDialogConfig = this.editDialogConfig || {};
        this.refIdField = this.editDialog.recordClass.getMeta('modelName') === 'MonthlyWTReport' ? 'wtr_monthly' : 'wtr_daily';
        _.set(this, `editDialogConfig.fixedFields.${this.refIdField}`, this.editDialog.record.getId());

        super.initComponent();
    }
    setOwnerCt(ct) {
        this.ownerCt = ct;

        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }
    }

    onCreate() {
        _.set(this, 'editDialogConfig.fixedFields.employee_id', this.editDialog.getForm().findField('employee_id').selectedRecord);
        super.onCreate();

        if (this.editDialog.record.modified) {
            this.editDialog.applyChanges();
        }

    }

    onRowDblClick(grid, row, col) {
        _.set(this, 'editDialogConfig.fixedFields.employee_id', this.editDialog.getForm().findField('employee_id').selectedRecord);
        super.onRowDblClick(grid, row, col);

        if (this.editDialog.record.modified) {
            this.editDialog.applyChanges();
        }
    }

    onEditDialogRecordUpdate(updatedRecord) {
        super.onEditDialogRecordUpdate(updatedRecord);
        this.editDialog.loadRecord('remote');
    }
}
Ext.reg('HumanResources.WTRCorrectionPicker', WTRCorrectionPicker);

Tine.widgets.form.FieldManager.register('HumanResources', 'DailyWTReport', 'corrections', {
    xtype: 'HumanResources.WTRCorrectionPicker',
    height: 132
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

Tine.widgets.form.FieldManager.register('HumanResources', 'MonthlyWTReport', 'corrections', {
    xtype: 'HumanResources.WTRCorrectionPicker',
    height: 132
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

export default WTRCorrectionPicker;

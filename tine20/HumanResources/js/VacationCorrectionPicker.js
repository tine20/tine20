/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './VacationCorrectionEditDialog'

Ext.ns('Tine.HumanResources');

class VacationCorrectionPicker extends Tine.widgets.grid.PickerGridPanel {
    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources');

        this.recordClass = 'HumanResources.VacationCorrection';
        this.fieldLabel = this.app.i18n._('Vacation correction');
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
        this.refIdField = 'account_id';
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
        _.set(this, 'editDialogConfig.fixedFields.employee_id', this.editDialog.record.data.employee_id);
        super.onCreate();

        if (this.editDialog.record.modified) {
            this.editDialog.applyChanges();
        }

    }

    onRowDblClick(grid, row, col) {
        _.set(this, 'editDialogConfig.fixedFields.employee_id', this.editDialog.record.data.employee_id);
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
Ext.reg('HumanResources.VacationCorrectionPicker', VacationCorrectionPicker);

Tine.widgets.form.FieldManager.register('HumanResources', 'Account', 'vacation_corrections', {
    xtype: 'HumanResources.VacationCorrectionPicker',
    height: 132
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

export default VacationCorrectionPicker;

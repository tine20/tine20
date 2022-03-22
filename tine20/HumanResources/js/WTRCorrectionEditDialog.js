/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.HumanResources');

class WTRCorrectionEditDialog extends Tine.widgets.dialog.EditDialog {

    checkStates() {
        super.checkStates.apply(this, arguments);
        
        const employee = this.getForm().findField('employee_id').selectedRecord;
        const isNewRecord = !this.record.get('creation_time');
        const grants = _.get(employee, 'data.division_id.account_grants', {});
        const isOwn = Tine.Tinebase.registry.get('currentAccount').accountId === _.get(employee, 'data.account_id.accountId');
        const processStatusPicker = this.getForm().findField('status')
        const processStatus = processStatusPicker.getValue();
        const allowUpdate = grants.adminGrant || grants.updateChangeRequestGrant ||
            (processStatus === 'REQUESTED' && (isNewRecord || (isOwn && grants.createOwnChangeRequestGrant) || grants.createChangeRequestGrant));

        processStatusPicker.setDisabled(!(grants.updateChangeRequestGrant || grants.adminGrant));
        if (isNewRecord && employee !== processStatusPicker.employee && (grants.updateChangeRequestGrant || grants.adminGrant)) {
            processStatusPicker.employee = employee;
            processStatusPicker.setValue('ACCEPTED');
        }

        [this.getForm().findField('title'), this.getForm().findField('description'), this.attachmentsPanel, this.action_saveAndClose].forEach((item) => {
            item[item.setReadOnly ? 'setReadOnly' : 'setDisabled'](!allowUpdate);
        });
    }
}

Tine.HumanResources.WTRCorrectionEditDialog = WTRCorrectionEditDialog;
export default WTRCorrectionEditDialog

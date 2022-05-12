/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './HTOTPSecretField';
import './WebAuthnPublicKeyDataField'

class UserConfigPanel extends Tine.Tinebase.BL.BLConfigPanel {
    /**
     * 
     * @param config
     *  account: user to create this panel for
     */
    constructor(config) {
        Object.assign(config, {
            recordClass: Tine.Tinebase.Model.MFA_UserConfig,
            dynamicRecordField: 'config',
            dataPath: 'data.mfa_configs'
        });
        super(config);
    }

    initComponent() {
        super.initComponent();

        this.BLElementPicker.emptyText = i18n._('Add new MFA Device');

        // load dynamic list of possible mfa devices for user
        return new Promise((async (resolve) => {
            const me = this;
            const mfaDevices =  !this.selfServiceMode ? await Tine.Admin.getPossibleMFAs(this.account.getId()) : await Tine.Tinebase_AreaLock.getSelfServiceableMFAs();
            const arr = _.map(mfaDevices, (record) => {
                // we use mfa_config_id as id here as config_class is not unique!
                return [record.mfa_config_id, deviceTypeRenderer(record.config_class, {}, record)];
            });

            this.BLElementPicker.store.loadData(arr);
            this.BLElementPicker.getQuickAddRecordData = function() {
                // selected mfa device needs to set config_class as well
                return {
                    id: Tine.Tinebase.data.Record.generateUID(),
                    config_class: _.find(mfaDevices, {mfa_config_id: this.store.getAt(this.selectedIndex).data.field1}).config_class,
                    mfa_config_id: this.getValue(),
                    config: {
                        id: 0
                    }
                };
            };
        }));
    }
    
}

/**
 * NOTE: device type is a combination of mfa_config_id (should be speaking) and provider
 *       this way we can distinguish between two mfa configs of the same provider!
 *       
 * @param config_class
 * @param metadata
 * @param record
 * @return {string|string}
 */
const deviceTypeRenderer = (config_class, metadata, record) => {
    const recordClass = Tine.Tinebase.data.RecordMgr.get(_.get(record, 'data.config_class', record.config_class));
    const providerName = recordClass.getRecordName();
    const mfaConfigId = _.get(record, 'data.mfa_config_id', _.get(record, 'mfa_config_id', providerName));

    return mfaConfigId + (mfaConfigId !== providerName ? ` (${i18n._hidden(providerName)})` : '');
}

Tine.widgets.grid.RendererManager.register('Tinebase', 'MFA_UserConfig', 'config_class', deviceTypeRenderer);

export default UserConfigPanel

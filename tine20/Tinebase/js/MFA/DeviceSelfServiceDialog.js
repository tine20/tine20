/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import '../BL/BLConfigPanel'
import UserConfigPanel from "./UserConfigPanel";

Ext.ns('Tine.Tinebase.MFA');

Tine.Tinebase.MFA.DeviceSelfServiceDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    windowNamePrefix: 'Tine.Tinebase.MFA.DeviceSelfServiceDialog',
    layout: 'vbox',

    initComponent() {
        this.MFAPanel = new UserConfigPanel({
            flex: 1,
            selfService: true,
            title: false,
            account: Tine.Tinebase.data.Record.setFromJson(Tine.Tinebase.registry.get('currentAccount'), Tine.Tinebase.Model.Account),
            editDialog: this,
            tbar: [this.refresh = new Ext.Toolbar.Button({
                tooltip: Ext.PagingToolbar.prototype.refreshText,
                overflowText: Ext.PagingToolbar.prototype.refreshText,
                iconCls: 'x-tbar-loading',
                handler: this.loadMFADevices,
                scope: this
            })]
        });

        // @TODO: explain stuff?
        this.items = [this.MFAPanel];

        this.supr().initComponent.call(this);

        this.loadMFADevices();
        this.MFAPanel.store.on('add', (store, records, ) => {
            if (this.isLoading) return;
            const record = records[0];
            this.saveMFADevice(record);
        });
        this.MFAPanel.store.on('update', (store, record, ) => {
            this.saveMFADevice(record);
        });
        this.MFAPanel.store.on('remove', async (store, record, ) => {
            if (! record.isLocal) {
                this.refresh.disable();
                await Tine.Tinebase_AreaLock.deleteMFAUserConfigs([record.id]);
                this.refresh.enable();
            }
        });
    },

    async loadMFADevices() {
        this.refresh.disable();
        const userConfigs = await Tine.Tinebase_AreaLock.getUsersMFAUserConfigs();
        this.isLoading = true;
        this.MFAPanel.setStoreFromArray(userConfigs);
        this.isLoading = false;
        this.refresh.enable();
    },

    async saveMFADevice(record, MFAPassword) {
        // if (! this.loadMask) {
        //     this.loadMask = new Ext.LoadMask(this.getEl(), {msg: this.app.i18n._("Transferring MFA Device...")});
        // }
        // await this.loadMask.show();

        try {
            record.isLoacl = true;
            this.refresh.disable();
            const userConfigData = await Tine.Tinebase_AreaLock.saveMFAUserConfig(record.get('mfa_config_id'), {... record.data}, MFAPassword);
            await this.loadMFADevices();
        } catch (exception) {
            this.refresh.enable();
            switch (exception.data.code) {
                case 630:
                    return Tine.Tinebase.areaLocks.unlock(exception.data.area, {
                        mfaDevices: exception.data.mfaUserConfigs,
                        USERABORTMethod: () => {
                            this.MFAPanel.store.remove(record);
                        },
                        unlockMethod: (areaName, MFAUserConfigId, MFAPassword) => {
                            this.saveMFADevice(record, MFAPassword);
                        }
                    });
                    break;
                case 631:
                    return Tine.Tinebase.areaLocks.onMFAFail(exception.area, exception, {
                        retryMethod: () => {
                            this.saveMFADevice(record);
                        },
                        USERABORTMethod: () => {
                            this.MFAPanel.store.remove(record);
                        }
                    });
                    break;
                default:
                    return Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                    break;
            }
        }
    }
});

Tine.Tinebase.MFA.DeviceSelfServiceDialog.openWindow = function(config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    return Tine.WindowFactory.getWindow({
        width: 640,
        height: 450,
        name: Tine.Tinebase.MFA.DeviceSelfServiceDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Tinebase.MFA.DeviceSelfServiceDialog',
        contentPanelConstructorConfig: config,
    });
}

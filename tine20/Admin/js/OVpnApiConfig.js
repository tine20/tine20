import MFAPanel from 'MFA/UserConfigPanel';

class AuthConfig extends MFAPanel {
    initComponent() {
        this.dataPath = 'data.auth_configs';
        // NOTE: this is wrong but we don't have a real account so let's work in the context of the current user (admin)
        this.account = new Tine.Tinebase.Model.User({}, Tine.Tinebase.registry.get('currentAccount').accountId),
        super.initComponent();
    }
    onRender() {
        super.onRender.apply(this, arguments);
        this.editDialog.on('load', this.syncName, this, {buffer: 100});
        this.editDialog.on('change', this.syncName, this, {buffer: 100});
    }
    syncName() {
        const name = this.editDialog.record.get('name');
        this.account.constructor.getFieldNames().forEach((fieldName) => {
            if (String(fieldName).match(/name/i)) {
                this.account.set(fieldName, name);
            }
        });
    }
}

Tine.widgets.form.FieldManager.register('Admin', 'OVpnApiAccount', 'auth_configs', {
    xtype: AuthConfig,
    height: 120,
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

Tine.Admin.registerItem({
    text: 'OVPN API Config', // _('OVPN API Config')
    iconCls: 'admin-node-quota-usage', // TODO icon?
    pos: 2000,
    dataPanelType: "ovpnapiconfig",
    hidden: ! (Tine.Admin.showModule('ovpnaccounts') || Tine.Admin.showModule('ovpnrealms')),
    leaf: false,
    children: [{
        text: 'Realms', // _('Realms')
        iconCls: 'admin-node-customfields', // TODO icon?
        leaf: true,
        dataPanelType: "Tine.Admin.OVpnApiRealmGridPanel",
        hidden: !Tine.Admin.showModule('ovpnrealms')
    }, {
        text: 'Accounts', // _('Accounts')
        iconCls: 'admin-node-customfields', // TODO icon?
        leaf: true,
        dataPanelType: "Tine.Admin.OVpnApiAccountGridPanel",
        hidden: !Tine.Admin.showModule('ovpnaccounts')
    }]
});


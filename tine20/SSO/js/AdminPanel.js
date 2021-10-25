/*
 * Tine 2.0
 *
 * @package     SSO
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornleius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.SSO');

Tine.SSO.AdminPanel = Ext.extend(Ext.TabPanel, {

    border: false,
    activeTab: 0,

    /**
     * @private
     */
    initComponent: function() {

        this.app = Tine.Tinebase.appMgr.get('SSO');

        this.items = [
            new Tine.SSO.RelyingPartyGridPanel({
                title: this.app.i18n._('Relying Parties'),
                disabled: !Tine.Tinebase.common.hasRight('manage_sso', 'SSO')
            }),
            new Tine.Admin.config.GridPanel({
                configApp: this.app
            })

        ];

        Tine.SSO.AdminPanel.superclass.initComponent.call(this);
    }
});

/**
 * SSO Admin Panel Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.SSO.AdminPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 470,
        name: 'admin-sso-adminpanel',
        contentPanelConstructor: 'Tine.SSO.AdminPanel',
        contentPanelConstructorConfig: config
    });
};

/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * entry point for sso login client
 */
import(/* webpackChunkName: "Tinebase/js/Tinebase" */ 'Tinebase.js').then(function (libs) {
    libs.lodash.assign(window, libs);
    require('tineInit');

    Tine.Tinebase.tineInit.renderWindow = Tine.Tinebase.tineInit.renderWindow.createInterceptor(function () {
        const mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel;
        const i18n = new Locale.Gettext();
        i18n.textdomain('SSO');
        const rpInfo = window.initialData.relyingParty;

        Tine.loginPanel = new Tine.Tinebase.LoginPanel({
            defaultUsername: Tine.Tinebase.registry.get('defaultUsername'),
            defaultPassword: Tine.Tinebase.registry.get('defaultPassword'),
            allowBrowserPasswordManager: Tine.Tinebase.registry.get('allowBrowserPasswordManager'),
            // headsUpText: i18n._('SSO'),
            infoText:
                (rpInfo.logo ? '<img class="tb-login-infotext-logo" src="' + rpInfo.logo + '" />' : '') +
                (rpInfo.label ? '<p class="tb-login-infotext-label">' + String.format(i18n._('After successful login you will be redirected to {0}'), rpInfo.label)  + '</p>' : '') +
                (rpInfo.description ? '<p class="tb-login-infotext-description">' + rpInfo.description + '</p>' : ''),
            scope: this,
            onLoginPress: function (additionalParams) {
                Ext.MessageBox.wait(window.i18n._hidden('Logging you in...'), window.i18n._hidden('Please wait'));

                const form = this.getLoginPanel().getForm();
                const values = form.getFieldValues();
                const formData = new FormData();
                formData.append('username', values.username);
                formData.append('password', values.password);
                Object.keys(window.initialData.sso).forEach((key) => {formData.append(key, window.initialData.sso[key])});
                Object.keys(additionalParams || {}).forEach((key) => {formData.append(key, additionalParams[key])});

                var xhr = new XMLHttpRequest();
                xhr.addEventListener("load", () => {
                    const isJSON = xhr.responseText.match(/^{/);
                    if (xhr.status >= 200 && xhr.status < 300 && !isJSON) {
                        window.document.body.innerHTML = xhr.responseText;
                        document.getElementsByTagName("form")[0].submit();
                    } else {
                        if (isJSON) {
                            const response = JSON.parse(xhr.responseText);
                            if (response?.error?.data) {
                                return this.onLoginFail({responseText: JSON.stringify(response.error)});
                            }
                        }
                        Ext.MessageBox.show({
                            title: window.i18n._hidden('Login failure'),
                            msg: window.i18n._hidden('Your username and/or your password are wrong!'),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR,
                            fn: () => {
                                this.getLoginPanel().getForm().findField('password').focus(true);
                            }
                        });
                    }
                });
                xhr.open("POST", `${window.location.origin}${window.location.pathname}`, true);
                xhr.withCredentials = true;
                xhr.send(formData);
            }
        });
        mainCardPanel.layout.container.add(Tine.loginPanel);
        mainCardPanel.layout.setActiveItem(Tine.loginPanel.id);
        Tine.loginPanel.doLayout();

        return false;
    });
});

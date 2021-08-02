import(/* webpackChunkName: "Tinebase/js/Tinebase" */ 'Tinebase.js').then(function (libs) {
    libs.lodash.assign(window, libs);
    require('tineInit');
    
    Tine.Tinebase.tineInit.renderWindow = Tine.Tinebase.tineInit.renderWindow.createInterceptor(function () {
        var mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel;

        Tine.loginPanel = new Tine.Tinebase.LoginPanel({
            headsUpText: window.i18n._('SSO'),
            scope: this,
            onLoginPress: function () {
                Ext.MessageBox.wait(i18n._('Logging you in...'), i18n._('Please wait'));

                const form = this.getLoginPanel().getForm();
                const values = form.getFieldValues();
                const formData = new FormData();
                formData.append('username', values.username);
                formData.append('password', values.password);
                Object.keys(window.initialData.sso).forEach((key) => {formData.append(key, window.initialData.sso[key])});
                
                var xhr = new XMLHttpRequest();
                xhr.addEventListener("load", () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        window.document.body.innerHTML = xhr.responseText;
                        document.getElementsByTagName("form")[0].submit();
                    } else {
                        //@TODO MFA
                        Ext.MessageBox.show({
                            title: i18n._('Login failure'),
                            msg: i18n._('Your username and/or your password are wrong!'),
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

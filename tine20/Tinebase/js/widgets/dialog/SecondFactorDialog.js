/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.dialog');

Tine.Tinebase.widgets.dialog.SecondFactorDialog = Ext.extend(Tine.Tinebase.widgets.dialog.PasswordDialog, {
    hasPwGen: false,
    allowEmptyPassword: true,
    height: 120,

    initComponent: function() {

        this.title = i18n._('Second Factor Password Required');

        Tine.Tinebase.widgets.dialog.SecondFactorDialog.superclass.initComponent.call(this);

        this.on('passwordEntered', function (password) {

            window.postal.publish({
                channel: "messagebus",
                topic: 'secondfactor.check'
            });

            this.loadMask = new Ext.LoadMask(this.getEl(), {
                msg: i18n._('Validating password...')
            });
            this.loadMask.show();

            Ext.Ajax.request({
                params: {
                    method: 'Tinebase.validateSecondFactor',
                    password: password,
                },
                success: function (_result, _request) {
                    this.hideLoadMask();
                    var response = Ext.util.JSON.decode(_result.responseText);
                    // TODO add data to messagebus events?

                    if (response.success) {
                        window.postal.publish({
                            channel: "messagebus",
                            topic: 'secondfactor.valid'
                        });

                        if (! Tine.Tinebase.widgets.dialog.SecondFactorDialog.presenceObserver) {
                            var secondFactorLifetime = Tine.Tinebase.registry.get('secondFactorSessionLifetime') || 15;
                            Tine.Tinebase.widgets.dialog.SecondFactorDialog.presenceObserver = new Tine.Tinebase.PresenceObserver({
                                maxAbsenceTime: secondFactorLifetime / 3, // ping server each secondFactorLifetime / 3 minutes
                                absenceCallback: function (lastPresence, po) {
                                    window.postal.publish({
                                        channel: "messagebus",
                                        topic: 'secondfactor.invalid'
                                    });
                                },
                                presenceCallback: function (lastPresence) {
                                    // report presence to server
                                    Tine.Tinebase.reportPresence(Ext.encode(lastPresence));
                                }
                            });

                        } else {
                            Tine.Tinebase.widgets.dialog.SecondFactorDialog.presenceObserver.startChecking();
                        }

                    } else {
                        window.postal.publish({
                            channel: "messagebus",
                            topic: 'secondfactor.invalid'
                        });

                        this.passwordField.setValue('');

                        // TODO fix z-index?
                        //Ext.MessageBox.show({
                        //    title: i18n._('Wrong Password'),
                        //    msg: i18n._('Invalid second factor password given'),
                        //    buttons: Ext.MessageBox.OK
                        //});
                    }
                },
                failure: function() {
                    // TODO do some more?
                    this.hideLoadMask();
                },
                scope: this
            });
        }, this);
    }
});

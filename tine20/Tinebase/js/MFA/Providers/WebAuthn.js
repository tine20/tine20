/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Abstract from './Abstract'

/* global Tine,i18n */
class WebAuthn extends Abstract {
    constructor (config) {
        super(config)
        // this.windowTitle = i18n._('MFA secret required')
        // this.questionText = formatMessage('This area is locked. To unlock it you need to provide the secret from your Multi factor authentication device {mfaDevice.device_name}.', this)
        // this.passwordFieldLabel = formatMessage('Secret from {mfaDevice.device_name}', this)
    }

    async unlock (opts) {
        const rfc4648 = await import(/* webpackChunkName: "Tinebase/js/rfc4648" */ 'rfc4648');
        const publicKeyOptions = await Tine.Tinebase.getWebAuthnAuthenticateOptionsForMFA(this.username, this.mfaDevice.id);
        const accountid = publicKeyOptions.extensions.userHandle;
        publicKeyOptions.challenge = rfc4648.base64url.parse(publicKeyOptions.challenge, { loose: true });
        for (let allowCredential of publicKeyOptions.allowCredentials) {
            allowCredential.id = rfc4648.base64url.parse(allowCredential.id, { loose: true });
        }
        
        try {
            const publicKeyCredential = await navigator.credentials.get({
                publicKey: publicKeyOptions
            });
            // NOTE: publicKeyCredential is a browser object which can't be JSON.serilized
            const publicKeyData = {
                id: publicKeyCredential.id,
                type: publicKeyCredential.type,
                rawId: rfc4648.base64url.stringify(new Uint8Array(publicKeyCredential.rawId)),
                response: {
                    clientDataJSON: rfc4648.base64url.stringify(new Uint8Array(publicKeyCredential.response.clientDataJSON)),
                    authenticatorData: rfc4648.base64url.stringify(new Uint8Array(publicKeyCredential.response.authenticatorData)),
                    signature: rfc4648.base64url.stringify(new Uint8Array(publicKeyCredential.response.signature)),
                    userHandle: rfc4648.base64url.stringify(accountid)
                }
            }

            const unlockMethod = this.unlockMethod || Tine.Tinebase_AreaLock.unlock
            await unlockMethod(this.areaName, this.mfaDevice.id, JSON.stringify(publicKeyData));
        } catch (e) {
            //@TODO!!!
            debugger
        }
        

        
        // let me = this
        // return new Promise((resolve, reject) => {
        //     let pwDlg = new Tine.Tinebase.widgets.dialog.PasswordDialog({
        //         windowTitle: me.windowTitle,
        //         questionText: me.questionText,
        //         passwordFieldLabel: me.passwordFieldLabel,
        //         allowEmptyPassword: false,
        //         hasPwGen: false,
        //         locked: !me.isOTP
        //     })
        //     pwDlg.openWindow()
        //     pwDlg.on('apply', async (password) => {
        //         try {
        //             const unlockMethod = this.unlockMethod || Tine.Tinebase_AreaLock.unlock
        //             resolve(await unlockMethod(me.areaName, me.mfaDevice.id, password))
        //         } catch (e) {
        //             reject(e)
        //         }
        //     })
        //     pwDlg.on('cancel', () => {
        //         reject(new Error('USERABORT'))
        //     })
        // })
    }
}
export default WebAuthn

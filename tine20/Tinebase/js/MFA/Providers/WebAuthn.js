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
        this.username = this.username || Tine.Tinebase.registry.get('currentAccount').accountLoginName
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
                    userHandle: rfc4648.base64url.stringify(Uint8Array.from(accountid, c => c.charCodeAt(0)))
                }
            }

            const unlockMethod = this.unlockMethod || Tine.Tinebase_AreaLock.unlock
            return unlockMethod(this.areaName, this.mfaDevice.id, JSON.stringify(publicKeyData));
        } catch (e) {
            if (await Ext.MessageBox.show({
                icon: Ext.MessageBox.WARNING,
                buttons: Ext.MessageBox.OKCANCEL,
                title: i18n._('Error'),
                msg: i18n._("FIDO2 WebAuthn authentication failed. Try again?")
            }) === 'ok') {
                return this.unlock();
            } else {
                throw new Error('USERABORT');
            }
        }
    }
}
export default WebAuthn

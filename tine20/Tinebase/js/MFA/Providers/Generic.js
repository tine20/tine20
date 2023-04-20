/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Abstract from './Abstract'

/* global Tine,i18n */
class Generic extends Abstract {
  constructor (config) {
    super(config)
    this.windowTitle = i18n._('MFA secret required')
    this.questionText = formatMessage('This area is locked. To unlock it you need to provide the secret from your Multi factor authentication device {mfaDevice.device_name}.', this)
    this.passwordFieldLabel = formatMessage('Secret from {mfaDevice.device_name}', this)
  }

  unlock (opts) {
    let me = this
    return new Promise((resolve, reject) => {
      let pwDlg = new Tine.Tinebase.widgets.dialog.PasswordDialog({
        windowTitle: me.windowTitle,
        questionText: me.questionText,
        passwordFieldLabel: me.passwordFieldLabel,
        allowEmptyPassword: false,
        hasPwGen: false,
        locked: !me.isOTP
      })
      pwDlg.openWindow()
      pwDlg.on('apply', async (password) => {
        try {
          const unlockMethod = this.unlockMethod || Tine.Tinebase_AreaLock.unlock
          resolve(await unlockMethod(me.areaName, me.mfaDevice.id, password))
        } catch (e) {
          reject(e)
        }
      })
      pwDlg.on('cancel', () => {
        reject(new Error('USERABORT'))
      })
      pwDlg.on('destroy', () => {
        reject(new Error('USERABORT'))
      })
    })
  }
}
export default Generic

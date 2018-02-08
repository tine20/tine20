/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import AbstractProvider from './AbstractProvider.es6'

/* global Tine,i18n */
class UserPasswordProvider extends AbstractProvider {
  constructor (config) {
    super(config)
    this.windowTitle = i18n._('Password required')
    this.questionText = i18n._('This area is locked. To unlock it you need to provide your password')
    this.passwordFieldLabel = i18n._('Password')
  }

  unlock () {
    let me = this
    return new Promise((resolve, reject) => {
      let pwDlg = new Tine.Tinebase.widgets.dialog.PasswordDialog({
        windowTitle: me.windowTitle,
        questionText: me.questionText,
        passwordFieldLabel: me.passwordFieldLabel,
        allowEmptyPassword: false,
        hasPwGen: false
      })
      pwDlg.openWindow()
      pwDlg.on('apply', (password) => {
        me.isUnlocking = true
        me.fireEvent('unlocking', me)
        return Tine.Tinebase_AreaLock.unlock(me.area, password)
          .finally(() => {
            me.isUnlocking = false
          })
          .then(() => {
            return super.unlock()
          })
          .catch((e) => {
            reject(e)
          })
      })
    })
  }
}
export default UserPasswordProvider

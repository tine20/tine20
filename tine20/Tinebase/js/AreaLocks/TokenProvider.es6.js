/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import UserPasswordProvider from './UserPasswordProvider.es6'

/* global i18n */
class TokenProvider extends UserPasswordProvider {
  constructor (config) {
    super(config)
    this.windowTitle = i18n._('Token required')
    this.questionText = i18n._('This area is locked. To unlock it you need to enter your Token')
    this.passwordFieldLabel = i18n._('Token')
  }
}

export default TokenProvider

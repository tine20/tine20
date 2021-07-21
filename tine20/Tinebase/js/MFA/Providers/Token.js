/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Generic from './Generic'

/* global i18n */
class Token extends Generic {
  constructor (config) {
    super(config)
    this.windowTitle = i18n._('Token required')
    this.questionText = formatMessage('This area is locked. To unlock it you need to provide your token from your {mfaDevice.device_name}.', this)
    this.passwordFieldLabel = formatMessage('Token from {mfaDevice.device_name}', this)
  }
}

export default Token

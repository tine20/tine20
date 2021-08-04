/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Generic from './Generic'

class Sms extends Generic {
    constructor (config) {
        super(config)
        this.isOTP = true
        this.windowTitle = i18n._('Authenticator code required')
        this.questionText = formatMessage('This area is locked. To unlock it you need to provide the code from your authenticator app {mfaDevice.device_name}.', this)
        this.passwordFieldLabel = formatMessage('Authenticator code from {mfaDevice.device_name}', this)
    }
}

export default Sms

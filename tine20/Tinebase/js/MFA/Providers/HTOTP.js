/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Generic from './Generic'

/* global i18n */
class HTOTP extends Generic {
    constructor (config) {
        super(config)
        this.windowTitle = i18n._('OTP required')
        this.questionText = formatMessage('This area is locked. To unlock it you need to provide your {mfaDevice.device_name} secret.', this)
        this.passwordFieldLabel = formatMessage('{mfaDevice.device_name} secret', this)
    }
}

export default HTOTP

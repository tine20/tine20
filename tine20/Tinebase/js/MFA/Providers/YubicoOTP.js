/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Generic from './Generic'

/* global Tine,i18n */
class YubicoOTP extends Generic {
    constructor (config) {
        super(config)
        this.windowTitle = i18n._('Yubico OTP required')
        this.questionText = i18n._('This area is locked. To unlock it you need to provide your Yubico OTP.')
        this.passwordFieldLabel = i18n._('Yubico OTP')
    }
}

export default YubicoOTP

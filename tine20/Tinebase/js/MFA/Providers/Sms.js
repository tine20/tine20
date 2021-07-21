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
        this.windowTitle = i18n._('SMS security code required')
        this.questionText = formatMessage('This area is locked. To unlock it we send a securitycode to via {mfaDevice.device_name}.', this)
        this.passwordFieldLabel = formatMessage('Security code from {mfaDevice.device_name}', this)
    }
    
    async unlock (opts) {
        const triggerMFAMethod = this.triggerMFAMethod || Tine.Tinebase_AreaLock.triggerMFA
        triggerMFAMethod(this.mfaDevice.id)
        return super.unlock(opts)
    }
}

export default Sms

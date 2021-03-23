/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Generic from './Generic'

/* global Tine,i18n */
class UserPassword extends Generic {
    constructor (config) {
        super(config)
        this.providerClass = 'Tinebase_Auth_SecondFactor_MockSmsAdapter'
        this.windowTitle = i18n._('Password required')
        this.questionText = i18n._('This area is locked. To unlock it you need to provide your password')
        this.passwordFieldLabel = i18n._('Password')
    }
}

export default UserPassword

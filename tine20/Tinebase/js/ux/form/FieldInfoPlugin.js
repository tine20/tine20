/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FieldTriggerPlugin from "./FieldTriggerPlugin"

class FieldInfoPlugin extends FieldTriggerPlugin {
    triggerClass = 'info'

    onTriggerClick () {

    }
}

Ext.preg('ux.fieldinfoplugin', FieldInfoPlugin);

export default FieldInfoPlugin

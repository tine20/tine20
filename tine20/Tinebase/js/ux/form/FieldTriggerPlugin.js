/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import '../../../css/ux/form/FieldTriggerPlugin.css'

class FieldTriggerPlugin {
    triggerClass = 'x-form-trigger'
    
    #trigger
    
    constructor(config) {
        _.assign(this, config)
    }
    
    async init (field) {
        this.field = field

        await field.afterIsRendered()
        const wrap = field.el.parent('.x-form-element') ||
            field.el.parent('.x-grid-editor')
        this.#trigger = wrap.createChild(this.triggerConfig ||
            {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger x-form-trigger-plugin " + this.triggerClass})

        field.mon(this.#trigger, 'click', this.onTriggerClick, this, {preventDefault:true});
        this.#trigger.addClassOnOver('x-form-trigger-over');
        this.#trigger.addClassOnClick('x-form-trigger-click');
    }
}
export default FieldTriggerPlugin



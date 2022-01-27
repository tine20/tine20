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
    visible = true
    qtip = null

    #trigger
    
    constructor(config) {
        _.assign(this, config)
    }
    
    async init (field) {
        this.field = field

        await field.afterIsRendered()
        const wrap = field.el.parent('.x-form-element') ||
            field.el.parent('.x-grid-editor')
        if (wrap) {
            this.#trigger = wrap.createChild(this.triggerConfig ||
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger x-form-trigger-plugin " + this.triggerClass})
            this.setVisible(this.visible)
            if (this.qtip) {
                this.setQtip(this.qtip)
            }

            field.mon(this.#trigger, 'click', this.onTriggerClick, this, {preventDefault:true});
            this.#trigger.addClassOnOver('x-form-trigger-over');
            this.#trigger.addClassOnClick('x-form-trigger-click');
        }
    }

    setTriggerClass(triggerClass) {
        if (this.#trigger) {
            this.#trigger.removeClass(this.triggerClass);
            this.#trigger.addClass(triggerClass);
            this.triggerClass = triggerClass;
        }
    }

    setVisible(visible) {
        this.visible = visible
        if (this.#trigger) {
            this.#trigger.setVisible(visible);
        }
    }

    setQtip(qtip) {
        this.qtip = qtip
        if (this.#trigger) {
            this.#trigger.set({ 'ext:qtip': Tine.Tinebase.common.doubleEncode(qtip) });
        }
    }
}
export default FieldTriggerPlugin



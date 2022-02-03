/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FieldTriggerPlugin from "../../ux/form/FieldTriggerPlugin"

class LocalizedLangPicker extends Tine.Tinebase.widgets.keyfield.ComboBox {

    initComponent() {
        this.hideTrigger = true
        this.width = 100

        this.plugins = this.plugins || [];
        this.plugins.push(this.triggerPlugin = new FieldTriggerPlugin({
            triggerConfig: {tag: "div", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger x-form-trigger-plugin x-form-localized-field tine-grid-cell-localized x-form-localized-picker"},
            qtip: i18n._('Some fields are  multilingual. Click here to select language to display.'),
            onTriggerClick: _.bind(this.onTriggerClick, this),
        }));
        Tine.Tinebase.widgets.keyfield.ComboBox.prototype.initComponent.call(this);
    }

    setValue(value) {
        _.defer(_.bind(this.triggerPlugin.update, this.triggerPlugin), [String(value).toUpperCase()])
        Tine.Tinebase.widgets.keyfield.ComboBox.prototype.setValue.call(this, value)
    }
}

const getLocalizedLangPicker = (recordClass) => {
    const languagesAvailableDef = _.get(recordClass.getModelConfiguration(), 'languagesAvailable')
    if (languagesAvailableDef) {
        return new LocalizedLangPicker({
            app: _.get(languagesAvailableDef, 'config.appName', recordClass.getMeta('appName')),
            keyFieldName: languagesAvailableDef.name,
        })
    }
}

export { LocalizedLangPicker, getLocalizedLangPicker }

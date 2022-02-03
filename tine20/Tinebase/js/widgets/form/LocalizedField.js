/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.form');

import FieldTriggerPlugin from "../../ux/form/FieldTriggerPlugin"

const mixin = {
    initComponent() {
        this.plugins = this.plugins || [];
        this.plugins.push(this.triggerPlugin = new FieldTriggerPlugin({
            triggerConfig: {tag: "div", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger x-form-trigger-plugin x-form-localized-field tine-grid-cell-localized"},
            onTriggerClick: _.bind(this.onTriggerClick, this),
            setLangCode: this.setLangCode,
        }));
        this.supr().initComponent.call(this)
    },

    onTriggerClick() {
        // open window with all langs?
    },

    setValue(value, record) {
        this.value = value || []

        if (! this.isConfigured) {
            const recordClass = record.constructor
            const localizedLangPicker = this.findParentBy((c) => {return c.localizedLangPicker})?.localizedLangPicker
            if (localizedLangPicker) {
                this.lang = localizedLangPicker.getValue()
                localizedLangPicker.on('change', (picker, lang) => {
                    const value = this.getValue() // sync value
                    this.lang = lang
                    this.setValue(value)
                })
            } else {
                const languagesAvailableDef = _.get(recordClass.getModelConfiguration(), 'languagesAvailable')
                const keyFieldDef = Tine.Tinebase.widgets.keyfield.getDefinition(_.get(languagesAvailableDef, 'config.appName', recordClass.getMeta('appName')), languagesAvailableDef.name)
                this.lang = keyFieldDef.default
            }

            this.isConfigured = true
        }

        this.localized = _.find(this.value, { language: this.lang })
        if (! this.localized) {
            this.localized = { language: this.lang, text: '' }
            this.value.push(this.localized)
        }

        const text = Ext.util.Format.htmlEncode(_.get(this.localized, 'text', ''))
        const langCode = _.get(this.localized, 'language', '').toUpperCase()

        this.setRawValue(text)
        this.triggerPlugin.update(langCode)
    },

    getValue() {
        _.set(this.localized, 'text', this.supr().getValue.call(this))
        return this.value;
    }
}

Ext.reg('tw-localized-string-text-field', Ext.extend(Ext.form.TextField, mixin));
Ext.reg('tw-localized-string-text-area', Ext.extend(Ext.form.TextArea, mixin));

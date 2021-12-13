/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.form');

Tine.Tinebase.widgets.form.LanguagePicker = Ext.extend(Ext.form.ComboBox, {
    // allowBlank: false,
    forceSelection: true,
    // displayField: 'modelName',
    // valueField: 'className',
    mode: 'local',

    initComponent() {
        // grr. ext validates value not key
        this.maxLength = 255;

        const allLanguages = Locale.getTranslationList('Language');
        // const availableModelsRegExp = this.availableModelsRegExp ? new RegExp(this.availableModelsRegExp.replaceAll('/','')) : null;

        this.store = Object.entries(allLanguages);

        this.supr().initComponent.call(this);
    }
});

Ext.reg('tw-languagepicker', Tine.Tinebase.widgets.form.LanguagePicker);

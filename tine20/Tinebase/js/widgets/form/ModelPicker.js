/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.form');

Tine.Tinebase.widgets.form.ModelPicker = Ext.extend(Ext.form.ComboBox, {
    includeAppName: true,

    availableModels: null,

    availableModelsRegExp: null,

    allowBlank: false,
    forceSelection: true,
    displayField: 'modelName',
    valueField: 'className',
    mode: 'local',

    initComponent() {
        const availableModels = this.availableModels?.map((model) => { return Tine.Tinebase.data.RecordMgr.get(model) });
        const availableModelsRegExp = this.availableModelsRegExp ? new RegExp(this.availableModelsRegExp.replaceAll('/','')) : null;

        this.store = new Ext.data.ArrayStore({
            fields: ['className', 'modelName'],
            data: Tine.Tinebase.data.RecordMgr.items.reduce((models, recordClass) => {
                let name = recordClass.getRecordsName();
                name = !name || name === 'records'? recordClass.getMeta('modelName') : name;

                const label = (this.includeAppName ? recordClass.getAppName() + ' ' : '') + name;
                const className = recordClass.getPhpClassName();

                if ((!availableModels || availableModels.indexOf(recordClass) >= 0)
                    && (!availableModelsRegExp || availableModelsRegExp.test(className))) {
                    models.push([className, label]);
                }

                return models;
            }, [])
        });

        this.supr().initComponent.call(this)
    }
});

Ext.reg('tw-modelpicker', Tine.Tinebase.widgets.form.ModelPicker);

/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

Ext.ns('Tine.Tinebase.widgets.form.RecordEditField');


Tine.Tinebase.widgets.form.RecordEditField = Ext.extend(Ext.form.TriggerField, {

    itemCls: 'tw-recordEditField',
    triggerClass: 'action_edit',
    editable: false,
    
    initComponent: async function () {
        var _ = window.lodash;

        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.appName, this.modelName);
        this.emptyText = i18n._('No record');

        Tine.Tinebase.widgets.form.RecordEditField.superclass.initComponent.call(this);
    },

    setValue : function(v, owningRecord){
        this.recordData = _.get(v, 'data', v);
        
        // if the field is a dynamicReccord, get classname and adopt thi.recordClass
        const owningRecordClass = _.get(owningRecord, 'constructor');
        const owningRecordFieldDefinitions = _.get(owningRecordClass, 'getFieldDefinitions') ? owningRecordClass.getFieldDefinitions() : null;
        const ownFieldDefinition = _.get(_.find(owningRecordFieldDefinitions, {name: this.fieldName}), 'fieldDefinition');
        const classNameField = _.get(ownFieldDefinition, 'config.refModelField');
        const className = _.get(owningRecord, 'data.'+classNameField);
        this.recordClass = className ? Tine.Tinebase.data.RecordMgr.get(className) || this.recordClass : this.recordClass;

        let valueRecord = this.recordClass && this.recordData ? Tine.Tinebase.data.Record.setFromJson(this.recordData, this.recordClass) : null;
        Tine.Tinebase.widgets.form.RecordEditField.superclass.setValue.call(this, valueRecord ? valueRecord.getTitle() : '');
    },

    onTriggerClick: function () {
        let me = this;
        let editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(this.recordClass);

        if (editDialogClass) {
            editDialogClass.openWindow({
                mode: 'local',
                record: this.recordData,
                listeners: {
                    scope: me,
                    'update': (updatedRecord) => {
                        let record = !updatedRecord.data ? Tine.Tinebase.data.Record.setFromJson(updatedRecord, me.recordClass) : updatedRecord;
                        Tine.Tinebase.common.assertComparable(record);
                        this.setValue(record);
                    },
                    'cancel': () => {
                        if (new Date().getTime() - 1000 < this.blurOnSelectLastRun) return;
                        _.delay(() => {
                            this.blurOnSelectLastRun = new Date().getTime();
                            const focusClass = this.focusClass;
                            this.focusClass = '';
                            Ext.form.TriggerField.superclass.onBlur.call(this);
                            this.focusClass = focusClass;
                        }, 100);
                    }
                }
            });
        }
    },
    
    getValue : function(){
       return this.recordData;
    },

});

Ext.reg('tw-recordEditField', Tine.Tinebase.widgets.form.RecordEditField);

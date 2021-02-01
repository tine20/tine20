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
    
    initComponent: async function () {
        var _ = window.lodash;

        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.appName, this.modelName);
        this.emptyText = i18n._('No record');

        Tine.Tinebase.widgets.form.RecordEditField.superclass.initComponent.call(this);
    },

    setValue : function(v){
        this.recordData = _.get(v, 'data', v);
        
        let record = Tine.Tinebase.data.Record.setFromJson(this.recordData, this.recordClass);
        Tine.Tinebase.widgets.form.RecordEditField.superclass.setValue.call(this, record.getTitle());
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
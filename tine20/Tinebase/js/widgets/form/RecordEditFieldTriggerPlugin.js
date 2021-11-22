/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FieldTriggerPlugin from "../../ux/form/FieldTriggerPlugin"

class RecordEditFieldTriggerPlugin extends FieldTriggerPlugin {
    allowCreateNew = true
    /**
     * properties from record.json to preserve when editing (see Ext.copyTo for syntax)
     * @type {string}
     */
    preserveJsonProps = ''

    triggerClass = 'action_edit'

    constructor(config) {
        super(config)
        _.assign(this, config)
    }

    async init (field) {
        this.visible = this.allowCreateNew
        await super.init(field)
        field.setValue = field.setValue.createSequence((value) => {
            this.setVisible(!!field.selectedRecord && !this.allowCreateNew);
        })
        field.clearValue = field.clearValue.createSequence(() => {
            this.setVisible(!!field.selectedRecord&& !this.allowCreateNew);
        })
    }
    onTriggerClick () {
        // let me = this;
        let editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(this.field.recordClass);

        if (editDialogClass) {
            editDialogClass.openWindow({
                mode: 'local',
                record: this.field.selectedRecord,
                listeners: {
                    scope: this,
                    'update': (updatedRecord) => {
                        let record = !updatedRecord.data ? Tine.Tinebase.data.Record.setFromJson(updatedRecord, this.field.recordClass) : updatedRecord;
                        Tine.Tinebase.common.assertComparable(record);
                        Ext.copyTo(record.json, this.field.selectedRecord.json, this.preserveJsonProps)
                        // here we loose record.json data from old record! -> update existing record? vs. have preserveJSON props? // not a problem?
                        this.field.setValue(record);
                    },
                    'cancel': () => {
                        if (new Date().getTime() - 1000 < this.field.blurOnSelectLastRun) return;
                        _.delay(() => {
                            this.field.blurOnSelectLastRun = new Date().getTime();
                            const focusClass = this.field.focusClass;
                            this.field.focusClass = '';
                            Ext.form.TriggerField.superclass.onBlur.call(this.field);
                            this.field.focusClass = focusClass;
                        }, 100);
                    }
                }
            });
        }
    }
}

export default RecordEditFieldTriggerPlugin

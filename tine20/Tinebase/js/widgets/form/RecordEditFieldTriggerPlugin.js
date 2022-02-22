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

    editDialogMode = null

    triggerClass = 'action_edit'

    constructor(config) {
        super(config)
        _.assign(this, config)
    }

    async init (field) {
        this.visible = this.allowCreateNew
        await super.init(field)
        this.assertState()
        field.setValue = field.setValue.createSequence(_.bind(this.assertState, this))
        field.clearValue = field.clearValue.createSequence(_.bind(this.assertState, this))
    }
    
    assertState() {
        this.setVisible((!!this.field.selectedRecord || this.allowCreateNew) && !this.field.readOnly && !this.field.disabled);
        this.setTriggerClass(!!this.field.selectedRecord ? 'action_edit' : 'action_add');
    }

    // allow to configure defaults from outside
    async getRecordDefaults() {
        return {}
    }

    async onTriggerClick () {
        // let me = this;
        let editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(this.field.recordClass);

        if (editDialogClass) {
            const record = this.field.selectedRecord || Tine.Tinebase.data.Record.setFromJson(Ext.apply(this.field.recordClass.getDefaultData(), await this.getRecordDefaults()), this.field.recordClass);
            const mode = this.editDialogMode ?? editDialogClass.prototype.mode;

            if (!this.field.selectedRecord && mode === 'remote') {
                // prevent loading non existing remote record
                record.setId(0);
            }

            editDialogClass.openWindow({mode, record,
                recordId: record.getId(),
                listeners: {
                    scope: this,
                    'update': (updatedRecord) => {
                        let record = !updatedRecord.data ? Tine.Tinebase.data.Record.setFromJson(updatedRecord, this.field.recordClass) : updatedRecord;
                        Tine.Tinebase.common.assertComparable(record.data);
                        if (this.field.selectedRecord && this.preserveJsonProps) {
                            Ext.copyTo(record.json, this.field.selectedRecord.json, this.preserveJsonProps)
                        }
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

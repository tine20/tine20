import getTierTypes from './tierTypes'

Ext.ux.ItemRegistry.registerItem('Filemanager-Node-EditDialog-TabPanel',  Ext.extend(Ext.Panel, {
    border: false,
    frame: true,
    requiredGrant: 'editGrant',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('EFile');
        this.title = this.app.getTitle();
        this.recordClass = Tine.EFile.Model.FileMetadata;
        
        const metaDataFieldManager = _.bind(Tine.widgets.form.FieldManager.get,
            Tine.widgets.form.FieldManager, 'EFile', 'FileMetadata', _,
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
        
        this.gotoFileButton = new Ext.Button({
            iconCls: 'efile-tiertype-file',
            text: this.app.i18n._('Jump to File'),
            handler: this.onGotoFileClick,
            scope: this,
            hidden: true
        });
        
        const mflds = this.metadataFields = {};
        _.each(Tine.widgets.form.RecordForm.getFieldDefinitions(this.recordClass), (fieldDefinition) => {
            const fieldName = fieldDefinition.fieldName
            const config = {};
            switch (fieldName) {
                case 'paper_file_location':
                    config.checkState = function() {
                        const checked = mflds.is_hybrid.getValue();
                        const value = this.getValue();
                        if (!checked && value) {
                            this.setValue('');
                            this.lastValue = value;
                        } else if (checked && !value && this.lastValue) {
                            this.setValue(this.lastValue);
                            this.lastValue = undefined;
                        }
                        this.setDisabled(!checked);
                    }
                    break;
                case 'final_decree_date':
                case 'final_decree_by':
                case 'retention_period':
                case 'retention_period_end_date':
                    config.checkState = function() {
                        const checked = mflds.is_closed.getValue();
                        this.setDisabled(!checked);
                        if (checked && fieldName === 'final_decree_date' && !this.getValue()) {
                            this.setValue(new Date().clearTime());
                        }
                        if (fieldName === 'retention_period_end_date') {
                            if (checked && !this.getValue()) {
                                mflds.retention_period_end_date.computeDate();
                            }
                            if (mflds.retention_period.getValue() === 'ETERNALLY') {
                                this.setDisabled(true);
                            }
                        }
                    }
                    if (fieldName === 'retention_period') {
                        _.set(config, 'listeners.change', (field, newValue) => {
                            mflds.retention_period_end_date.computeDate();
                        });
                    }
                    if (fieldName === 'retention_period_end_date') {
                        config.computeDate = function () {
                            const finalDecreeDate = mflds.final_decree_date.getValue();
                            const retentionPeriod = parseInt(mflds.retention_period.getValue(), 10);
                            let date = '';
                            if (_.isDate(finalDecreeDate) && retentionPeriod) {
                                date = finalDecreeDate.add(Date.YEAR, retentionPeriod);
                                if (date.format('m-d') !== '01-01') {
                                    date = Date.parseDate((parseInt(date.format('Y'), 10) + 1)+ '-01-01', 'Y-m-d');
                                }
                            }

                            this.setValue(date);
                        }
                    }
                    break;
                case 'disposal_type':
                case 'disposal_date':
                case 'archive_name':
                    config.checkState = function() {
                        const checked = mflds.is_disposed.getValue();
                        this.setDisabled(!checked);
                        if (checked && fieldName === 'disposal_date' && !this.getValue()) {
                            this.setValue(new Date().clearTime());
                        }
                        if (fieldName === 'archive_name') {
                            const disposalType = mflds.disposal_type.getValue();
                            this[disposalType === 'QUASHED' ? 'hide' : 'show']();
                            if (disposalType === 'QUASHED') {
                                this.setValue('')
                            }
                        }
                    }
                    break;
            }

            this.metadataFields[fieldName] =  Ext.create(metaDataFieldManager(fieldName, config));
        });
        
        this.items = [{
            xtype: 'columnform',
            defaults: {columnWidth: 0.5},
            items: [
                [mflds.duration_start, mflds.duration_end],
                [_.assign(mflds.commissioned_office, {columnWidth: 2/3})],
                [_.assign(mflds.is_hybrid, {columnWidth: 2/3})], 
                [_.assign(mflds.paper_file_location, {columnWidth: 2/3})],

                [_.assign(mflds.is_closed, {columnWidth: 2/3})],
                [mflds.final_decree_date, mflds.final_decree_by],
                [mflds.retention_period, mflds.retention_period_end_date],

                [_.assign(mflds.is_disposed, {columnWidth: 2/3})],
                [mflds.disposal_type, mflds.disposal_date],
                [_.assign(mflds.archive_name, {columnWidth: 2/3})],
            ]
        }];

        this.supr().initComponent.call(this);
    },

    onRecordLoad: async function(editDialog, record) {
        const mflds = this.metadataFields;
        const tierTypes = _.map(await getTierTypes(), 'tierType');
        
        const tierType = record.get('efile_tier_type');
        const typeIsFileParent = tierType ? _.indexOf(tierTypes, tierType) < _.indexOf(tierTypes, 'file') : undefined;
        
        // NOTE: have fast UI alignment (before async request starts)
        this.gotoFileButton[tierType && !typeIsFileParent && tierType !== 'file' ? 'show' : 'hide']();
        this.ownerCt[(tierType && !typeIsFileParent ? 'un' : '') +'hideTabStripItem'](this);

        this.fileData = null;
        if (tierType) {
            if (typeIsFileParent) {
                // hide all mflds
            } else if (tierType === 'file') {
                this.fileData = record.data;
            } else {
                this.fileData = await Tine.Filemanager.getParentNodeByFilter(record.id, [{
                    field: 'efile_tier_type',
                    operator: 'equals',
                    value: 'file'
                }]);
            }
        }
        
        if (this.fileData) {
            const fileMetadata = _.get(this.fileData, 'efile_file_metadata.data', _.get(this.fileData, 'efile_file_metadata'));
            this.metadataRecord = Tine.Tinebase.data.Record.setFromJson(fileMetadata, this.recordClass);
            
            this.metadataRecord.fields.each((fieldDef) => {
                const field = mflds[fieldDef.name];
                if (field) {
                    if (!fileMetadata || !fileMetadata.hasOwnProperty(fieldDef.name)) {
                        // apply field default
                        _.set(this.metadataRecord, 'data.' + fieldDef.name, field.getValue());
                    } else {
                        // set from given value
                        field.setValue(this.metadataRecord.get(fieldDef.name));
                    }
                }
            });
            
        } else {
            this.metadataRecord = null;
        }
        
        _.each(mflds, (field) => {
            field.setReadOnly(tierType !== 'file');
            field[this.metadataRecord ? 'show' : 'hide']();
        });
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        // @TODO: set panel to readonly if user has no grants!
    },

    onRecordUpdate: function(editDialog, record) {
        const mflds = this.metadataFields;
        const tierType = record.get('efile_tier_type');
        
        if (tierType === 'file' && this.metadataRecord) {
            _.each(mflds, (field, fieldName) => {
                this.metadataRecord.set(fieldName, field.getValue());
            });
            if (_.keys(this.metadataRecord.getChanges()).length) {
                record.set('efile_file_metadata', this.metadataRecord);
            }
        }
    },

    setOwnerCt: function(ct) {
        this.ownerCt = ct;
        
        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        this.editDialog.getToolbar().add(this.gotoFileButton);
        
        // NOTE: in case record is already loaded
        if (! this.setOwnerCt.initialOnRecordLoad) {
            this.setOwnerCt.initialOnRecordLoad = true;
            this.onRecordLoad(this.editDialog, this.editDialog.record);
        }
        
    },

    onGotoFileClick: function() {
        const path = _.get(this.fileData, 'path');
        Tine.Tinebase.appMgr.get('Filemanager').showNode(path);
        this.editDialog.onCancel();
    }
    
}), 5);



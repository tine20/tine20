/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.grid');

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.MappingPickerGridPanel
 * @extends     Tine.widgets.grid.PickerGridPanel
 *
 *
 * pick mapped dependent records with can be grouped by other records optionally
 *
 * Example: Project Members(Addressbook.Contact) grouped by Addressbook.Lists
 *
 * NOTE: Members must be defined as mapped dependent records because the (single) referenceId is stored on the depended
 *       record side - we can't add a field to Contacts AND a Contact might me member of multiple Projects.
 *
   projectMemberGroupsGrid = Ext.extend(Tine.widgets.grid.MappingPickerGridPanel, {
       field: 'membergroups',
       mappingField: 'groupMemberId'
   });
   projectMembersGrid = Ext.extend(Tine.widgets.grid.MappingPickerGridPanel, {
       field: 'members',
       mappingField: 'memberId',
       groupRecordsGrid: projectMemberGroupsGrid
   });

 * you end up with two members grids, one for lists one for contacts
 * OK, this is a bad example as you might expect this in one grid with typed members
 * @TODO: rethink should both grids be part of this picker?
 *        than the group grid could optional be merged as typed records here
 *
 */
Tine.widgets.grid.MappingPickerGridPanel = Ext.extend(Tine.widgets.grid.PickerGridPanel, {

    /**
     * @cfg {String} field - required!
     * field of mapping records.
     */
    field: null,
    /**
     * @cfg {String} mappingField - required!
     * forward reference field in mapping record.
     */
    mappingField: null,
    /**
     * @cfg {String} groupRecordsGrid - optional
     * property name of grid which group records. The groups get automatically resolved when specified
     */
    groupRecordsGrid: null,

    frame: true,
    height: 200,

    initComponent: function() {
        var _ = window.lodash,
            me = this,
            parent = me.findParentBy(function(c) {return c.editDialog}),
            editDialog = _.get(parent, 'editDialog'),
            mappingFieldDef = editDialog.recordClass.getField(me.field),
            mappingRecordClass = mappingFieldDef.getRecordClass(),
            recordDef = mappingRecordClass.getField(me.mappingField),
            recordClass = recordDef.getRecordClass();

        editDialog.on('load', me.onRecordLoad, me);
        editDialog.on('save', me.onRecordSave, me);

        me.editDialog = editDialog;
        me.recordClass = recordClass;
        me.mappingRecordClass = mappingRecordClass;
        me.store = new Tine.Tinebase.data.RecordStore({
            readOnly: true,
            autoLoad: false,
            recordClass: me.recordClass
        });
        me.store.on('add', me.onRecordSave, me);
        me.store.on('update', me.onRecordSave, me);
        me.store.on('remove', me.onRecordSave, me);

        me.referenceField = mappingFieldDef.fieldDefinition.config.refIdField;

        me.title = recordClass.getRecordsName();

        me.mappingRecordStore = new Tine.Tinebase.data.RecordStore({
            readOnly: true,
            autoLoad: false,
            recordClass: me.mappingRecordClass
        });

        me.afterIsRendered().then(function() {
            var groupRecordStore = _.get(me.editDialog, [me.groupRecordsGrid, 'store'].join('.')),
                groupField = _.get(me.editDialog, [me.groupRecordsGrid, 'field'].join('.')),
                groupMappingField = _.get(me.editDialog, [me.groupRecordsGrid, 'mappingField'].join('.')),
                columns = [].concat(me.colModel.config);

            if (groupRecordStore) {
                groupRecordStore.on('add', me.onRecordLoad, me);
                groupRecordStore.on('remove', me.onRecordLoad, me);

                columns.unshift({
                    dataIndex: me.recordClass.getMeta('idProperty'),
                    header: i18n._('via'),
                    renderer: function(id, meta, record) {
                        return _.reduce(me.mappingRecordStore.data.items, function(names, mappingRecord) {
                            if (_.get(mappingRecord, 'data.' + me.mappingField) == id) {
                                var groupRecordId = _.get(mappingRecord, 'json.' + groupMappingField, ''),
                                    groupRecord = groupRecordStore.getById(groupRecordId);

                                if (groupRecord) {
                                    names.push(groupRecord.getTitle());
                                }
                            }
                            return names;
                        }, []).join(', ');
                    }
                });

                me.colModel.setConfig(columns);
            }

        });

        Tine.widgets.grid.MappingPickerGridPanel.superclass.initComponent.apply(me, arguments);
    },

    onRecordLoad: function() {
        var _ = window.lodash,
            me = this,
            mappingRecordsData = _.get(me.editDialog.record, 'data.' + me.field) || [],
            recordIds = _.reduce(mappingRecordsData, function(ids, mappingRecordData) {
                var recordId = mappingRecordData[me.mappingField];

                return ids.concat([recordId]);
            }, []);

        me.mappingRecordStore.loadData({results: mappingRecordsData});
        me.resolveGroupRecords(recordIds).then(function() {
            me.store.load({
                params: {
                    filter: [
                        {field: 'id', operator: 'in', value: recordIds}
                    ]
                }
            });
        });
    },

    // resolve corresponding records from groupField
    resolveGroupRecords: function(recordIds) {
        var _ = window.lodash,
            me = this,
            groupField = _.get(me.editDialog, [me.groupRecordsGrid, 'field'].join('.')),
            groupMappingField = _.get(me.editDialog, [me.groupRecordsGrid, 'mappingField'].join('.')),
            groupRecordsData = _.get(me.editDialog.record, 'data.' + groupField) || [];

        return !groupRecordsData.length ? Promise.resolve() :
            new Promise(function(resolve) {
                var groupMappingFieldDef = me.recordClass.getField(groupField),
                    groupMappingRecoredClass = groupMappingFieldDef.getRecordClass(),
                    groupFieldDef = groupMappingRecoredClass.getField(groupMappingField),
                    groupRecoredClass = groupFieldDef.getRecordClass(),
                    getGroupRecordMethod = _.get(Tine, groupRecoredClass.getMeta('appName') + '.get' + groupRecoredClass.getMeta('modelName')),
                    getRecordPromises = _.reduce(groupRecordsData, function(promises, groupRecordData) {
                        return promises.concat(getGroupRecordMethod(groupRecordData[groupMappingField]));
                    }, []);

                me.showLoadMask();

                Promise.all(getRecordPromises).then(function(values) {
                    _.each(values, function(groupRecordData) {
                        var groupRecord = Tine.Tinebase.data.Record.setFromJson(groupRecordData, groupRecoredClass),
                            groupTitle = groupRecord.getTitle(),
                            mappingRecordsData = _.get(groupRecordData, me.field) || [];

                        me.mappingRecordStore.loadData({results: mappingRecordsData}, true);
                        _.each(mappingRecordsData, function(mappingRecordData) {
                            var recordId = mappingRecordData[me.mappingField];

                            recordIds.push(recordId);
                        });

                    });
                    resolve();
                });

            });
    },

    onRecordSave: function() {
        var _ = window.lodash,
            me = this,
            recordsData = Tine.Tinebase.common.assertComparable([]);

        _.each(me.store.data.items, function(record, idx) {
            var mappingRecord = {},
                existingMappingIdx = me.mappingRecordStore.find(me.mappingField, record.id),
                existingMappingRecord = existingMappingIdx >= 0 ? me.mappingRecordStore.getAt(existingMappingIdx) : null,
                existingRefId = existingMappingRecord ? existingMappingRecord.get(me.referenceField) : null;

            if (me.isGroupRecord(record)) return;

            mappingRecord[me.mappingField] = record.id;
            mappingRecord[me.referenceField] = me.editDialog.record.id;

            recordsData.push(mappingRecord);
        });

        me.editDialog.record.set(this.field, recordsData);
    },

    actionRemoveUpdater: function(action, grants, records) {
        var _ = window.lodash,
            me = this,
            containsGroupRecord = _.reduce(records, function(r, s) {
                return r || me.isGroupRecord(s);
            }, false);

        action.setDisabled(containsGroupRecord);
    },

    isGroupRecord: function(record) {
        var me = this,
            existingMappingIdx = me.mappingRecordStore.find(me.mappingField, record.id),
            existingMappingRecord = existingMappingIdx >= 0 ? me.mappingRecordStore.getAt(existingMappingIdx) : null,
            existingRefId = existingMappingRecord ? existingMappingRecord.get(me.referenceField) : null;

        if (existingMappingRecord && !existingRefId) return true;
        if (existingRefId && existingRefId != me.editDialog.record.id) return true;

        return false;
    }

});
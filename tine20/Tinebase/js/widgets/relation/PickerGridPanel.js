/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

import waitFor from 'util/waitFor.es6';

Ext.ns('Tine.widgets.relation');

/**
 * related model specific picker grid
 *
 * acts as a 'mirror' of the GenericPickerGridPanel for _one_ related model of _one_ relation_type and _one_ relation_degree
 *
 * @TODO respect relation constraints
 */
Tine.widgets.relation.PickerGridPanel = Ext.extend(Tine.widgets.grid.PickerGridPanel, {
    /**
     * @cfg {Tine.widgets.dialog.EditDialog}
     */
    editDialog: null,

    /**
     * record class of related record
     *
     * @cfg {Tine.Tinebase.data.Record} recordClass
     */
    recordClass: null,

    /**
     * relation degree of the relation
     * @cfg {String} relationType
     */
    relationDegree: 'sibling',

    /**
     * relation type of the relation
     * @cfg {String} relationType
     */
    relationType: null,

    // private
    suspendOwnStoreEvents: false,
    suspendRelationStoreEvents: false,

    initComponent: function () {
        if (!this.app && this.recordClass) {
            this.app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
        }

        this.ownRecordIdProp = this.recordClass.getMeta('idProperty');

        this.asyncInit();
        this.supr().initComponent.call(this);
    },

    asyncInit: async function() {
        await waitFor(() => { return this.editDialog.relationsPanel });

        this.store.on('add', this.onOwnStoreAdd, this);
        this.store.on('update', this.onOwnStoreUpdate, this);
        this.store.on('remove', this.onOwnStoreRemove, this);

        this.editDialog.relationsPanel.store.on('add', this.onRelationStoreAdd, this);
        this.editDialog.relationsPanel.store.on('update', this.onRelationStoreUpdate, this);
        this.editDialog.relationsPanel.store.on('remove', this.onRelationStoreRemove, this);

        // question: are related record updates persistent before dlg is saved?
        // changes are reflected immediately
        // new record are created after record save // this might be hard to implement?
    },

    onOwnStoreAdd: function(store, records, idx) {
        if (! this.suspendOwnStoreEvents) {
            _.each(records, (record) => {
                const existingRelation = this.getRelation(record);
                if (existingRelation) {
                    this.onOwnStoreUpdate(store, record, 'UPDATE');
                } else {
                    this.withSuspendedRelationStoreEvents(async () => {
                        await this.editDialog.relationsPanel.onAddRecord(records[0], {
                            type: this.relationType,
                            related_model: this.recordClass.getPhpClassName(),
                            related_degree: this.relationDegree
                        });
                    });
                }
            });
        }
    },

    onOwnStoreUpdate: function(store, record, operation) {
        // NOTE: we're dealing with relations - we don't want to have the relation of the relations!
        record.data.relations = null;
        delete record.data.relations;

        if (! this.suspendOwnStoreEvents) {
            const relation = this.getRelation(record);
            this.withSuspendedRelationStoreEvents(() => {
                relation.set('related_record', record.data);
                relation.commit();
            });
        }
    },

    onOwnStoreRemove: function(store, record, idx) {
        if (! this.suspendOwnStoreEvents) {
            this.editDialog.relationsPanel.store.remove(this.getRelation(record));
        }
    },

    onRelationStoreAdd: function (store, relations, idx) {
        if (! this.suspendRelationStoreEvents) {
            _.each(relations, (relation) => {
                if (this.isMyRelationKind(relation)) {
                    const record = Tine.Tinebase.data.Record.setFromJson(relation.get('related_record'), this.recordClass);
                    if (!this.store.getById(record.getId())) {
                        this.withSuspendedOwnStoreEvents(() => {
                            this.store.add(record);
                        });
                    } else {
                        this.onRelationStoreUpdate(store, relation, 'UPDATE')
                    }
                }
            });
        }
    },

    onRelationStoreUpdate: function (store, relation, operation) {
        if (! this.suspendRelationStoreEvents) {
            if (this.isMyRelationKind(relation)) {
                const record = Tine.Tinebase.data.Record.setFromJson(relation.get('related_record'), this.recordClass);
                const existingRecord = this.store.getById(record.getId());
                if (existingRecord) {
                    this.withSuspendedOwnStoreEvents(() => {
                        const idx = this.store.indexOf(record);
                        this.store.remove(existingRecord);
                        this.store.insert(idx, [record]);
                    });
                } else {
                    this.onRelationStoreAdd(store, [relation], this.store.getCount());
                }
            }
        }
    },

    onRelationStoreRemove: function (store, relation, idx) {
        if (! this.suspendRelationStoreEvents) {
            if (this.isMyRelationKind(relation)) {
                const record = Tine.Tinebase.data.Record.setFromJson(relation.get('related_record'), this.recordClass);
                const existingRecord = this.store.getById(record.getId());
                if (existingRecord) {
                    this.withSuspendedOwnStoreEvents(() => {
                        this.store.remove(existingRecord);
                    });
                }
            }
        }
    },

    withSuspendedOwnStoreEvents: async function (fn) {
        const current = this.suspendOwnStoreEvents;
        this.suspendOwnStoreEvents = true;
        await fn();
        this.suspendOwnStoreEvents = current;
    },

    withSuspendedRelationStoreEvents: async function (fn) {
        const current = this.suspendRelationStoreEvents;
        this.suspendRelationStoreEvents = true;
        await fn();
        this.suspendRelationStoreEvents = current;
    },

    isMyRelationKind: function (relation) {
        return relation.get('type') === this.relationType
        && relation.get('related_degree') === this.relationDegree
        && relation.get('related_model') === this.recordClass.getPhpClassName()
    },

    // @TODO move to relationsPanel - also needed by picker
    getRelation: function(record) {
        const idx = this.editDialog.relationsPanel.store.findBy((relation) => {
            if (this.isMyRelationKind(relation)) {
                const relatedRecord = relation.get('related_record');
                const relatedRecordId = _.get(relatedRecord, 'data.' + this.ownRecordIdProp, _.get(relatedRecord, this.ownRecordIdProp));
                return relatedRecordId === record.getId();
            }
        });

        return this.editDialog.relationsPanel.store.getAt(idx);
    }

});
Ext.reg('tinerelationpickergridpanel', Tine.widgets.relation.PickerGridPanel);
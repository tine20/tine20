/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('./SignatureEditDialog');

Tine.Felamimail.SignatureGridPanel = Ext.extend(Ext.grid.GridPanel, {

    editDialog: null,

    // private
    enableHdMenu: false,
    autoExpandColumn: 'name',
    border: false,

    initComponent: function() {
        let me = this;

        me.app = Tine.Tinebase.appMgr.get('Felamimail');

        me.title = me.app.i18n._('Signatures');

        me.recordClass = Tine.Tinebase.data.RecordMgr.get('Felamimail.Model.Signature');
        me.store = new Ext.data.JsonStore({
            autoLoad: false,
            fields: me.recordClass,
            sortInfo: {
                field: 'name',
                direction: 'ASC'
            }
        });

        me.columns = [
            {id: 'name', dataIndex: 'name', header: me.app.i18n._('Name'), sortable: false},
            me.checkColumn = new Ext.ux.grid.CheckColumn({
                id: 'is_default',
                header: me.app.i18n._('Is Default'),
                dataIndex: 'is_default',
                width: 100,
                listeners: {checkchange: _.bind(me.onCheckChange, me)}
            })
        ];

        me.plugins = [me.checkColumn];

        me.tbar = [{
            ref: '../addAction',
            text: me.app.i18n._('Add Signature'),
            iconCls: 'action_add',
            handler: _.bind(me.onEditSignature, me, 'add')
        }, {
            ref: '../editAction',
            text: me.app.i18n._('Edit Signature'),
            iconCls: 'action_edit',
            disabled: true,
            handler: _.bind(me.onEditSignature, me, 'edit')
        }, {
            ref: '../deleteAction',
            text: me.app.i18n._('Delete Signature'),
            iconCls: 'action_delete',
            disabled: true,
            handler: _.bind(me.onDeleteSignature, me),
        }];

        me.selModel = new Ext.grid.RowSelectionModel({
            // singleSelect: true,
            listeners: {
                selectionchange: _.bind(me.onSelectionChange, me)
            }
        });

        me.on('rowdblclick', _.bind(me.onEditSignature, me, 'edit'));

        me.editDialog.on('load', me.onRecordLoad, me);
        me.editDialog.on('save', me.onRecordUpdate, me);

        Tine.Felamimail.SignatureGridPanel.superclass.initComponent.call(this);
    },

    onSelectionChange: function(sm) {
        let me = this;
        me.editAction.setDisabled(sm.getCount() !== 1);
        me.deleteAction.setDisabled(!sm.getCount());
    },

    onRecordLoad: function(ed, record) {
        let me = this;
        let signatures = _.get(record, 'data.signatures', []);

        me.store.loadData(signatures);
    },

    onRecordUpdate: function(dlg, record) {
        let me = this;
        Tine.Tinebase.common.assertComparable(record.data.signatures);
        record.set('signatures', _.map(_.get(me, 'store.data.items', []), 'data'));
    },

    onEditSignature: function(action) {
        let me = this;
        let recordData = action !== 'add' ?
            me.selModel.getSelected().data : {
                id: Tine.Tinebase.data.Record.generateUID(),
                account_id: me.editDialog.record.get('id')
            };

        Tine.Felamimail.SignatureEditDialog.openWindow({
            mode: 'local',
            record: JSON.stringify(recordData),
            listeners: {
                scope: me,
                'update' : (record) => {
                    let signature = Tine.Tinebase.data.Record.setFromJson(record, me.recordClass);
                    let existing = me.store.getById(signature.id);
                    if (existing) {
                        me.store.remove(existing);
                    }
                    me.store.add(signature);
                    let hasDefault = _.reduce(me.store.data.items, (hd, r) => {return hd || r.get('is_default')}, false);
                    me.onCheckChange(null, signature.get('is_default') || !hasDefault, null, signature);
                }
            }
        })
    },

    onDeleteSignature: function() {
        let me = this;
        me.store.remove(me.selModel.getSelected());
    },

    onCheckChange: function(cc, newVal, oldVal, record) {
        let me = this;
        if (newVal) {
            me.store.each((r) => {
                r.set('is_default', false);
                r.commit();
            });
            record.set('is_default', true);
            record.commit();
        }
    }
});

/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

Tine.Filemanager.NotificationGridPanel = Ext.extend(Tine.widgets.account.PickerGridPanel, {
    app: null,

    selectType: 'both',
    selectAnyone: false,

    userCombo: null,
    groupCombo: null,

    currentUser: null,

    editDialog: null,

    initComponent: function () {
        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');
        this.currentUser = Tine.Tinebase.registry.get('currentAccount');
        this.initColumns();
        this.supr().initComponent.call(this);

        this.on('beforeedit', this.onBeforeEdit.createDelegate(this));
    },

    initColumns: function () {
        var me = this;
        this.configColumns = [
            new Ext.ux.grid.CheckColumn({
                id: 'active',
                header: this.app.i18n._('Notification'),
                tooltip: this.app.i18n._('Notification active'),
                dataIndex: 'active',
                width: 55,
                onBeforeCheck: function (checkbox, record) {
                    return this.checkGrant(record);
                }.createDelegate(me)
            }), {
                id: 'summary',
                dataIndex: 'summary',
                width: 100,
                sortable: true,
                header: this.app.i18n._('Summary'),
                renderer: function (value) {
                    if (value === 1) {
                        return value + ' ' + this.app.i18n._('Day');
                    }

                    if (value > 1) {
                        return value + ' ' + this.app.i18n._('Days');
                    }

                    return '';
                },
                editor: {
                    xtype: 'numberfield'
                }
            }
        ];
    },

    onBeforeEdit: function (e) {
        return this.checkGrant(e.record);
    },

    checkGrant: function (record) {
        var _ = window.lodash;

        var userHasAdminGrant = _.get(this.editDialog, 'record.data.account_grants.adminGrant', false);

        // get id if it's from notification props, if its a record which was added or if it's a group which was added
        var id = _.get(record, 'data.account_id', _.get(record, 'data.accountId')) || _.get(record, 'data.group_id');

        if (!userHasAdminGrant && id !== this.currentUser.accountId) {
            return false;
        }

        return true;
    },

    getColumnModel: function () {
        if (!this.colModel) {
            this.colModel = new Ext.grid.ColumnModel({
                defaults: {
                    sortable: true
                },
                columns: [
                    {
                        id: 'name',
                        header: this.app.i18n._('Name'),
                        dataIndex: this.recordPrefix + 'name',
                        renderer: this.accountRenderer.createDelegate(this)
                    }
                ].concat(this.configColumns)
            });
        }

        return this.colModel;
    },

    accountRenderer: function (value, meta, record) {
        if (!record) {
            return '';
        }

        var _ = window.lodash;

        var iconCls = _.get(record, 'data.accountType') === 'user' ? 'renderer renderer_accountUserIcon' : 'renderer renderer_accountGroupIcon';

        return '<div class="' + iconCls + '">&#160;</div>' + Ext.util.Format.htmlEncode(_.get(record, 'data.accountName') || '');
    },

    resetCombobox: function (combo) {
        combo.collapse();
        combo.clearValue();
        combo.reset();
    },

    onAddRecordFromCombo: function (recordToAdd, index, combo) {
        var _ = window.lodash;

        var id = _.get(recordToAdd, 'data.account_id') || _.get(recordToAdd, 'data.group_id');

        // If there is no admin grant, only allow to edit the own record
        if (!_.get(this.editDialog, 'record.data.account_grants.adminGrant', false) && id !== this.currentUser.accountId) {
            Ext.Msg.alert(i18n._('No permission'), 'You are only allowed to edit your own notifications.');

            this.resetCombobox(combo);
            return false;
        }

        var record = {
            'active': true,
            'summary': null,
            'accountId': id,
            'accountType': _.get(recordToAdd, 'data.type', null),
            'accountName': _.get(recordToAdd, 'data.n_fileas') || _.get(recordToAdd, 'data.name') || i18n._('all')
        };

        if (this.store.getById(id)) {
            this.resetCombobox(combo);
            return false;
        }

        this.store.loadData([record], true);

        this.resetCombobox(combo);

    },

    getContactSearchCombo: function () {
        if (! this.userCombo) {
            this.userCombo = new Tine.Addressbook.SearchCombo({
                hidden: true,
                accountsStore: this.store,
                emptyText: i18n._('Search for users ...'),
                newRecordClass: this.recordClass,
                newRecordDefaults: this.recordDefaults,
                recordPrefix: this.recordPrefix,
                userOnly: true,
                additionalFilters: (this.showHidden) ? [{field: 'showDisabled', operator: 'equals', value: true}] : []
            });

            this.userCombo.onSelect = this.onAddRecordFromCombo.createDelegate(this, [this.userCombo], true);
        }

        return this.userCombo;
    },

    onRemove: function () {
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            if (this.checkGrant(selectedRows[i])) {
                this.store.remove(selectedRows[i]);
            }
        }
    },

    getGroupSearchCombo: function () {
        if (! this.groupCombo) {
            this.groupCombo = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
                hidden: true,
                accountsStore: this.store,
                blurOnSelect: true,
                recordClass: this.groupRecordClass,
                newRecordClass: this.recordClass,
                newRecordDefaults: this.recordDefaults,
                recordPrefix: this.recordPrefix,
                emptyText: this.app.i18n._('Search for groups ...')
            });

            this.groupCombo.onSelect = this.onAddRecordFromCombo.createDelegate(this, [this.groupCombo], true);
        }

        return this.groupCombo;
    }
})
;
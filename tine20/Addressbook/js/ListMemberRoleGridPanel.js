/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Addressbook');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListMemberRoleGridPanel
 * @extends     Ext.grid.EditorGridPanel
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Addressbook.ListMemberRoleGridPanel = Ext.extend(Tine.widgets.grid.PickerGridPanel, {

    recordClass: Tine.Addressbook.Model.Contact,
    clicksToEdit: 1,
    enableHdMenu: false,
    autoExpandColumn: 'n_fileas',
    memberroles: null,

    // the list record
    record: null,

    /**
     * init component
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Addressbook');

        this.memberProperty = this.memberProperty ? this.memberProperty : 'members';
        this.roleProperty = this.roleProperty ? this.roleProperty : 'memberroles';

        this.memberDataPath = 'data.' + this.memberProperty;
        this.roleDataPath = 'data.' + this.roleProperty;
        
        this.title = this.hasOwnProperty('title') ? this.title : this.app.i18n._('Members');
        this.plugins = this.plugins || [];
        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));

        this.sm = new Ext.grid.RowSelectionModel({singleSelect:true});

        this.initColumns();

        this.store = new Ext.data.Store({
            autoSave: false,
            fields:  Tine.Addressbook.Model.Contact
        });

        this.addListener("beforeedit", this.onBeforeEdit, this);
        this.addListener("afteredit", this.onAfterEdit, this);

        // add specific search combo to be able to switch "userOnly" for system groups
        this.searchCombo = new Tine.Addressbook.SearchCombo({
            accountsStore: this.store,
            emptyText: this.app.i18n._('Search for members ...'),
            newRecordClass: this.recordClass,
            newRecordDefaults: this.recordDefaults,
            recordPrefix: this.recordPrefix,
            userOnly: false,
            blurOnSelect: true,
            listeners: {
                scope: this,
                select: this.onAddRecordFromCombo
            }
        });

        Tine.Addressbook.ListMemberRoleGridPanel.superclass.initComponent.call(this);
    },

    onRender: function() {
        this.supr().onRender.apply(this, arguments);

        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        // NOTE: in case we are rendered after record was load
        this.onRecordLoad(this.editDialog, this.editDialog.record);
    },

    /**
     * before cell edit
     *
     * @param o
     */
    onBeforeEdit: function(o) {
        var ed = this.colModel.getCellEditor(o.column, o.row);
        ed.record = o.record
    },

    /**
     * before cell edit
     *
     * @param o
     */
    onAfterEdit: function(o) {
        o.record.commit();
    },

    /**
     * init columns
     */
    initColumns: function() {
        this.columns = this.getColumns();
        this.editors = [];
        var visibleColumns = ["n_fileas", "email", "memberroles"];
        Ext.each(this.columns, function(value, idx) {
            if (visibleColumns.indexOf(this.columns[idx].id) === -1) {
                this.columns[idx].hidden = true;
            } else {
                this.columns[idx].width = 150;
            }
            if (this.columns[idx].id === "memberroles") {
                this.editors[idx] = new Tine.Addressbook.ListMemberRoleLayerCombo({
                    gridRecordClass: Tine.Addressbook.Model.ListRole
                });
                this.columns[idx].editor = this.editors[idx];
            }
        }, this);
    },

    /**
     * returns array with columns
     * 
     * @return {Array}
     */
    getColumns: function() {
        var _ = window.lodash,
            baseCols = Tine.Addressbook.ContactGridPanel.getBaseColumns(this.app.i18n);

        // NOTE: contact grid basecols have memberroles with different data layout
        baseCols = _.filter(baseCols, function(c) {return c.id !== 'memberroles'});
        baseCols = _.filter(baseCols, function(c) {return c.id !== 'email'});

        return baseCols.concat([
            { id: 'memberroles', header: this.app.i18n._('List Roles'), dataIndex: 'memberroles', renderer: this.listMemberRoleRenderer },
            { id: 'email', header: this.app.i18n._('Email'), dataIndex: 'email', renderer: this.preferredEmailRenderer }
        ]);
    },

    /**
     *list member role render
     *
     * @param value
     * @returns {string}
     * @constructor
     */
    listMemberRoleRenderer: function(value) {
        if (Ext.isArray(value)) {
            var result = [];
            Ext.each(value, function(memberrole) {
                if (memberrole.list_role_id.name) {
                    result.push(memberrole.list_role_id.name);
                }
            });
            return result.toString();
        }

        return '';
    },

    preferredEmailRenderer : function(value,metadata,record) {
        return record.getPreferredEmail();
    },

    onRecordLoad: function(editDialog, record) {
        var _ = window.lodash,
            memberData = _.get(record, this.memberDataPath) || [],
            rolesData = _.get(record, this.roleDataPath) || [];
        
        this.setStoreFromArray(memberData);
        this.setRolesFromData(rolesData);
    },

    onRecordUpdate: function(editDialog, record) {
        var _ = window.lodash,
            memberData = this.getFromStoreAsArray(),
            roleData = this.getRolesFromData(memberData);
        
        _.set(record, this.memberDataPath, memberData);
        _.set(record, this.roleDataPath, roleData);
    },

    /**
     * get values from store (as array)
     *
     * @param {Array}
     *
     */
    setStoreFromArray: function(data) {
        this.store.clearData();

        for (var i = data.length-1; i >=0; --i) {
            var recordData = {}
            recordData = data[i];
            this.store.insert(0, new this.recordClass(recordData));
        }
    },

    /**
     * get values from store (as array)
     *
     * @return {Array}
     */
    getFromStoreAsArray: function() {
        var result = Tine.Tinebase.common.assertComparable([]);
        this.store.each(function(record) {
            var data = record.data;
            result.push(data);
        }, this);

        return result;
    },

    /**
     * get memberrole records from store
     * 
     * @param data
     * @returns {mixed}
     */
    getRolesFromData: function(data) {
        var result = Tine.Tinebase.common.assertComparable([]);

        if (Ext.isArray(data)) {
            Ext.each(data, function(contact) {
                if (contact.memberroles) {
                    var roles = contact.memberroles;

                    Ext.each(roles, function(role) {
                        role.contact_id = contact.id;

                        result.push(role);
                    });
                }
            });
        }
        
        return result;
    },

    /**
     * get memberroles
     * 
     * @param roles
     * @param list
     */
    setRolesFromData: function(roles) {
        this.store.each(function(contact) {
            var contactRoles = [];
            Ext.each(roles, function(role) {
                if (role.contact_id.id == contact.get('id'))
                {
                    contactRoles.push(role);
                }
            }, this);
            if (contactRoles.length > 0) {
                contact.set('memberroles', contactRoles);
                contact.commit();
            }
        }, this);
    }
});

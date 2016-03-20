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
    memberRolesPanel: null,

    // the list record
    record: null,

    /**
     * init component
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Addressbook');

        this.title = this.hasOwnProperty('title') ? this.title : this.app.i18n._('Members');
        this.plugins = this.plugins || [];
        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));

        this.sm = new Ext.grid.RowSelectionModel({singleSelect:true});

        this.initColumns();
        this.store = new Ext.data.Store({
            autoSave: false,
            fields:  Tine.Addressbook.Model.Contact,
            proxy: Tine.Addressbook.contactBackend,
            reader: Tine.Addressbook.contactBackend.getReader(),
            listeners: {
                load: this.onLoad,
                scope: this
            }
        });

        this.addListener("beforeedit", this.onBeforeEdit, this);
        this.addListener("afteredit", this.onAfterEdit, this);

        Tine.Addressbook.ListMemberRoleGridPanel.superclass.initComponent.call(this);
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

        // update this.memberroles + memberRolesPanel
        this.memberRolesPanel.setListRolesOfContact(o.record);
    },

    /**
     * initialises grid with an array of member uids
     */
    setMembers: function() {
        var members = this.record.get("members"),
            memberroles = this.record.get("memberroles")

        if (members) {
            var options = {params: {filter: [ { "field":"id","operator":"in", "value": members } ]}};
            this.store.load(options);
            this.store.sort("n_fileas");
        }

        this.memberroles = memberroles;
    },

    onLoad: function(store) {
        if (this.memberroles) {
            this.store.each(function(contact) {
                var contactRoles = [];
                Tine.log.debug(contact);
                // TODO improve detection of matching contact (filter?)
                Ext.each(this.memberroles, function(memberrole) {
                    if (memberrole.contact_id.id == contact.get('id')
                        && memberrole.list_id == this.record.get('id')
                        && memberrole.list_role_id.id
                    )
                    {
                        contactRoles.push(memberrole);
                    }
                }, this);
                if (contactRoles.length > 0) {
                    contact.set('memberroles', contactRoles);
                    contact.commit();
                }
            }, this);

            this.memberRolesPanel.setListRoles(this.memberroles);
        }
    },

    /**
     * returns current array of member uids
     *
     * @return []
     */
    getMembers: function() {
        var result = [],
            roles = null;
        this.memberroles = [];

        this.store.each(function(contact) {
            if (contact.get('id')) {
                result.push(contact.get('id'));
                if (contact.get('memberroles').length > 0) {
                    roles = contact.get('memberroles');
                    Ext.each(roles, function (role) {
                        role.contact_id = contact.get('id');
                        role.list_id = this.record.get('id');
                        this.memberroles.push(role);
                    }, this);
                }
            }
        }, this);

        return result;
    },

    /**
     * returns current array of member roles
     *
     * @return []
     */
    getMemberRoles: function() {
        return this.memberroles;
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
                this.editors[idx] = new Tine.Addressbook.ListMemberRoleLayerCombo({});
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
        return Tine.Addressbook.ContactGridPanel.getBaseColumns(this.app.i18n);
    },

    // NOTE: Ext doc seems to be wrong on arguments here
    onContextMenu: function(e, target) {
        e.preventDefault();
        var row = this.getView().findRowIndex(target);
        var contact = this.store.getAt(row);
        if (contact) {
            // don't delete 'add' row
            if (! contact.get('id')) {
                return;
            }

            // select row
            this.getSelectionModel().selectRow(row);

            Tine.log.debug('onContextMenu - contact:');
            Tine.log.debug(contact);

            var items = [{
                text: this.app.i18n._('Remove Member'),
                iconCls: 'action_delete',
                scope: this,
                //disabled: ! this.record.get('editGrant'),
                handler: function() {
                    this.store.removeAt(row);
                }
            }, '-'];

            this.ctxMenu = new Ext.menu.Menu({
                items: items,
                listeners: {
                    scope: this,
                    hide: function() {
                        this.getSelectionModel().clearSelections();
                    }
                }
            });
            this.ctxMenu.showAt(e.getXY());
        }
    }
});

/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Addressbook');

/**
 * config grid panel
 *
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListMemberRoleLayerCombo
 * @extends     Tine.widgets.grid.LayerCombo
 */
Tine.Addressbook.ListMemberRoleLayerCombo = Ext.extend(Tine.widgets.grid.PickerGridLayerCombo, {

    /**
     * @cfg contact record
     */
    record: null,

    /**
     * @cfg grid record class
     */
    gridRecordClass: Tine.Addressbook.Model.ListRole,

    stripData: function(value) {
        var listRoles = [];
        if (Ext.isArray(value)) {
            Ext.each(value, function (role) {
                if (role.list_role_id) {
                    listRoles.push(role.list_role_id);
                } else {
                    listRoles.push(role);
                }
            });
        }

        return listRoles;
    },

    wrapData: function(listRoles) {
        var result = [];

        Ext.each(listRoles, function(role) {
            result.push({
                list_role_id: role,
                contact_id: this.record,
            })
        }, this);

        return result;
    },

    setValue: function(value) {
        this.supr().setValue.call(this, this.stripData(value));
    },

    /**
     * sets values to innerForm (grid)
     */
    setFormValue: function (value) {
        this.setStoreFromArray(this.stripData(value));
    },

    getValue: function() {
        var value = this.supr().getValue.call(this);
        return Tine.Tinebase.common.assertComparable(this.wrapData(value));
    },

    /**
     * retrieves values from grid
     *
     * @returns {*|Array}
     */
    getFormValue: function () {
        var listRoles = this.getFromStoreAsArray();

        return this.wrapData(listRoles);
    },
});

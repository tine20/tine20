/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

Ext.ns('Tine.Tinebase.widgets.form');

Tine.Tinebase.widgets.form.ApplicationPickerCombo = Ext.extend(Ext.form.ComboBox, {

    /**
     * @property {Tine.Tinebase.data.Record}
     */
    selectedRecord: null,

    // private
    displayField: 'name',
    valueField: 'id',
    mode: 'local',
    forceSelection: true,

    initComponent: function() {
        this.fieldLabel = window.i18n._('Application');

        const userApplications = _.map(Tine.Tinebase.appMgr.getAll().items, function(app) {
            return {id: app.id, name: app.i18n._(app.appName)};
        });

        this.store = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Admin.Model.Application
        });

        this.store.loadData({
            results: userApplications,
            totalcount: userApplications.length
        });

        Tine.Tinebase.widgets.form.ApplicationPickerCombo.superclass.initComponent.call(this);
    },

    /**
     * store a copy of the selected record
     *
     * @param {Tine.Tinebase.data.Record} record
     * @param {Number} index
     */
    onSelect: function (record, index) {
        this.selectedRecord = record;
        return Tine.Tinebase.widgets.form.ApplicationPickerCombo.superclass.onSelect.call(this, record, index);
    },
});

Ext.reg('tw-app-picker', Tine.Tinebase.widgets.form.ApplicationPickerCombo);
Tine.widgets.form.RecordPickerManager.register('Admin', 'Application', 'tw-app-picker');
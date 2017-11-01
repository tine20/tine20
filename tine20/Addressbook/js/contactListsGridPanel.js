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
 * @class       Tine.Addressbook.ListRoleGridPanel
 * @extends     Ext.grid.EditorGridPanel
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Addressbook.contactListsGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    recordClass: Tine.Addressbook.Model.List,
    usePagingToolbar: false,

    gridConfig: {
        autoExpandColumn: 'name'
    },

    // the list record
    record: null,

    // deactivate some fns
    initActions: Ext.emptyFn,
    initFilterPanel: Ext.emptyFn,

    /**
     * init component
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Addressbook');
        this.store = new Ext.data.JsonStore({
            fields: Tine.Addressbook.Model.List
        });

        this.gridConfig.cm = new Ext.grid.ColumnModel({
            defaults: {
                resizable: true
            },
            columns: this.getColumns()
        });

        Tine.Addressbook.contactListsGridPanel.superclass.initComponent.call(this);
    },

    getColumns: function() {
        return [
            {id: 'type', header: this.app.i18n._('Type'), dataIndex: 'type', width: 20, renderer: Tine.Addressbook.ListGridPanel.listTypeRenderer, hidden: false },
            {id: 'name', header: this.app.i18n._('Name'), dataIndex: 'name', width: 100, sortable: true}
        ];
    }
});

/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Addressbook');

/**
 * Contact grid panel
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ContactGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Contact Grid Panel</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Addressbook.ContactGridPanel
 */
Tine.Addressbook.ContactGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Addressbook.Model.Contact} recordClass
     */
    recordClass: Tine.Addressbook.Model.Contact,
    
    /**
     * grid specific
     * @private
     */ 
    defaultSortInfo: {field: 'n_fileas', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'n_fileas',
        enableDragDrop: true,
        ddGroup: 'containerDDGroup'
    },
    copyEditAction: true,
    felamimail: false,
    multipleEdit: true,
    
    /**
     * @cfg {Bool} hasDetailsPanel 
     */
    hasDetailsPanel: true,
    
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Addressbook.contactBackend;
        
        // check if felamimail is installed and user has run right and wants to use felamimail in adb
        if (Tine.Felamimail && Tine.Tinebase.common.hasRight('run', 'Felamimail') && Tine.Felamimail.registry.get('preferences').get('useInAdb')) {
            this.felamimail = (Tine.Felamimail.registry.get('preferences').get('useInAdb') == 1);
        }
        this.gridConfig.cm = this.getColumnModel();
        this.filterToolbar = this.filterToolbar || this.getFilterToolbar();

        if (this.hasDetailsPanel) {
            this.detailsPanel = this.getDetailsPanel();
        }
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Addressbook.ContactGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({ 
            defaults: {
                sortable: true,
                hidden: true,
                resizable: true
            },
            columns: this.getColumns()
        });
    },
    
    /**
     * returns array with columns
     * 
     * @return {Array}
     */
    getColumns: function() {
        return [
            { id: 'tid', header: this.app.i18n._('Type'), dataIndex: 'tid', width: 30, renderer: this.contactTidRenderer.createDelegate(this), hidden: false },
            { id: 'tags', header: this.app.i18n._('Tags'), dataIndex: 'tags', width: 50, renderer: Tine.Tinebase.common.tagsRenderer, sortable: false, hidden: false  },
            { id: 'salutation', header: this.app.i18n._('Salutation'), dataIndex: 'salutation', renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Addressbook', 'contactSalutation') },
            { id: 'n_family', header: this.app.i18n._('Last Name'), dataIndex: 'n_family' },
            { id: 'n_given', header: this.app.i18n._('First Name'), dataIndex: 'n_given', width: 80 },
            { id: 'n_fn', header: this.app.i18n._('Full Name'), dataIndex: 'n_fn' },
            { id: 'n_fileas', header: this.app.i18n._('Display Name'), dataIndex: 'n_fileas', hidden: false},
            { id: 'org_name', header: this.app.i18n._('Company'), dataIndex: 'org_name', width: 120, hidden: false },
            { id: 'org_unit', header: this.app.i18n._('Unit'), dataIndex: 'org_unit'  },
            { id: 'title', header: this.app.i18n._('Job Title'), dataIndex: 'title' },
            { id: 'role', header: this.app.i18n._('Job Role'), dataIndex: 'role' },
            { id: 'room', header: this.app.i18n._('Room'), dataIndex: 'room' },
            { id: 'adr_one_street', header: this.app.i18n._('Street'), dataIndex: 'adr_one_street' },
            { id: 'adr_one_locality', header: this.app.i18n._('City'), dataIndex: 'adr_one_locality', width: 150, hidden: false },
            { id: 'adr_one_region', header: this.app.i18n._('Region'), dataIndex: 'adr_one_region' },
            { id: 'adr_one_postalcode', header: this.app.i18n._('Postalcode'), dataIndex: 'adr_one_postalcode' },
            { id: 'adr_one_countryname', header: this.app.i18n._('Country'), dataIndex: 'adr_one_countryname' },
            { id: 'adr_two_street', header: this.app.i18n._('Street (private)'), dataIndex: 'adr_two_street' },
            { id: 'adr_two_locality', header: this.app.i18n._('City (private)'), dataIndex: 'adr_two_locality' },
            { id: 'adr_two_region', header: this.app.i18n._('Region (private)'), dataIndex: 'adr_two_region' },
            { id: 'adr_two_postalcode', header: this.app.i18n._('Postalcode (private)'), dataIndex: 'adr_two_postalcode' },
            { id: 'adr_two_countryname', header: this.app.i18n._('Country (private)'), dataIndex: 'adr_two_countryname' },
            { id: 'email', header: this.app.i18n._('Email'), dataIndex: 'email', width: 150, hidden: false },
            { id: 'tel_work', header: this.app.i18n._('Phone'), dataIndex: 'tel_work', hidden: false },
            { id: 'tel_cell', header: this.app.i18n._('Mobile'), dataIndex: 'tel_cell', hidden: false },
            { id: 'tel_fax', header: this.app.i18n._('Fax'), dataIndex: 'tel_fax' },
            { id: 'tel_car', header: this.app.i18n._('Car phone'), dataIndex: 'tel_car' },
            { id: 'tel_pager', header: this.app.i18n._('Pager'), dataIndex: 'tel_pager' },
            { id: 'tel_home', header: this.app.i18n._('Phone (private)'), dataIndex: 'tel_home' },
            { id: 'tel_fax_home', header: this.app.i18n._('Fax (private)'), dataIndex: 'tel_fax_home' },
            { id: 'tel_cell_private', header: this.app.i18n._('Mobile (private)'), dataIndex: 'tel_cell_private' },
            { id: 'email_home', header: this.app.i18n._('Email (private)'), dataIndex: 'email_home' },
            { id: 'url', header: this.app.i18n._('Web'), dataIndex: 'url' },
            { id: 'url_home', header: this.app.i18n._('URL (private)'), dataIndex: 'url_home' },
            { id: 'note', header: this.app.i18n._('Note'), dataIndex: 'note' },
            { id: 'tz', header: this.app.i18n._('Timezone'), dataIndex: 'tz' },
            { id: 'geo', header: this.app.i18n._('Geo'), dataIndex: 'geo' },
            { id: 'bday', header: this.app.i18n._('Birthday'), dataIndex: 'bday', renderer: Tine.Tinebase.common.dateRenderer }
        ].concat(this.getModlogColumns().concat(this.getCustomfieldColumns()));
    },
    
    /**
     * @private
     */
    initActions: function() {
        this.actions_exportContact = new Ext.Action({
            requiredGrant: 'exportGrant',
            text: this.app.i18n._('Export Contact'),
            iconCls: 'action_export',
            scope: this,
            disabled: true,
            allowMultiple: true,
            menu: {
                items: [
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as PDF'),
                        iconCls: 'action_exportAsPdf',
                        format: 'pdf',
                        exportFunction: 'Addressbook.exportContacts',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as CSV'),
                        iconCls: 'tinebase-action-export-csv',
                        format: 'csv',
                        exportFunction: 'Addressbook.exportContacts',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as ODS'),
                        format: 'ods',
                        iconCls: 'tinebase-action-export-ods',
                        exportFunction: 'Addressbook.exportContacts',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as XLS'),
                        format: 'xls',
                        iconCls: 'tinebase-action-export-xls',
                        exportFunction: 'Addressbook.exportContacts',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as ...'),
                        iconCls: 'tinebase-action-export-xls',
                        exportFunction: 'Addressbook.exportContacts',
                        showExportDialog: true,
                        gridPanel: this
                    })
                ]
            }
        });

        this.actions_import = new Ext.Action({
            //requiredGrant: 'addGrant',
            text: this.app.i18n._('Import contacts'),
            disabled: false,
            handler: this.onImport,
            iconCls: 'action_import',
            scope: this,
            allowMultiple: true
        });
        
        // register actions in updater
        this.actionUpdater.addActions([
            this.actions_exportContact,
            this.actions_import
        ]);
        
        Tine.Addressbook.ContactGridPanel.superclass.initActions.call(this);
    },
    
    /**
     * add custom items to action toolbar
     * 
     * @return {Object}
     */
    getActionToolbarItems: function() {
        return [
            {
                xtype: 'buttongroup',
                columns: 1,
                frame: false,
                items: [
                    this.actions_exportContact,
                    this.actions_import
                ]
            }
        ];
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.actions_exportContact,
            '-'
        ];
        
        return items;
    },
    
    /**
     * import contacts
     * 
     * @param {Button} btn 
     * 
     * TODO generalize this & the import button
     */
    onImport: function(btn) {
        var popupWindow = Tine.widgets.dialog.ImportDialog.openWindow({
            appName: 'Addressbook',
            modelName: 'Contact',
            defaultImportContainer: this.app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer('defaultAddressbook'),
            
            // update grid after import
            listeners: {
                scope: this,
                'finish': function() {
                    this.loadGridData({
                        preserveCursor:     false, 
                        preserveSelection:  false, 
                        preserveScroller:   false,
                        removeStrategy:     'default'
                    });
                }
            }
        });
    },
        
    /**
     * tid renderer
     * 
     * @private
     * @return {String} HTML
     */
    contactTidRenderer: function(data, cell, record) {
    	
        switch(record.get('type')) {
            case 'user':
                return "<img src='images/oxygen/16x16/actions/user-female.png' width='12' height='12' alt='contact' ext:qtip='" + this.app.i18n._("Internal Contact") + "'/>";
            default:
                return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";
        }
    },
    
    /**
     * returns details panel
     * 
     * @private
     * @return {Tine.Addressbook.ContactGridDetailsPanel}
     */
    getDetailsPanel: function() {
        return new Tine.Addressbook.ContactGridDetailsPanel({
            gridpanel: this,
            il8n: this.app.i18n,
            felamimail: this.felamimail
        });
    }
});

/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
    duplicateResolvable: true,
    
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

        if (this.hasDetailsPanel) {
            this.detailsPanel = this.getDetailsPanel();
        }

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
        return Tine.Addressbook.ContactGridPanel.getBaseColumns(this.app.i18n)
            .concat(this.getModlogColumns().concat(this.getCustomfieldColumns()));
    },
    
    /**
     * @private
     */
    initActions: function() {
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
            this.actions_import
        ]);
        
        Tine.Addressbook.ContactGridPanel.superclass.initActions.call(this);
    },

    /**
     * get default / selected addressbook container
     *
     * @returns {Object|Tine.Tinebase.Model.Container}
     */
    getDefaultContainer: function() {
        return this.app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer('defaultAddressbook');
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

// Static Methods

/**
 * tid renderer
 * 
 * @private
 * @return {String} HTML
 */
Tine.Addressbook.ContactGridPanel.contactTypeRenderer = function(data, cell, record) {
    var i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n,
        hasAccount = ((record.get && record.get('account_id')) || record.account_id),
        cssClass = 'tine-grid-row-action-icon ' + (hasAccount ? 'renderer_typeAccountIcon' : 'renderer_typeContactIcon'),
        qtipText = Tine.Tinebase.common.doubleEncode(hasAccount ? i18n._('Contact of a user account') : i18n._('Contact'));
    
    return '<div ext:qtip="' + qtipText + '" style="background-position:0px;" class="' + cssClass + '">&#160</div>';
};

Tine.Addressbook.ContactGridPanel.displayNameRenderer = function(data) {
    var i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n;
    return data ?  Tine.Tinebase.EncodingHelper.encode(data) : ('<div class="renderer_displayNameRenderer_noName">' + i18n._('No name') + '</div>');
};

Tine.Addressbook.ContactGridPanel.countryRenderer = function(data) {
    data = Locale.getTranslationData('CountryList', data);
    return Ext.util.Format.htmlEncode(data);
};

/**
 * Column renderer adb preferred_address field
 * @param value
 * @return {*}
 */
Tine.Addressbook.ContactGridPanel.preferredAddressRenderer = function(value) {
    var i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n;

    switch (value) {
        case '0':
            return i18n._('Business');
        case '1':
            return i18n._('Private');
        default:
            return i18n._('Not set');
    }
};

/**
 * Statically constructs the columns used to represent a contact. Reused by ListMemberGridPanel + ListMemberRoleGridPanel
 */
Tine.Addressbook.ContactGridPanel.getBaseColumns = function(i18n) {
    var columns = [
        { id: 'type', header: i18n._('Type'), tooltip: i18n._('Type'), dataIndex: 'type', width: 20, renderer: Tine.Addressbook.ContactGridPanel.contactTypeRenderer.createDelegate(this), hidden: false },
        { id: 'jpegphoto', header: i18n._('Contact Image'), tooltip: i18n._('Contact Image'), dataIndex: 'jpegphoto', width: 20, sortable: false, resizable: false, renderer: Tine.widgets.grid.imageRenderer, hidden: false },
        { id: 'attachments', header: window.i18n._('Attachments'), tooltip: window.i18n._('Attachments'), dataIndex: 'attachments', width: 20, sortable: false, resizable: false, renderer: Tine.widgets.grid.attachmentRenderer, hidden: false },
        { id: 'tags', header: i18n._('Tags'), dataIndex: 'tags', width: 50, renderer: Tine.Tinebase.common.tagsRenderer, sortable: false, hidden: false  },
        { id: 'salutation', header: i18n._('Salutation'), dataIndex: 'salutation', renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Addressbook', 'contactSalutation') },
        {
            id: 'container_id',
            dataIndex: 'container_id',
            header: Tine.Addressbook.Model.Contact.getContainerName(),
            width: 150,
            renderer: Tine.Tinebase.common.containerRenderer
        },
        { id: 'n_prefix', header: i18n._('Title'), dataIndex: 'n_prefix', width: 80 },
        { id: 'n_middle', header: i18n._('Middle Name'), dataIndex: 'n_middle', width: 80 },
        { id: 'n_family', header: i18n._('Last Name'), dataIndex: 'n_family' },
        { id: 'n_given', header: i18n._('First Name'), dataIndex: 'n_given', width: 80 },
        { id: 'n_fn', header: i18n._('Full Name'), dataIndex: 'n_fn', renderer: Tine.Addressbook.ContactGridPanel.displayNameRenderer },
        { id: 'n_fileas', header: i18n._('Display Name'), dataIndex: 'n_fileas', hidden: false, renderer: Tine.Addressbook.ContactGridPanel.displayNameRenderer},
        { id: 'org_name', header: i18n._('Company'), dataIndex: 'org_name', width: 120, hidden: false },
        { id: 'org_unit', header: i18n._('Unit'), dataIndex: 'org_unit'  },
        { id: 'title', header: i18n._('Job Title'), dataIndex: 'title' },
//            { id: 'role', header: i18n._('Job Role'), dataIndex: 'role' },
//            { id: 'room', header: i18n._('Room'), dataIndex: 'room' },
        { id: 'adr_one_street', header: i18n._('Street'), dataIndex: 'adr_one_street' },
        { id: 'adr_one_locality', header: i18n._('City'), dataIndex: 'adr_one_locality', width: 150, hidden: false },
        { id: 'adr_one_region', header: i18n._('Region'), dataIndex: 'adr_one_region' },
        { id: 'adr_one_postalcode', header: i18n._('Postalcode'), dataIndex: 'adr_one_postalcode' },
        { id: 'adr_one_countryname', header: i18n._('Country'), dataIndex: 'adr_one_countryname', renderer: Tine.Addressbook.ContactGridPanel.countryRenderer },
        { id: 'adr_two_street', header: i18n._('Street (private)'), dataIndex: 'adr_two_street' },
        { id: 'adr_two_locality', header: i18n._('City (private)'), dataIndex: 'adr_two_locality' },
        { id: 'adr_two_region', header: i18n._('Region (private)'), dataIndex: 'adr_two_region' },
        { id: 'adr_two_postalcode', header: i18n._('Postalcode (private)'), dataIndex: 'adr_two_postalcode' },
        { id: 'adr_two_countryname', header: i18n._('Country (private)'), dataIndex: 'adr_two_countryname', renderer: Tine.Addressbook.ContactGridPanel.countryRenderer },
        { id: 'preferred_address', header: i18n._('Preferred Address'), dataIndex: 'preferred_address', renderer: Tine.Addressbook.ContactGridPanel.preferredAddressRenderer },
        { id: 'email', header: i18n._('Email'), dataIndex: 'email', width: 150, hidden: false },
        { id: 'tel_work', header: i18n._('Phone'), dataIndex: 'tel_work', hidden: false },
        { id: 'tel_cell', header: i18n._('Mobile'), dataIndex: 'tel_cell', hidden: false },
        { id: 'tel_fax', header: i18n._('Fax'), dataIndex: 'tel_fax' },
        { id: 'tel_car', header: i18n._('Car phone'), dataIndex: 'tel_car' },
        { id: 'tel_pager', header: i18n._('Pager'), dataIndex: 'tel_pager' },
        { id: 'tel_home', header: i18n._('Phone (private)'), dataIndex: 'tel_home' },
        { id: 'tel_fax_home', header: i18n._('Fax (private)'), dataIndex: 'tel_fax_home' },
        { id: 'tel_cell_private', header: i18n._('Mobile (private)'), dataIndex: 'tel_cell_private' },
        { id: 'email_home', header: i18n._('Email (private)'), dataIndex: 'email_home' },
        { id: 'url', header: i18n._('Web'), dataIndex: 'url' },
        { id: 'url_home', header: i18n._('URL (private)'), dataIndex: 'url_home' },
        { id: 'note', header: i18n._('Note'), dataIndex: 'note' },
        { id: 'tz', header: i18n._('Timezone'), dataIndex: 'tz' },
        { id: 'geo', header: i18n._('Geo'), dataIndex: 'geo' },
        { id: 'bday', header: i18n._('Birthday'), dataIndex: 'bday', renderer: Tine.Tinebase.common.dateRenderer },
        { id: 'memberroles', header: i18n._('List Roles'), dataIndex: 'memberroles', sortable: false, renderer: Tine.Addressbook.ListMemberRoleRenderer }
    ];

    if (Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureIndustry')) {
        columns.push({ id: 'industry', header: i18n._('Industry'), dataIndex: 'industry', renderer: Tine.Tinebase.common.foreignRecordRenderer});
    }
    if (Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureShortName')) {
        columns.push({ id: 'n_short', header: i18n._('Short Name'), dataIndex: 'n_short', width: 50 });
    }
    
    return columns;
};

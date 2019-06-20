/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Addressbook');

/**
 * List grid panel
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>List Grid Panel</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Addressbook.ListGridPanel
 */
Tine.Addressbook.ListGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Addressbook.Model.List} recordClass
     */
    recordClass: Tine.Addressbook.Model.List,
    
    /**
     * grid specific
     * @private
     */ 
    defaultSortInfo: {field: 'name', direction: 'ASC'},
    copyEditAction: true,
    felamimail: false,
    multipleEdit: false,
    duplicateResolvable: false,
    
    /**
     * @cfg {Bool} hasDetailsPanel 
     */
    hasDetailsPanel: false,
    
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Addressbook.listBackend;
        
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
        
        Tine.Addressbook.ListGridPanel.superclass.initComponent.call(this);
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
            { id: 'type', header: this.app.i18n._('Type'), dataIndex: 'type', width: 30, renderer: Tine.Addressbook.ListGridPanel.listTypeRenderer, hidden: false },
            { id: 'tags', header: this.app.i18n._('Tags'), dataIndex: 'tags', width: 50, renderer: Tine.Tinebase.common.tagsRenderer, sortable: false, hidden: false },
            { id: 'name', header: this.app.i18n._('Name'), dataIndex: 'name', width: 30, hidden: false },
            { id: 'email', header: this.app.i18n._('E-Mail'), dataIndex: 'email', width: 40, hidden: false },
            { id: 'list_type', header: this.app.i18n._('List type'), dataIndex: 'list_type', width: 30, renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Addressbook', 'listType'), hidden: false },
            // TODO do we still need it? discuss!
            { id: 'emails', header: this.app.i18n._('Member E-Mails'), dataIndex: 'emails', hidden: false, renderer: function(value) {
                if (! value) {
                    return '';
                }
                // TODO should be fixed on server side
                // remove leading comma
                return value.replace(/^,/, '');
            }},
        ].concat(this.getModlogColumns().concat(this.getCustomfieldColumns()));
    },
    
    /**
     * @private
     */
    initActions: function() {        
        Tine.Addressbook.ListGridPanel.superclass.initActions.call(this);
    },

    /**
     * returns details panel
     * 
     * @private
     * @return {Tine.Addressbook.ListGridDetailsPanel}
     */
    getDetailsPanel: function() {
        return new Tine.Addressbook.ListGridDetailsPanel({
            gridpanel: this,
            il8n: this.app.i18n
        });
    }
});

/**
 * list type renderer
 *
 * @private
 * @return {String} HTML
 */
Tine.Addressbook.ListGridPanel.listTypeRenderer = function(data, cell, record) {
    var i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n,
        type = ((record.get && record.get('type')) || record.type),
        cssClass = 'tine-grid-row-action-icon ' + (type == 'group' ? 'renderer_typeGroupIcon' : 'renderer_typeListIcon'),
        qtipText = Tine.Tinebase.common.doubleEncode(type == 'group' ? i18n._('System Group') : i18n._('Group'));

    return '<div ext:qtip="' + qtipText + '" style="background-position:0px;" class="' + cssClass + '">&#160</div>';
};

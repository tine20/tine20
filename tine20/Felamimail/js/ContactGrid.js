/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Felamimail');

/**
 * Contact grid panel
 * 
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.ContactGridPanel
 * @extends     Tine.Addressbook.ContactGridPanel
 * 
 * <p>Contact Grid Panel</p>
 * 
 * TODO         make ctx menu actions work
 * TODO         add recipient on doubleclick
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.ContactGridPanel
 */
Tine.Felamimail.ContactGridPanel = Ext.extend(Tine.Addressbook.ContactGridPanel, {

    hasDetailsPanel: false,
    hasFavoritesPanel: false,
    hasQuickSearchFilterToolbarPlugin: false,
    stateId: 'FelamimailContactGrid',
    
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'n_fileas',
        enableDragDrop: true,
        ddGroup: 'recipientDDGroup'
    },
    
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Addressbook');
        this.filterToolbar = this.getFilterToolbar({
            filterFieldWidth: 100,
            filterValueWidth: 100
        });
        
        Tine.Felamimail.ContactGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns array with columns
     * 
     * @return {Array}
     */
    getColumns: function() {
        var columns = Tine.Felamimail.ContactGridPanel.superclass.getColumns.call(this);
        
        // hide all columns except name/company/email/email_home (?)
        Ext.each(columns, function(column) {
            if (['n_fileas', 'org_name', 'email'].indexOf(column.dataIndex) === -1) {
                column.hidden = true;
            }
        });
        
        return columns;
    },
    
    /**
     * Return CSS class to apply to rows depending upon email set or not
     * 
     * @param {Tine.Addressbook.Model.Contact} record
     * @param {Integer} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var className = '';
        
        if ((! record.get('email') || record.get('email') == '') && (! record.get('email_home') || record.get('email_home') == '')) {
            className = 'felamimail-no-email';
        }
        
        return className;
    },
    
    /**
     * @private
     */
    initActions: function() {
        this.actions_addAsTo = new Ext.Action({
            requiredGrant: 'readGrant',
            //actionUpdater: this.updatePhoneActions,
            text: this.app.i18n._('Add as "To"'),
            disabled: true,
            iconCls: 'action_add',
            handler: this.onAdd,
            scope: this
        });
        
//        //register actions in updater
//        this.actionUpdater.addActions([
//            this.actions_exportContact,
//            this.actions_callContact,
//            this.actions_composeEmail,
//            this.actions_import
//        ]);
//        
//        Tine.Addressbook.ContactGridPanel.superclass.initActions.call(this);
    },
    
    onAdd: function() {
        console.log();
    },

    /**
     * returns rows context menu
     * 
     * @return {Ext.menu.Menu}
     */
    getContextMenu: function() {
        if (! this.contextMenu) {
            var items = [
                // TODO add more actions
                this.actions_addAsTo
            ];
            this.contextMenu = new Ext.menu.Menu({items: items});
        }
        return this.contextMenu;
    }
});

Ext.reg('felamimailcontactgrid', Tine.Felamimail.ContactGridPanel);

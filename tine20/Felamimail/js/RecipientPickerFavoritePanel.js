/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.RecipientPickerFavoritePanel
 * @extends     Ext.tree.TreePanel
 * 
 * <p>PersistentFilter Picker Panel</p>
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.RecipientPickerFavoritePanel
 */
Tine.Felamimail.RecipientPickerFavoritePanel = Ext.extend(Tine.widgets.persistentfilter.PickerPanel, {
    
    collapsible: true,
    baseCls: 'ux-arrowcollapse',
    animCollapse: true,
    titleCollapse:true,
    draggable : true,
    autoScroll: false,
    
    /**
     * no context menu
     * @type Function
     */
    onContextMenu: Ext.emptyFn,
    
    /**
     * @private
     */
    initComponent: function() {
        this.title = this.app.i18n._('Recipient filter');
        
        this.store = new Ext.data.ArrayStore({
            fields: Tine.widgets.persistentfilter.model.PersistentFilter.getFieldDefinitions(),
            sortInfo: {field: 'name', direction: 'ASC'}
        });
        
        var label = '';
        Ext.each(['all', 'to', 'cc', 'bcc'], function(field) {
            switch (field) {
                case 'all':
                    label = this.app.i18n._('All recipients');
                    break;
                default:
                    label = String.format(this.app.i18n._('"{0}" recipients'), field);
                    break;
            }
            this.store.add([new Tine.widgets.persistentfilter.model.PersistentFilter({
                filters: field,
                name: label,
                model: 'Addressbook_Model_Contact',
                application_id: this.app.id,
                id: Ext.id()
            })]);
        }, this);

        
        this.filterNode = new Ext.tree.AsyncTreeNode({
            id: '_recipientFilter',
            leaf: false,
            expanded: true
        });
        
        Tine.Felamimail.RecipientPickerFavoritePanel.superclass.initComponent.call(this);
    },
    
    /**
     * load grid from saved filter
     * 
     * -> overwritten to allow to dynamically update email filter
     * 
     *  @param {Tine.widgets.persistentfilter.model.PersistentFilter} persistentFilter
     */
    onFilterSelect: function(persistentFilter) {
        var emailRecipients = [];

        switch (persistentFilter.get('filters')) {
            case 'all':
                Ext.each(['to', 'cc', 'bcc'], function(field) {
                    emailRecipients = emailRecipients.concat(this.grid.messageRecord.data[field]);
                }, this);
                break;
            default:
                emailRecipients = this.grid.messageRecord.data[persistentFilter.get('filters')];
                break;
        }
        
        var filterValue = [], emailRegExp = /<([^>]*)/, filter = [{field: 'container_id', operator: 'in', value: []}];
        Ext.each(emailRecipients, function(email) {
            emailRegExp.exec(email);
            if (RegExp.$1 != '') {
                filterValue.push(RegExp.$1)
            }
        }, this);
        if (filterValue.length > 0) {
            filter = [{field: 'email_query', operator: 'in', value: filterValue}];
        }

        var updatedPersistentFilter = new Tine.widgets.persistentfilter.model.PersistentFilter({
            filters: filter,
            name: persistentFilter.get('name'),
            model: 'Addressbook_Model_Contact',
            application_id: this.app.id,
            id: Ext.id()
        });

        Tine.Felamimail.RecipientPickerFavoritePanel.superclass.onFilterSelect.call(this, updatedPersistentFilter);
    }
});

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
    /**
     * @private
     */
    initComponent: function() {
        this.store = Tine.widgets.persistentfilter.store.getPersistentFilterStore();
        this.filterNode = new Ext.tree.AsyncTreeNode({
            text: this.app.i18n._('Recipients'),
            id: '_recipientFilters',
            leaf: false,
            expanded: true
        });
        
        Tine.Felamimail.RecipientPickerFavoritePanel.superclass.initComponent.call(this);
    }
});

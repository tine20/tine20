/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Expressomail');

/**
 * folder select trigger field
 * 
 * @namespace   Tine.widgets.container
 * @class       Tine.Expressomail.FolderSelectTriggerField
 * @extends     Ext.form.ComboBox
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */
Tine.Expressomail.FolderSelectTriggerField = Ext.extend(Ext.form.TriggerField, {
    
    triggerClass: 'x-form-search-trigger',
    account: null,
    allAccounts: false,
    
    /**
     * onTriggerClick
     * open ext window with (folder-)select panel that fires event on select
     * 
     * @param e
     */
    onTriggerClick: function(e) {
        if (! this.disabled && (this.account && this.account.id !== 0) || this.allAccounts) {
            this.selectPanel = Tine.Expressomail.FolderSelectPanel.openWindow({
                account: this.account,
                allAccounts: this.allAccounts,
                listeners: {
                    // NOTE: scope has to be first item in listeners! @see Ext.ux.WindowFactory
                    scope: this,
                    'folderselect': this.onSelectFolder
                }
            });
        }
    },
    
    /**
     * select folder event listener
     * 
     * @param {Ext.tree.AsyncTreeNode} node
     */
    onSelectFolder: function(node) {
        this.selectPanel.close();
        this.setValue(node.attributes.globalname);
        this.el.focus();
    }
});
Ext.reg('expressomailfolderselect', Tine.Expressomail.FolderSelectTriggerField);

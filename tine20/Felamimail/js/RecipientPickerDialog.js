/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.RecipientPickerDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Message Compose Dialog</p>
 * <p>This dialog is for searching contacts in the addressbook and adding them to the recipient list in the email compose dialog.</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RecipientPickerDialog
 */
 Tine.Felamimail.RecipientPickerDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'RecipientPickerWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Message,
    recordProxy: Tine.Felamimail.messageBackend,
    loadRecord: false,
    evalGrants: false,
    mode: 'local',
    hideAttachmentsPanel: true,
    
    bodyStyle:'padding:0px',
    
    /**
     * overwrite update toolbars function (we don't have record grants)
     * @private
     */
    updateToolbars: Ext.emptyFn,
    
    /**
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        var subject = (this.record.get('subject') != '') ? this.record.get('subject') : this.app.i18n._('(new message)');
        this.window.setTitle(String.format(this.app.i18n._('Select recipients for "{0}"'), subject));
        
        this.hideLoadMask();
    },

    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initialisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        var adbApp = Tine.Tinebase.appMgr.get('Addressbook');
        
        this.treePanel = new Tine.widgets.container.TreePanel({
            allowMultiSelection: true,
            region: 'west',
            filterMode: 'filterToolbar',
            recordClass: Tine.Addressbook.Model.Contact,
            app: adbApp,
            width: 200,
            minSize: 100,
            maxSize: 300,
            border: false,
            enableDrop: false
        });
        
        this.contactGrid = new Tine.Felamimail.ContactGridPanel({
            region: 'center',
            messageRecord: this.record,
            app: adbApp,
            plugins: [this.treePanel.getFilterPlugin()]
        });
        
        this.westPanel = new Tine.widgets.mainscreen.WestPanel({
            app: adbApp,
            hasFavoritesPanel: true,
            ContactTreePanel: this.treePanel,
            ContactFilterPanel: new Tine.widgets.persistentfilter.PickerPanel({
                filter: [{field: 'model', operator: 'equals', value: 'Addressbook_Model_ContactFilter'}],
                app: adbApp,
                grid: this.contactGrid
            }),
            additionalItems: [ new Tine.Felamimail.RecipientPickerFavoritePanel({
                app: this.app,
                grid: this.contactGrid
            })]
        });
        
        return {
            border: false,
            layout: 'border',
            items: [{
                cls: 'tine-mainscreen-centerpanel-west',
                region: 'west',
                stateful: false,
                layout: 'border',
                split: true,
                width: 200,
                minSize: 100,
                maxSize: 300,
                border: false,
                collapsible: true,
                collapseMode: 'mini',
                header: false,
                items: [{
                    border: false,
                    region: 'center',
                    items: [ this.westPanel ]
                }]
            }, this.contactGrid]
        };
    }
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.RecipientPickerDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 1000,
        height: 600,
        name: Tine.Felamimail.RecipientPickerDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.RecipientPickerDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

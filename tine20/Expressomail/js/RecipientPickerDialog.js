/*
 * Tine 2.0
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.RecipientPickerDialog
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
 Tine.Expressomail.RecipientPickerDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'RecipientPickerWindow_',
    appName: 'Expressomail',
    recordClass: Tine.Expressomail.Model.Message,
    recordProxy: Tine.Expressomail.messageBackend,
    loadRecord: false,
    evalGrants: false,
    mode: 'local',
    
    bodyStyle:'padding:0px',
    
    /**
     * overwrite update toolbars function (we don't have record grants)
     * @private
     */
    updateToolbars: Ext.emptyFn,
    
    
    query: null,
    
    /**
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        Ext.each(['to', 'cc', 'bcc'], function(type) {
                if (this.record.data[type].indexOf(this.query) !== -1) {
                    this.record.data[type].remove(this.query);
                }
        }, this);
        
        var subject = (this.record.get('subject') != '') ? this.record.get('subject') : this.app.i18n._('(new message)');
        this.window.setTitle(String.format(this.app.i18n._('Select recipients for "{0}"'), subject));
        
        this.loadMask.hide();
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
        
        this.contactGrid = new Tine.Expressomail.ContactGridPanel({
            region: 'center',
            messageRecord: this.record,
            app: adbApp,
            query: this.query,
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
            additionalItems: [ new Tine.Expressomail.RecipientPickerFavoritePanel({
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
 * Expressomail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Expressomail.RecipientPickerDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 1000,
        height: 600,
        name: Tine.Expressomail.RecipientPickerDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Expressomail.RecipientPickerDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

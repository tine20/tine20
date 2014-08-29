/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * TODO         maybe we don't need this
 */
Ext.ns('Tine.Filemanager');

/**
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.DownloadLinkDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Sieve Filter Dialog</p>
 * <p>This dialog is for editing sieve filters (rules).</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RulesDialog
 */
Tine.Filemanager.DownloadLinkDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @cfg {Tine.Felamimail.Model.Account}
     * 
     * TODO use node record?
     */
    node: null,

    /**
     * @private
     */
    windowNamePrefix: 'DownloadLinkWindow_',
    appName: 'Filemanager',
//    loadRecord: false,
    mode: 'local',
    tbarItems: [],
    evalGrants: false,
    
    //private
    initComponent: function(){
        Tine.Filemanager.DownloadLinkDialog.superclass.initComponent.call(this);
        
        this.i18nRecordName = this.app.i18n._('Node Download Links');
    },
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * 
     * @private
     */
    updateToolbars: Ext.emptyFn,
    
    /**
     * init record to edit
     * -> we don't have a real record here
     */
    initRecord: function() {
//        this.onRecordLoad();
    },
    
    /**
     * executed after record got updated from proxy
     * -> we don't have a real record here
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
//        if (! this.rendered) {
//            this.onRecordLoad.defer(250, this);
//            return;
//        }
//        
//        var title = String.format(this.app.i18n._('Sieve Filter Rules for {0}'), this.account.get('name'));
//        this.window.setTitle(title);
//        
//        this.loadMask.hide();
    },
        
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     * 
     */
    getFormItems: function() {
        this.linkGrid = new Tine.Filemanager.DownloadLinkGridPanel({
            //account: this.account
        });
        
        return [this.linkGrid];
    },
    
    /**
     * apply changes handler (get rules and send them to saveRules)
     */
//    onApplyChanges: function(closeWindow) {
//        var rules = [];
//        this.rulesGrid.store.each(function(record) {
//            rules.push(record.data);
//        });
//        
//        this.loadMask.show();
//        Tine.Felamimail.rulesBackend.saveRules(this.account.id, rules, {
//            scope: this,
//            success: function(record) {
//                if (closeWindow) {
//                    this.purgeListeners();
//                    this.window.close();
//                }
//            },
//            failure: Tine.Felamimail.handleRequestException.createSequence(function() {
//                this.loadMask.hide();
//            }, this),
//            timeout: 150000 // 3 minutes
//        });
//    }
});

/**
 * DownloadLink Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Filemanager.DownloadLinkDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 400,
        name: Tine.Filemanager.DownloadLinkDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Filemanager.DownloadLinkDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

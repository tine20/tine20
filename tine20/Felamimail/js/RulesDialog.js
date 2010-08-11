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
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.RulesDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Sieve Filter Dialog</p>
 * <p>This dialog is editing sieve filters (rules).</p>
 * <p>
 * TODO         implement onApplyChanges
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RulesDialog
 */
Tine.Felamimail.RulesDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @cfg {Tine.Felamimail.Model.Account}
     */
    account: null,

    /**
     * @private
     */
    windowNamePrefix: 'VacationEditWindow_',
    appName: 'Felamimail',
    //recordClass: Tine.Felamimail.Model.Rules,
    loadRecord: false,
    mode: 'local',
    tbarItems: [],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * 
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * init record to edit
     * -> we don't have a real record here
     */
    initRecord: function() {
        this.onRecordLoad();
    },
    
    /**
     * executed after record got updated from proxy
     * -> we don't have a real record here
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        var title = String.format(this.app.i18n._('Sieve Filter Rules for {0}'), this.account.get('name'));
        this.window.setTitle(title);
        
        this.loadMask.hide();
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
        this.rulesGrid = new Tine.Felamimail.RulesGridPanel({
            account: this.account
        }); 
        
        return [this.rulesGrid];
    },
    
    /**
     * generic apply changes handler
     * 
     * TODO get all rules from grid and send them to Felamimail.saveRules
     */
    onApplyChanges: function(button, event, closeWindow) {
        Tine.log.info('not yet implemented');
        
        if (closeWindow) {
            this.purgeListeners();
            this.window.close();
        }
    }
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.RulesDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 400,
        name: Tine.Felamimail.RulesDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.RulesDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

/*
 * Tine 2.0
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Expressomail.sieve');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.sieve.RulesDialog
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
Tine.Expressomail.sieve.RulesDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @cfg {Tine.Expressomail.Model.Account}
     */
    account: null,

    /**
     * @private
     */
    windowNamePrefix: 'VacationEditWindow_',
    appName: 'Expressomail',
//    loadRecord: false,
    mode: 'local',
    tbarItems: [],
    evalGrants: false,
    
    //private
    initComponent: function(){
        Tine.Expressomail.sieve.RulesDialog.superclass.initComponent.call(this);
        
        this.i18nRecordName = this.app.i18n._('Sieve Filter Rules');
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
        this.rulesGrid = new Tine.Expressomail.sieve.RulesGridPanel({
            account: this.account
        });
        
        return [this.rulesGrid];
    },
    
    /**
     * apply changes handler (get rules and send them to saveRules)
     */
    onApplyChanges: function(closeWindow) {
        var rules = [];
        this.rulesGrid.store.each(function(record) {
            rules.push(record.data);
        });
        
        this.loadMask.show();
        Tine.Expressomail.rulesBackend.saveRules(this.account.id, rules, {
            scope: this,
            success: function(record) {
                if (closeWindow) {
                    this.purgeListeners();
                    this.window.close();
                }
            },
            failure: Tine.Expressomail.handleRequestException.createSequence(function() {
                this.loadMask.hide();
            }, this),
            timeout: 150000 // 3 minutes
        });
    }
});

/**
 * Expressomail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Expressomail.sieve.RulesDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 400,
        name: Tine.Expressomail.sieve.RulesDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Expressomail.sieve.RulesDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

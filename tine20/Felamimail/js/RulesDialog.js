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
 * @extends     Ext.TabPanel
 * 
 * <p>Sieve Filter Dialog</p>
 * <p>This dialog is editing sieve filters (rules).</p>
 * <p>
 * TODO         make it work
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
Tine.Felamimail.RulesDialog = Ext.extend(Ext.TabPanel, {

    /**
     * @cfg {Tine.Felamimail.Model.Account}
     */
    account: null,
    
    activeTab: 0,

    /**
     * @private
     */
    initComponent: function() {
        
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.title = String.format(this.app.i18n._('Sieve Filter Rules for {0}'), this.account.get('name'));
        
        this.items = [new Tine.Felamimail.RulesGridPanel({
            title: this.app.i18n._('Rules'),
            account: this.account
        })];
        
        Tine.Felamimail.RulesDialog.superclass.initComponent.call(this);
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
        width: 640,
        height: 480,
        name: Tine.Felamimail.RulesDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.RulesDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

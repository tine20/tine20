/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine', 'Tine.Setup');

/**
 * @namespace   Tine.Setup
 * @class       Tine.Setup.MainScreen
 * @extends     Tine.Tinebase.widgets.app.MainScreen
 * 
 * <p>MainScreen Definition</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.MainScreen
 */
Tine.Setup.MainScreen = Ext.extend(Tine.Tinebase.widgets.app.MainScreen, {
    
    /**
     * active panel
     * 
     * @property activePanel
     * @type String
     */
    activePanel: 'EnvCheckGridPanel',
    
    /**
     * set content panel
     */
    setContentPanel: function() {
        
        // which content panel?
        var panel = this.activePanel;
        
        if (! this[panel]) {
            this[panel] = new Tine.Setup[panel]({
                app: this.app
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this[panel], true);
        
        if (this[panel].hasOwnProperty('store')) {
            this[panel].store.load();
        }
    },
    
    /**
     * get content panel
     * 
     * @return {Ext.Panel}
     */
    getContentPanel: function() {
        return this[this.activePanel];
    },
    
    /**
     * sets toolbar in mainscreen
     */
    setToolbar: function() {
        var panel = this.activePanel;
        
        if (! this[panel + 'ActionToolbar']) {
            this[panel + 'ActionToolbar'] = this[panel].actionToolbar;
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this[panel + 'ActionToolbar'], true);
        
        // hide stuff in main menu
        Tine.Tinebase.MainScreen.getMainMenu().action_changePassword.setHidden(true);
        Tine.Tinebase.MainScreen.getMainMenu().action_showPreferencesDialog.setHidden(true);
    }
});

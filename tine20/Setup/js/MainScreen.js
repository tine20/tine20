/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine', 'Tine.Setup');

/**
 * @namespace   Tine.Setup
 * @class       Tine.Setup.MainScreen
 * @extends     Tine.widgets.MainScreen
 * 
 * <p>MainScreen Definition</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.MainScreen
 */
Tine.Setup.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    
    /**
     * active panel
     * 
     * @property activePanel
     * @type String
     */
    activePanel: 'EnvCheckGridPanel',

    /**
     * get content panel
     *
     * @return {Ext.Panel}
     */
    getCenterPanel: function() {
        if (! this[this.activePanel]) {
            this[this.activePanel] = new Tine.Setup[this.activePanel]({
                app: this.app
            });
        }

        return this[this.activePanel];
    },

    /**
     * get north panel for given contentType
     *
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getNorthPanel: function(contentType) {
        var panel = this.activePanel;
        
        if (! this[panel + 'ActionToolbar']) {
            this[panel + 'ActionToolbar'] = this[panel].actionToolbar;
        }
        
        // hide and disable stuff in main menu
        Tine.Tinebase.MainScreen.getMainMenu().action_changePassword.setHidden(true);
        Tine.Tinebase.MainScreen.getMainMenu().action_showPreferencesDialog.setHidden(true);
        Tine.Tinebase.MainScreen.getMainMenu().action_editProfile.setDisabled(true);

        return this[panel + 'ActionToolbar'];
    },
    
    /**
     * get west panel for given contentType
     * 
     * template method to be overridden by subclasses to modify default behaviour
     * 
     * @return {Ext.Panel}
     */
    getWestPanel: function() {
        if (! this.setupTreePanel) {
            this.setupTreePanel = new Tine.Setup.TreePanel();
        }
        
        return this.setupTreePanel;
    }
});

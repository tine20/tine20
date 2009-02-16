/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Tinebase.widgets.app');

Tine.Tinebase.widgets.app.MainScreen = function(config) {
    Ext.apply(this, config);
    
    this.addEvents(
        /**
         * @event beforeshow
         * Fires before the component is shown. Return false to stop the show.
         * @param {Ext.Component} this
         */
        'beforeshow',
        /**
         * @event show
         * Fires after the component is shown.
         * @param {Ext.Component} this
         */
        'show'
    );
    Tine.Tinebase.widgets.app.MainScreen.superclass.constructor.call(this);
};

Ext.extend(Tine.Tinebase.widgets.app.MainScreen, Ext.util.Observable, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,
    
    /**
     * @property {Ext.Panel} treePanel
     */
    /**
     * @property {Tine.widgets.app.GridPanel} gridPanel 
     */
    /**
     * @property {Ext.Toolbar} actionToolbar
     */
    
    /**
     * shows/activates this app mainscreen
     * 
     * @return {Tine.Tinebase.widgets.app.MainScreen} this
     */
    show: function() {
        if(this.fireEvent("beforeshow", this) !== false){
            this.setTreePanel();
            this.setContentPanel();
            this.setToolbar();
            this.updateMainToolbar();
            
            this.fireEvent('show', this);
        }
        return this;
    },
    
    onHide: function() {
        
    },
    
    /**
     * sets tree panel in mainscreen
     */
    setTreePanel: function() {
        if(!this.treePanel) {
            this.treePanel = new Tine[this.app.appName].TreePanel({app: this.app});
        }
        
        if(!this.filterPanel && Tine[this.app.appName].FilterPanel) {
            this.filterPanel = new Tine[this.app.appName].FilterPanel({
                app: this.app,
                treePanel: this.treePanel
            });
        }
        if (this.filterPanel) {
            Tine.Tinebase.MainScreen.setActiveTreePanel(new Ext.TabPanel({
                border: false,
                activeItem: 0,
                layoutOnTabChange: true,
                items: [{
                    title: _('Containers'),
                    items: this.treePanel
                }, {
                    title: _('Filter'),
                    items: this.filterPanel
                }]
            
            }), true);
        } else {
            Tine.Tinebase.MainScreen.setActiveTreePanel(this.treePanel, true);
        }
    },
    
    /**
     * sets content panel in mainscreen
     */
    setContentPanel: function() {
        if(!this.gridPanel) {
            var plugins = [];
            if (typeof(this.treePanel.getFilterPlugin) == 'function') {
                plugins.push(this.treePanel.getFilterPlugin());
            }
            
            this.gridPanel = new Tine[this.app.appName].GridPanel({
                app: this.app,
                plugins: plugins
            });
        }
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
        this.gridPanel.store.load();
    },
    
    /**
     * sets toolbar in mainscreen
     */
    setToolbar: function() {
        if(!this.actionToolbar) {
            this.actionToolbar = this.gridPanel.actionToolbar;
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this.actionToolbar, true);
    },
    
    /**
     * updates main toolbar
     */
    updateMainToolbar : function() {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('TasksTreePanel');

        adminButton.setDisabled(true);

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('TasksTreePanel');
        preferencesButton.setDisabled(true);
    }
    
});
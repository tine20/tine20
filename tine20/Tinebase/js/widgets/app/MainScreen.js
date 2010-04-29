/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.Tinebase.widgets.app');

/**
 * @namespace   Tine.Tinebase.widgets.app
 * @class       Tine.Tinebase.widgets.app.MainScreen
 * @extends     Ext.util.Observable
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @constructor
 * @todo refactor set/get tree stuff
 */
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
     * @cfg {String} activeContentType
     */
    activeContentType: '',
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
            this.showWestPanel();
            this.showCenterPanel();
            this.showNorthPanel();
            
            this.fireEvent('show', this);
        }
        return this;
    },
    
    onHide: function() {
        
    },
    
    /**
     * sets tree panel in mainscreen
     */
    showWestPanel: function() {
        if(!this.treePanel) {
            this.treePanel = new Tine[this.app.appName].TreePanel({app: this.app});
        }
        
        if(!this.filterPanel && Tine[this.app.appName].FilterPanel) {
            //console.log('creating filterPanel for ' + this.app.appName);
            this.filterPanel = new Tine[this.app.appName].FilterPanel({
                app: this.app,
                treePanel: this.treePanel
            });
        }
        
        if (this.filterPanel) {
            
            if (! this.leftTabPanel) {
                //console.log('creating leftTabPanel for ' + this.app.appName);
                var containersName = 'not found';
                if (this.treePanel.recordClass) {
                    var containersName = this.app.i18n.n_hidden(this.treePanel.recordClass.getMeta('containerName'), this.treePanel.recordClass.getMeta('containersName'), 50);
                }
                
                this.leftTabPanel = new Ext.TabPanel({
                    border: false,
                    activeItem: 0,
                    layoutOnTabChange: true,
                    autoScroll: true,
                    items: [{
                        title: containersName,
                        layout: 'fit',
                        items: this.treePanel,
                        autoScroll: true
                    }, {
                        title: _('Saved filter'),
                        layout: 'fit',
                        items: this.filterPanel,
                        autoScroll: true
                    }],
                    getPersistentFilterNode: this.filterPanel.getPersistentFilterNode.createDelegate(this.filterPanel)
                
                });
            }
            
            Tine.Tinebase.MainScreen.setActiveTreePanel(this.leftTabPanel, true);
        } else {
            Tine.Tinebase.MainScreen.setActiveTreePanel(this.treePanel, true);
        }
    },
    
    getTreePanel: function() {
        if (this.leftTabPanel) {
            return this.leftTabPanel;
        } else {
            return this.treePanel;
        }
    },
    
    /**
     * sets content panel in mainscreen
     */
    showCenterPanel: function() {
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.getContentPanel(), true);
    },
    
    
    getContentPanel: function() {
        // which content panel?
        var type = this.activeContentType;
        
        if (! this[type + 'GridPanel']) {
            this[type + 'GridPanel'] = new Tine[this.app.appName][type + 'GridPanel']({
                app: this.app,
                plugins: [this.treePanel.getFilterPlugin()]
            });
        }
        
        return this[type + 'GridPanel'];
    },
    
    /**
     * sets toolbar in mainscreen
     */
    showNorthPanel: function() {
        var type = this.activeContentType;
        
        if (! this[type + 'ActionToolbar']) {
            this[type + 'ActionToolbar'] = this[type + 'GridPanel'].getActionToolbar();
        }
        
        Tine.Tinebase.MainScreen.setActiveToolbar(this[type + 'ActionToolbar'], true);
    }
});

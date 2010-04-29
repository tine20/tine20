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
     * returns active content type
     * 
     * @return {String}
     */
    getActiveContentType: function() {
        return this.activeContentType;
    },
    
    /**
     * get center panel for given contentType
     * 
     * template method to be overridden by subclasses to modify default behaviour
     * 
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getCenterPanel: function(contentType) {
        if (! this[contentType + 'GridPanel']) {
            this[contentType + 'GridPanel'] = new Tine[this.app.appName][contentType + 'GridPanel']({
                app: this.app,
                plugins: [this.treePanel.getFilterPlugin()]
            });
        }
        
        return this[contentType + 'GridPanel'];
    },
    
    /**
     * get north panel for given contentType
     * 
     * template method to be overridden by subclasses to modify default behaviour
     * 
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getNorthPanel: function(contentType) {
        console.log(this);
        if (! this[contentType + 'ActionToolbar']) {
            this[contentType + 'ActionToolbar'] = this[contentType + 'GridPanel'].getActionToolbar();
        }
        
        return this[contentType + 'ActionToolbar'];
    },
    
    /**
     * get west panel for given contentType
     * 
     * template method to be overridden by subclasses to modify default behaviour
     * 
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getWestPanel: function(contentType) {
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
            
            return this.leftTabPanel;
        } else {
            return this.treePanel;
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
    
    /**
     * shows center panel in mainscreen
     */
    showCenterPanel: function() {
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.getCenterPanel(this.getActiveContentType()), true);
    },
    
    /**
     * shows west panel in mainscreen
     */
    showWestPanel: function() {
        Tine.Tinebase.MainScreen.setActiveTreePanel(this.getWestPanel(this.getActiveContentType()), true);
    },
    
    /**
     * shows north panel in mainscreen
     */
    showNorthPanel: function() {
        Tine.Tinebase.MainScreen.setActiveToolbar(this.getNorthPanel(this.getActiveContentType()), true);
    }
});

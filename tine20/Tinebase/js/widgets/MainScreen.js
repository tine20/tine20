/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets');

/**
 * @namespace   Tine.widgets
 * @class       Tine.widgets.MainScreen
 * @extends     Ext.util.Observable
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @constructor
 */
Tine.widgets.MainScreen = function(config) {
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
    
    this.useModuleTreePanel = Ext.isArray(this.contentTypes) && this.contentTypes.length > 1;
    
    Tine.widgets.MainScreen.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.MainScreen, Ext.util.Observable, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,
    
    /**
     * @cfg {String} activeContentType
     */
    activeContentType: null,
    /**
     * @cfg {Array} contentTypes
     */
    contentTypes: null,
    
    /**
     * @cfg {String} centerPanelClassName
     * name of centerpanel class name suffix in namespace of this app (defaults to GridPanel)
     * the class name will be expanded to Tine[this.appName][contentType + this.centerPanelClassNameSuffix]
     */
    centerPanelClassNameSuffix: 'GridPanel',

    /**
     * private 
     */
    
    /**
     * @type {Bool} useModuleTreePanel
     * use modulePanel (defaults to null -> autodetection)
     */
    useModuleTreePanel: null,
    
    /**
     * returns active content type
     * 
     * @return {String}
     */
    getActiveContentType: function() {
        return (this.activeContentType) ? this.activeContentType : '';
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
        contentType = contentType || this.getActiveContentType();
        
        if (! this[contentType + this.centerPanelClassNameSuffix]) {
            try {
                this[contentType + this.centerPanelClassNameSuffix] = new Tine[this.app.appName][contentType + this.centerPanelClassNameSuffix]({
                    app: this.app,
                    plugins: [this.getWestPanel().getFilterPlugin(contentType)]
                });
            } catch (e) {
                Tine.log.err('Could not create centerPanel "Tine.' + this.app.appName + '.' + contentType + this.centerPanelClassNameSuffix + '"');
                Tine.log.err(e.stack ? e.stack : e);
                this[contentType + this.centerPanelClassNameSuffix] = new Ext.Panel({html: 'ERROR'});
            }
        }
        
        return this[contentType + this.centerPanelClassNameSuffix];
    },
    
    /**
     * convinience fn to get container tree panel from westpanel
     * 
     * @return {Tine.widgets.container.containerTreePanel}
     *
    getContainerTreePanel: function() {
        return this.getWestPanel().getContainerTreePanel();
    },
    */
    
    /**
     * get north panel for given contentType
     * 
     * template method to be overridden by subclasses to modify default behaviour
     * 
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getNorthPanel: function(contentType) {
        contentType = contentType || this.getActiveContentType();
        
        if (! this[contentType + 'ActionToolbar']) {
            try {
                this[contentType + 'ActionToolbar'] = this[contentType + this.centerPanelClassNameSuffix].getActionToolbar();
            } catch (e) {
                Tine.log.err('Could not create northPanel');
                Tine.log.err(e.stack ? e.stack : e);
                this[contentType + 'ActionToolbar'] = new Ext.Panel({html: 'ERROR'});
            }
        }

        return this[contentType + 'ActionToolbar'];
    },
    
    /**
     * get module tree panel
     * 
     * @return {Ext.Panel}
     */
    getModuleTreePanel: function() {
        if (! this.moduleTreePanel) {
            if (this.useModuleTreePanel) {
                this.moduleTreePanel = new Tine.widgets.ContentTypeTreePanel({
                    app: this.app, 
                    contentTypes: this.contentTypes,
                    contentType: this.getActiveContentType()
                });
                this.moduleTreePanel.on('click', function (node, event) {
                    if(node != this.lastClickedNode) {
                        this.lastClickedNode = node;
                        this.fireEvent('selectionchange');
                    }
                });
            } else {
                this.moduleTreePanel = new Ext.Panel({html:'', border: false, frame: false});
            } 
        }
        return this.moduleTreePanel;
    },
    
    /**
     * get west panel for given contentType
     * 
     * template method to be overridden by subclasses to modify default behaviour
     * 
     * @return {Ext.Panel}
     */
    getWestPanel: function() {
        var contentType = this.getActiveContentType(),
            wpName = contentType + 'WestPanel';
            
        if (! this[wpName]) {
            var wpconfig = {
                    app: this.app, 
                    contentTypes: this.contentTypes,
                    contentType: contentType,
                    listeners: {
                        scope: this,
                        selectionchange: function() {
                            var cp = this.getCenterPanel();
                            if(cp) {
                                try {
                                    var grid = cp.getGrid();
                                    if(grid) {
                                        var sm = grid.getSelectionModel();
                                        if(sm) {
                                            sm.clearSelections();
                                            cp.actionUpdater.updateActions(sm.getSelectionsCollection());
                                        }
                                    }
                                } catch (e) {
                                    // do nothing - no grid
                                }
                            }
                        }
                    }
                };
            try {
                if(Tine[this.app.name].hasOwnProperty(wpName)) this[wpName] = new Tine[this.app.appName][wpName](wpconfig);
                else this[wpName] = new Tine.widgets.mainscreen.WestPanel(wpconfig);
            } catch (e) {
                Tine.log.err('Could not create westPanel');
                Tine.log.err(e.stack ? e.stack : e);
                this[wpName] = new Ext.Panel({html: 'ERROR'});
            }
        }
        return this[wpName];
    },
    
    /**
     * shows/activates this app mainscreen
     * 
     * @return {Tine.widgets.MainScreen} this
     */
    show: function() {
        if(this.fireEvent("beforeshow", this) !== false){
            this.showWestPanel();
            this.showCenterPanel();
            this.showNorthPanel();
            this.showModuleTreePanel()
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
     * shows module tree panel in mainscreen
     */
    showModuleTreePanel: function() {
        Tine.Tinebase.MainScreen.setActiveModulePanel(this.getModuleTreePanel(), true);
    },
    
    /**
     * shows west panel in mainscreen
     */
    showWestPanel: function() {
        Tine.Tinebase.MainScreen.setActiveTreePanel(this.getWestPanel(), true);
    },
    
    /**
     * shows north panel in mainscreen
     */
    showNorthPanel: function() {
        Tine.Tinebase.MainScreen.setActiveToolbar(this.getNorthPanel(this.getActiveContentType()), true);
    }
});

/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine', 'Tine.Tinebase');

/**
 * Tine 2.0 library/ExtJS client Mainscreen.
 */
Tine.Tinebase.MainScreen = Ext.extend(Ext.Panel, {
    
    border: false,
    layout: {
        type:'vbox',
        align:'stretch',
        padding:'0'
    },
    
    /**
     * @cfg {String} appPickerStyle "tabs" or "pile" defaults to "tabs"
     */
    appPicker: 'tabs',
    
    /**
     * @private
     */
    initComponent: function() {
        this.tineMenu = new Tine.Tinebase.MainMenu({});
        //this.appPicker = new Tine.Tinebase.AppPicker({});
        
        this.initLayout();
        
        Tine.Tinebase.appMgr.on('activate', this.onAppActivate, this);
        
        Tine.Tinebase.MainScreen.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    initLayout: function() {
        
        this.items = [{
            cls: 'tine-mainscreen-topbox',
            border: false,
            html: '<div class="tine-mainscreen-topbox-left"></div><div class="tine-mainscreen-topbox-middle"></div><div class="tine-mainscreen-topbox-right"></div>'
        }/*, {
            cls: 'tine-mainscreen-mainmenu',
            height: 26,
            layout: 'fit',
            border: false,
            items: this.tineMenu,
            hidden: false
        }*/, {
            cls: 'tine-mainscreen-statusbar',
            height: 26,
            layout: 'fit',
            border: false,
            //hidden: false,
            items: this.tineMenu
            //items: this.getStatusBar()
        }, {
            cls: 'tine-mainscreen-apptabs',
            hidden: this.appPicker != 'tabs',
            border: false,
            height: 22,
            items: new Tine.Tinebase.AppTabsPanel({
                plain: true
            })
        }, {
            cls: 'tine-mainscreen-centerpanel',
            flex: 1,
            layout: 'border',
            border: false,
            items: [{
                cls: 'tine-mainscreen-centerpanel-north',
                region: 'north',
                layout: 'card',
                activeItem: 0,
                height: 26,
                border: false,
                id:     'north-panel-2',
                items: []
            }, {
                cls: 'tine-mainscreen-centerpanel-center',
                region: 'center',
                id: 'center-panel',
                animate: true,
                border: false,
                layout: 'card',
                activeItem: 0,
                items: []
            }, {
                cls: 'tine-mainscreen-centerpanel-west',
                region: 'west',
                id: 'west',
                layout: 'border',
                split: true,
                width: 200,
                minSize: 100,
                maxSize: 300,
                border: false,
                collapsible:true,
                collapseMode: 'mini',
                header: false,
                items: [{
                    cls: 'tine-mainscreen-centerpanel-west-apptitle',
                    hidden: this.appPicker != 'pile',
                    region: 'north',
                    layout: 'fit',
                    border: false,
                    height: 40,
                    baseCls: 'x-panel-header',
                    html: '<div class ="app-panel-title"></div>'
                }, {
                    cls: 'tine-mainscreen-centerpanel-west-treecards',
                    border: false,
                    id: 'treecards',
                    region: 'center',
                    layout: 'card',
                    activeItem: 0,
                    items: []
                }, new Tine.Tinebase.AppPile({
                    cls: 'tine-mainscreen-centerpanel-west-apppile',
                    hidden: this.appPicker != 'pile',
                    region: 'south',
                    layout: 'fit',
                    border: false,
                    split: true,
                    collapsible:true,
                    collapseMode: 'mini',
                    header: false
                })]
            }]
        }];
    },
    
    getStatusBar: function() {
        if (! this.statusBar) {
            this.statusBar = new Ext.Toolbar({
                items:[]
            });
        }
        
        return this.statusBar;
    },
    
    onAppActivate: function(app) {
        // set document / browser title
        var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '';
        document.title = Tine.title + postfix  + ' - ' + app.getTitle();
        
        // set left top title
        Ext.DomQuery.selectNode('div[class=app-panel-title]').innerHTML = app.getTitle();
        
    },
    
    onRender: function(ct, position) {
        Tine.Tinebase.MainScreen.superclass.onRender.call(this, ct, position);
        
        // argh!!! fixme!!!
        Tine.Tinebase.MainScreen = this;
        
        this.activateDefaultApp();
        
        // check for new version 
        if (Tine.Tinebase.common.hasRight('check_version', 'Tinebase')) {
            Tine.widgets.VersionCheck();
        }
    },
    
    activateDefaultApp: function() {
        if (Ext.getCmp('treecards').rendered) {
            Tine.Tinebase.appMgr.activate();
        } else {
            this.activateDefaultApp.defer(10, this);
        }
    },
    
    /**
     * sets the active content panel
     * 
     * @param {Ext.Panel} _panel Panel to activate
     * @param {Bool} _keep keep panel
     */
    setActiveContentPanel: function(_panel, _keep) {
        // get container to which component will be added
        var centerPanel = Ext.getCmp('center-panel');
        _panel.keep = _keep;

        var i,p;
        if(centerPanel.items) {
            for (i=0; i<centerPanel.items.length; i++){
                p =  centerPanel.items.get(i);
                if (! p.keep) {
                    centerPanel.remove(p);
                }
            }  
        }
        if(_panel.keep && _panel.rendered) {
            centerPanel.layout.setActiveItem(_panel.id);
        } else {
            centerPanel.add(_panel);
            centerPanel.layout.setActiveItem(_panel.id);
            centerPanel.doLayout();
        }
    },
    
    /**
     * sets the active tree panel
     * 
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveTreePanel: function(panel, keep) {
        // get card panel to which component will be added
        var cardPanel = Ext.getCmp('treecards');
        panel.keep = keep;
        
        // remove all panels which should not be keeped
        var i,p;
        if(cardPanel.items) {
            for (i=0; i<cardPanel.items.length; i++){
                p =  cardPanel.items.get(i);
                if (! p.keep) {
                    cardPanel.remove(p);
                }
            }  
        }
        
        // add or set given panel
        if(panel.keep && panel.rendered) {
            cardPanel.layout.setActiveItem(panel.id);
        } else {
            cardPanel.add(panel);
            cardPanel.layout.setActiveItem(panel.id);
            cardPanel.doLayout();
        }
        
    },
    
    /**
     * gets the currently displayed toolbar
     * 
     * @return {Ext.Toolbar}
     */
    getActiveToolbar: function() {
        var northPanel = Ext.getCmp('north-panel-2');

        if(northPanel.layout.activeItem && northPanel.layout.activeItem.el) {
            return northPanel.layout.activeItem.el;
        } else {
            return false;            
        }
    },
    
    /**
     * sets toolbar
     * 
     * @param {Ext.Toolbar}
     */
    setActiveToolbar: function(_toolbar, _keep) {
        var northPanel = Ext.getCmp('north-panel-2');
        _toolbar.keep = _keep;
        
        var i,t;
        if(northPanel.items) {
            for (i=0; i<northPanel.items.length; i++){
                t = northPanel.items.get(i);
                if (! t.keep) {
                    northPanel.remove(t);
                }
            }  
        }
        
        if(_toolbar.keep && _toolbar.rendered) {
            northPanel.layout.setActiveItem(_toolbar.id);
        } else {
            northPanel.add(_toolbar);
            northPanel.layout.setActiveItem(_toolbar.id);
            northPanel.doLayout();
        }
    }
});

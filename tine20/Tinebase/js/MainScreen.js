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
    
    //private
    layout: 'border',
    border: false,
    
    /**
     * @private
     */
    initComponent: function() {
        this.onlineStatus = new Ext.ux.ConnectionStatus({});
        this.tineMenu = new Tine.Tinebase.MainMenu({});
        this.appPicker = new Tine.Tinebase.AppPicker({});
                    
        // init generic mainscreen layout
        var mainscreen = [{
            region: 'north',
            id:     'north-panel',
            split:  false,
            height: /*'platform' in window ? 26 :*/ 52,
            border: false,
            layout:'border',
            items: [/*'platform' in window ? {} :*/ {
                region: 'north',
                height: 26,
                border: false,
                id:     'north-panel-1',
                items: [
                    this.tineMenu
                ]
            },{
                region: 'center',
                layout: 'card',
                activeItem: 0,
                height: 26,
                border: false,
                id:     'north-panel-2',
                items: []
            }]
        }, {
            region: 'south',
            id: 'south',
            border: false,
            split: false,
            height: 26,
            initialSize: 26,
            items:[new Ext.Toolbar({
                id: 'tineFooter',
                height: 26,
                items:[
                    String.format(_('User: {0}'), Tine.Tinebase.registry.get('currentAccount').accountDisplayName), 
                    '->',
                    this.onlineStatus
                ]
        
            })]
        }, {
            region: 'center',
            id: 'center-panel',
            animate: true,
            useShim:true,
            border: false,
            layout: 'card'
        }, {
            region: 'west',
            id: 'west',
            split: true,
            width: 200,
            minSize: 100,
            maxSize: 300,
            border: false,
            collapsible:true,
            //containerScroll: true,
            collapseMode: 'mini',
            header: false,
            layout: 'fit',
            items: this.appPicker
        }];
        
        this.items = [{
            region: 'north',
            border: false,
            cls: 'tine-mainscreen-topbox',
            html: '<div class="tine-mainscreen-topbox-left"></div><div class="tine-mainscreen-topbox-middle"></div><div class="tine-mainscreen-topbox-right"></div>'
        }, {
            region: 'center',
            border: false,
            layout: 'border',
            items: mainscreen
        }];
        
        Tine.Tinebase.MainScreen.superclass.initComponent.call(this);
    },
    
    onRender: function(ct, position) {
        Tine.Tinebase.MainScreen.superclass.onRender.call(this, ct, position);
        Tine.Tinebase.MainScreen = this;
        this.activateDefaultApp();
        
        // check for new version 
        if (Tine.Tinebase.common.hasRight('check_version', 'Tinebase')) {
            Tine.widgets.VersionCheck();
        }
    },
    
    activateDefaultApp: function() {
        if (this.appPicker.getTreeCardPanel().rendered) {
            var defaultApp = Tine.Tinebase.appMgr.getDefault();
            defaultApp.getMainScreen().show();
            var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '';
            document.title = Tine.title + postfix  + ' - ' + defaultApp.getTitle();
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
        var cardPanel =  this.appPicker.getTreeCardPanel();
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

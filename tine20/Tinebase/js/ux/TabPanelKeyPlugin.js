/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux');

/**
 * @namespace   Ext.ux
 * @class       Ext.ux.TabPanelKeyPlugin
 * @author      Alexander Stintzing <alex@stintzing.net>
 */
Ext.ux.TabPanelKeyPlugin = function(config) {
    Ext.apply(this, config);
};

Ext.ux.TabPanelKeyPlugin.prototype = {
    panel : null,
        
    init : function(panel) {
        this.panel = panel;
        this.panel.on('afterrender', this.onRender, this);
        this.panel.on('add', this.onItemAdd, this);
    },
    
    /**
     * creates shortcuts for tabs
     */
    onRender: function() {
        
        if (! this.panel.rendered) {
            this.onRender.defer(250, this);
            return;
        }

        var tabCount = this.panel.items.length;
        
        for (var index = 0; index < tabCount; index++) {
            var item = this.panel.items.items[index];
            this.registerKeyMap(item, index);
        }
    },

    onItemAdd: function(panel, item, index) {
        this.registerKeyMap(item, index);
    },

    registerKeyMap: function(item, index, el) {
        el = el || Ext.getBody();

        if(item.disabled !== true) {
            new Ext.KeyMap(el, [{
                key: index + 49,
                ctrl: true,
                scope: this,
                fn: this.switchTab
            }]);
        }
    },

    /**
     * switch to tab
     * @param Integer code
     */
    switchTab: function(code) {
        var number = parseInt(code) - 49;
        if (this.panel) {
            this.panel.setActiveTab(number);
        }
    }
}

Ext.preg('ux.tabpanelkeyplugin', Ext.ux.TabPanelKeyPlugin);
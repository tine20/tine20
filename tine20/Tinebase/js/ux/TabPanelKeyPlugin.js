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
        this.panel.onRender = this.panel.onRender.createSequence(this.onRender, this);
    },
    
    /**
     * creates shortcuts for tabs
     */
    onRender: function() {
        
        if (! this.panel.rendered) {
            this.onRender.defer(250, this);
            return;
        }
        
        try {
            var tabCount = this.panel.items.length;
            
            for (var index = 0; index < tabCount; index++) {
                var item = this.panel.items.items[index];
                if(item.disabled !== true) {
                    new Ext.KeyMap(this.panel.el, [{
                        key: index + 49,
                        ctrl: true,
                        scope: this,
                        fn: this.switchTab
                    }]);
                }
            }
        } catch (e) {
            Tine.log.error('Ext.ux.TabPanelKeyPlugin::onRender');
            Tine.log.error(e.stack ? e.stack : e);
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
/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets.editDialog');

/**
 * @namespace   Tine.widgets.editDialog
 * @class       Tine.widgets.dialog.KeyShortcutsEditDialogPlugin
 * @author      Alexander Stintzing <alex@stintzing.net>
 */
Tine.widgets.dialog.KeyShortcutsEditDialogPlugin = function(config) {
    Ext.apply(this, config);
};

Tine.widgets.dialog.KeyShortcutsEditDialogPlugin.prototype = {
    editDialog : null,
    tabPanel: null,
    
    init : function(editDialog) {
        this.tabPanel = editDialog.items.find(function(item) {
            return Ext.isObject(item) && Ext.isFunction(item.getXType) && item.getXType() == 'tabpanel';
        });
        
        this.editDialog = editDialog;
        this.editDialog.onRender = this.editDialog.onRender.createSequence(this.onRender, this);
    },
    
    /**
     * creates shortcuts for tabs
     */
    onRender: function() {
        
        if (! this.editDialog.rendered) {
            this.onRender.defer(250, this);
            return;
        }
        
        try {
            var tabCount = (this.tabPanel) ? this.tabPanel.items.items.length : 0;
            
            for (var index = 0; index < tabCount; index++) {
                var item = this.tabPanel.items.items[index];
                if(item.disabled !== true) {
                    new Ext.KeyMap(this.editDialog.window.el, [{
                        key: index + 49,
                        ctrl: true,
                        scope: this,
                        fn: this.switchTab
                    }]);
                }
            }
        } catch (e) {
            Tine.log.error('Tine.widgets.dialog.KeyShortcutsEditDialogPlugin::onRender');
            Tine.log.error(e.stack ? e.stack : e);
        }
    },
    
    /**
     * switch to tab
     * @param Integer code
     */
    switchTab: function(code) {
        var number = parseInt(code) - 49;
        if (this.tabPanel) {
            this.tabPanel.setActiveTab(number);
        }
    }
}
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase');

Tine.Tinebase.MainContextMenu = Ext.extend(Ext.menu.Menu, {

    /**
     * context component
     * @property {Ext.Component}
     */
    context: null,

    initComponent: function() {
        this.plugins = [{
            ptype: 'ux.itemregistry',
            key:   'Tinebase-MainContextMenu'
        }];

        this.items = [];

        this.supr().initComponent.call(this);
    },

    onRender: function() {
        // hide fist separator
        var firstItem = this.items.get(0);
        if (firstItem && firstItem.isXType('menuseparator')) {
            this.items.get(0).hide();
        }
        this.supr().onRender.call(this);
    },

    onHide : function(){
        if (this.isVisible() && Ext.isFunction(Ext.EventObject.getSignature)) {
            Tine.Tinebase.MainContextMenu.hideEventSignature = Ext.EventObject.getSignature();
        }
        this.supr().onHide.call(this);
    }

});

/**
 * show main/global context menu if it has itmes
 *
 * @static
 * @param {Ext.EventObject} e
 * @returns {Ext.Component}
 */
Tine.Tinebase.MainContextMenu.showIf = function(e) {
    var menu = Tine.Tinebase.MainContextMenu.menu = new Tine.Tinebase.MainContextMenu({});

    if (menu.items.length) {
        menu.showAt(e.getXY());
        return menu;
    }
};

/**
 * get main context items
 *
 * @static
 * @param {Ext.EventObject} e
 * @returns {Ext.util.MixedCollection}
 */
Tine.Tinebase.MainContextMenu.getItems = function(e) {
    var menu = Tine.Tinebase.MainContextMenu.menu = new Tine.Tinebase.MainContextMenu({});

    return menu.items;
};

/**
 * get target component
 *
 * @static
 * @param {Ext.EventObject} e
 * @returns {Ext.Component}
 */
Tine.Tinebase.MainContextMenu.getCmp = function(e) {
    var target = e && e.target ? e.getTarget('[id^=ext-comp]', 30) : null,
        component = target ? Ext.ComponentMgr.get(target.id) : null;

    return component;
};

Tine.Tinebase.MainContextMenu.isVisible = function() {
    return Tine.Tinebase.MainContextMenu.hideEventSignature
        && Tine.Tinebase.MainContextMenu.hideEventSignature == Ext.EventObject.getSignature();
};


Tine.Tinebase.MainContextMenu.hide = function() {
    if (Tine.Tinebase.MainContextMenu.menu && Tine.Tinebase.MainContextMenu.menu.hide) {
        Tine.Tinebase.MainContextMenu.menu.hide();
    }
};
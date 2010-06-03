/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.ColorField
 * @extends     Ext.form.TriggerField
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * Provides a color input field with a {@link Ext.menu.ColorMenu} dropdown.
 * @constructor Create a new ColorField
 * @param {Object} config
 * @xtype colorfield
 */
Ext.ux.form.ColorField = Ext.extend(Ext.form.TriggerField, {
    listWidth: 150,
    editable: false,
    
    // private
    initComponent : function(){
        Ext.ux.form.ColorField.superclass.initComponent.call(this);
        
        this.store = new Ext.data.Store({});
        
        this.addEvents(
            /**
             * @event select
             * Fires when a color is selected via the color picker.
             * @param {Ext.form.ColorField} this
             * @param {String} color The color that was selected
             */
            'select'
        );
    },
    
    // private
    onDestroy : function(){
        Ext.destroy(this.menu);
        Ext.ux.form.ColorField.superclass.onDestroy.call(this);
    },
    
    //private
    onTriggerClick : function(){
        if(this.disabled){
            return;
        }
        if(this.menu == null){
            this.menu = new Ext.menu.ColorMenu({
                hideOnClick: false
            });
        }
        this.onFocus();

        this.menu.show(this.el, "tl-bl?");

        this.menuEvents('on');
    },

    setValue : function(color){
        Ext.ux.form.ColorField.superclass.setValue.call(this, color);
        this.el.setStyle('background', color);
        this.el.setStyle('color', color);
    },
    
    //private
    menuEvents: function(method){
        this.menu[method]('select', this.onSelect, this);
        this.menu[method]('hide', this.onMenuHide, this);
        this.menu[method]('show', this.onFocus, this);
    },
    
    //private
    onSelect: function(m, d){
        this.setValue('#'+d);
        this.fireEvent('select', this, '#'+d);
        this.menu.hide();
    },
    
    //private
    onMenuHide: function(){
        this.focus(false, 60);
        this.menuEvents('un');
    }
});

Ext.reg('colorfield', Ext.ux.form.ColorField);
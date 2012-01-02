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
 * @class       Ext.ux.MonthPickerPlugin
 * @author      Alexander Stintzing <alex@stintzing.net>
 */
Ext.ux.MonthPickerPlugin = function(config) {
    Ext.apply(this, config || {});
    
};

Ext.ux.MonthPickerPlugin.prototype = {
    
	init: function(picker) {
	   picker.onRender = picker.onRender.createSequence(this.onRender, picker);
       picker.update = picker.update.createSequence(this.update, picker);
	},

    onRender: function(picker) {
        var el = Ext.DomQuery.selectNode('table[class=x-date-inner]', this.getEl().dom);
        Ext.DomHelper.applyStyles(el, 'display: none');

        if(this.width) {
            var width = this.width - 2;
            var el1 = Ext.DomQuery.selectNode('table', this.getEl().dom);
            Ext.DomHelper.applyStyles(el1, 'width: ' + width + 'px');
        }
        
        this.mbtn.disable();
        this.mbtn.el.child('em').removeClass('x-btn-arrow');
        
        this.mbtn.removeClass('x-item-disabled');
    },
    
    update: function(picker) {
        this.fireEvent('change');
    }
    
};

Ext.preg('monthPickerPlugin', Ext.ux.MonthPickerPlugin);
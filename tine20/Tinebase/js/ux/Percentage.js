/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * increase depth of cell selector depth for event delegation (defaults to 4) 
 */
if (Ext.grid.GridView.prototype.cellSelectorDepth < 6) {
    Ext.grid.GridView.prototype.cellSelectorDepth = 6;
}

Ext.ns('Ext.ux');

/**
 * Percentage select combo box
 * 
 * @namespace   Ext.ux
 * @class       Ext.ux.PercentCombo
 * @extends     Ext.form.ComboBox
 */
Ext.ux.PercentCombo = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {bool} autoExpand Autoexpand comboBox on focus.
     */
    autoExpand: false,
    /**
     * @cfg {bool} blurOnSelect blurs combobox when item gets selected
     */
    blurOnSelect: false,
    
    displayField: 'value',
    valueField: 'key',
    mode: 'local',
    triggerAction: 'all',
    emptyText: 'percent ...',
    lazyInit: false,
    
    //private
    initComponent: function(){
        Ext.ux.PercentCombo.superclass.initComponent.call(this);
        // allways set a default
        if(!this.value) {
            this.value = 0;
        }
            
        this.store = new Ext.data.SimpleStore({
            fields: ['key','value'],
            data: [
                    ['0',    '0%'],
                    ['10',  '10%'],
                    ['20',  '20%'],
                    ['30',  '30%'],
                    ['40',  '40%'],
                    ['50',  '50%'],
                    ['60',  '60%'],
                    ['70',  '70%'],
                    ['80',  '80%'],
                    ['90',  '90%'],
                    ['100','100%']
                ]
        });
        
        if (this.autoExpand) {
            this.lazyInit = false;
            this.on('focus', function(){
                this.selectByValue(this.getValue());
                this.onTriggerClick();
            });
        }
        
        if (this.blurOnSelect){
            this.on('select', function(){
                this.fireEvent('blur', this);
            }, this);
        }
    },
    
    setValue: function(value) {
        value = value ? value : 0;
        Ext.ux.PercentCombo.superclass.setValue.call(this, value);
    }
});
Ext.reg('extuxpercentcombo', Ext.ux.PercentCombo);

/**
 * Renders a percentage value to a percentage bar
 * @constructor
 */
Ext.ux.PercentRenderer = function(percent) {
    if (! Ext.ux.PercentRenderer.template) {
        Ext.ux.PercentRenderer.template = new Ext.XTemplate(
            '<div class="x-progress-wrap PercentRenderer">',
            '<div class="x-progress-inner PercentRenderer">',
                '<div class="x-progress-bar PercentRenderer" style="width:{percent}%">',
                    '<div class="PercentRendererText PercentRenderer">',
                        '<div>{percent}%</div>',
                    '</div>',
                '</div>',
                '<div class="x-progress-text x-progress-text-back PercentRenderer">',
                    '<div>&#160;</div>',
                '</div>',
            '</div>',
        '</div>'
        ).compile();
    }
    
    return Ext.ux.PercentRenderer.template.apply({percent: percent});
};

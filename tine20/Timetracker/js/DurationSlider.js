/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/** NOTE: THIS CODE IS NOT USED YET!!! IT WAS THE FIRST IMPLEMENTATION WITH A SLIDER **/

Ext.ns('Tine.Timetracker');

Tine.Timetracker.DurationField = Ext.extend(Ext.form.Field,  {
    
    /**
     * @cfg {Number} defaultValue
     */
    defaultValue: 2,
    
    /**
     * @cfg {String/Object} autoCreate A DomHelper element spec
     */
    defaultAutoCreate: {tag: 'input', type: 'text', size: '30', autocomplete: 'off'},
    
    /**
     * @cfg {Number} textFieldWidth
     */
    textFieldWidth: 35,
    /**
     * @cfg {Number} spacerWdith
     */
    spacerWidth: 2,
    
    /**
     * @hide 
     * @method autoSize
     */
    autoSize: Ext.emptyFn,
    // private
    monitorTab : true,
    // private
    deferHeight : true,
    // private
    mimicing : false,

    // private
    onResize : function(w, h){
        Tine.Timetracker.DurationField.superclass.onResize.call(this, w, h);
        
        if(typeof w == 'number'){
            this.slider.setWidth(w-this.el.getWidth()-this.spacerWidth);
        }
        /*
        if(typeof w == 'number'){
            this.el.setWidth(this.adjustWidth('input', w - this.slider.getSize().width));
        }
        this.wrap.setWidth(this.el.getWidth()+this.slider.getSize().width);
        */
    },

    // private
    adjustSize : Ext.BoxComponent.prototype.adjustSize,

    // private
    getResizeEl : function(){
        return this.wrap;
    },

    // private
    getPositionEl : function(){
        return this.wrap;
    },

    // private
    alignErrorIcon : function(){
        if(this.wrap){
            this.errorIcon.alignTo(this.wrap, 'tl-tr', [2, 0]);
        }
    },
    
    initComponent: function() {
        Tine.Timetracker.DurationField.superclass.initComponent.call(this);
        
        this.slider = new Ext.Slider({
            minValue: 5,
            maxValue: 240,
            increment: 5,
            plugins: [
            /*
                new Ext.ux.SliderTip({
                    getText: function(slider){
                        return String.format(_('{0}'),  slider.getValue()/10).replace(/\ /, '&nbsp;');
                    }
                })
            */
            ]
            
            ,
            listeners: {
                scope: this,
                'drag': this.onSlide
            }
        });
        
        this.on('change', function() {
            this.slider.setValue(this.getValue()*10);
        }, this);
    },
    
    onSlide: function(slider) {
        this.setValue(slider.getValue()/10, true);
    },
    
    onRender: function (ct, position) {
        Tine.Timetracker.DurationField.superclass.onRender.call(this, ct, position);
        this.wrap = this.el.wrap({cls: "x-form-field-wrap"});
        this.el.setWidth(this.textFieldWidth);
        
        var sliderEl = Ext.DomHelper.insertFirst(this.wrap, {tag: 'div', style: {'float': 'left', 'margin-right': this.spacerWidth +'px'}}, true);
        this.slider.render(sliderEl);
        
    },
    
    setValue: function(value, omitSlider) {
        if (! value) {
            value = this.defaultValue;
        }
        Tine.Timetracker.DurationField.superclass.setValue.call(this, value);
        
        if (! omitSlider) {
            this.slider.setValue(value*10);
        }
    }
    
    /*
    getValue: function() {
        
    }
    */
});

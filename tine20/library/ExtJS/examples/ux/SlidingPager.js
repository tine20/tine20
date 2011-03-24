/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
Ext.ux.SlidingPager = Ext.extend(Object, {
    init : function(pbar){
        var idx = pbar.items.indexOf(pbar.inputItem);
        Ext.each(pbar.items.getRange(idx - 2, idx + 2), function(c){
            c.hide();
        });
        var slider = new Ext.Slider({
            width: 114,
            minValue: 1,
            maxValue: 1,
            plugins: new Ext.ux.SliderTip({
                getText : function(s){
                    return String.format('Page <b>{0}</b> of <b>{1}</b>', s.value, s.maxValue);
                }
            }),
            listeners: {
                changecomplete: function(s, v){
                    pbar.changePage(v);
                }
            }
        });
        pbar.insert(idx + 1, slider);
        pbar.on({
            change: function(pb, data){
                slider.setMaxValue(data.pages);
                slider.setValue(data.activePage);
            }
        });
    }
});
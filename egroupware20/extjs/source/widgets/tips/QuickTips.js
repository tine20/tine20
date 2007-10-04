/*
 * Ext JS Library 2.0 Alpha 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

Ext.QuickTips = function(){
    var tip, locks = [];
    return {
        init : function(){
            if(!tip){
                tip = new Ext.QuickTip({elements:'header,body'});
            }
        },

        /**
         * Enable this quick tip.
         */
        enable : function(){
            if(tip){
                locks.pop();
                if(locks.length < 1){
                    tip.enable();
                }
            }
        },

        /**
         * Disable this quick tip.
         */
        disable : function(){
            if(tip){
                tip.disable();
            }
            locks.push(1);
        },

        /**
         * Returns true if the quick tip is enabled, else false.
         */
        isEnabled : function(){
            return tip && !tip.disabled;
        },

        getQuickTip : function(){
            return tip;
        },

        register : function(){
            tip.register.apply(tip, arguments);
        },

        unregister : function(){
            tip.unregister.apply(tip, arguments);
        },

        tips :function(){
            tip.register.apply(tip, arguments);
        }
    }
}();
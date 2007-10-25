/*
 * Ext JS Library 2.0 Beta 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * @class Ext.QuickTips
 * The global QuickTips instance that reads quick tip configs from existing markup and manages quick tips. 
 * @singleton
 */
Ext.QuickTips = function(){
    var tip, locks = [];
    return {
        /**
         * Initialize the global QuickTips instance and prepare any quick tips.
         */
        init : function(){
            if(!tip){
                tip = new Ext.QuickTip({elements:'header,body'});
            }
        },

        /**
         * Enable quick tips globally.
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
         * Disable quick tips globally.
         */
        disable : function(){
            if(tip){
                tip.disable();
            }
            locks.push(1);
        },

        /**
         * Returns true if quick tips are enabled, else false.
         * @return {Boolean}
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
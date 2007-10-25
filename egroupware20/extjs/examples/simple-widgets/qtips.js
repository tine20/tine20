/*
 * Ext JS Library 2.0 Beta 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

Ext.onReady(function(){
    new Ext.ToolTip({
        target: 'tip1',
        html: 'This is a test msg',
        title: 'My Tip Title'
    });

    new Ext.ToolTip({
        target: 'tip2',
        html: 'This is a test msg',
        title: 'My Tip Title',
        autoHide: false,
        closable: true
    });

    Ext.QuickTips.init();

});
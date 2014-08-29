/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.ExceptionHandler
 * 
 * <p>Exception Handler for Calendar</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

Tine.Calendar.handleRequestException = function(exception, callback, callbackScope) {
    if (! exception.code && exception.responseText) {
        // we need to decode the exception first
        var response = Ext.util.JSON.decode(exception.responseText);
        exception = response.data;
    }
    
    var app = Tine.Tinebase.appMgr.get('Calendar');
    
    var defaults = {
        buttons: Ext.Msg.OK,
        icon: Ext.MessageBox.ERROR,
        fn: callback,
        scope: callbackScope,
        title: app.i18n._(exception.title),
        msg: app.i18n._(exception.message)
    };
    
    Tine.log.warn('Request exception :');
    Tine.log.warn(exception);

    switch(exception.code) {
        case 911: // Calendar_Exception_InvalidUrl
            Ext.MessageBox.show(defaults);
            break;
        // return false will the generic exceptionhandler handle the caught exception
        default:
            return false;
    }
    
    return true;
}

Tine.Tinebase.ExceptionHandlerRegistry.register('Calendar', Tine.Calendar.handleRequestException);
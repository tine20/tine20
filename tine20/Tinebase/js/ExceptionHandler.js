/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine', 'Tine.Tinebase');

/**
 * @namespace Tine.Tinebase
 * @class Tine.Tinebase.ExceptionHandler
 * @sigleton
 * 
 * IE NOTE: http://msdn.microsoft.com/library/default.asp?url=/library/en-us/dnscrpt/html/WebErrors2.asp
 *    "A common problem that bites many developers occurs when their onerror handler is not 
 *     called because they have script debugging enabled for Internet Explorer."
 *
 * central class for exception handling
 */
Tine.Tinebase.ExceptionHandler = function() {
    
    /**
     * handle window errors
     */
    var onWindowError = function() {
        
        var error = getNormalisedError.apply(this, arguments);
        
        var traceHtml = '<table>';
        for (p in error) {
            if (error.hasOwnProperty(p)) {
                traceHtml += '<tr><td><b>' + p + '</b></td><td>' + error[p] + '</td></tr>'
            }
        }
        traceHtml += '</table>'
        
        // check for spechial cases we don't want to handle
        if (traceHtml.match(/versioncheck/)) {
            return true;
        }
        // we don't wanna know fancy FF3.5 crom bugs
        if (traceHtml.match(/chrome/)) {
            return true;
        }
        
        // realy bad thing: fix exists only in close source version
        // http://www.extjs.com/forum/showthread.php?t=76860
        if (traceHtml.match(/swf\.setDataProvider/)) {
            return true;
        }
        
        var data = {
            message: 'js exception: ' + error.message,
            code:   error.number,
            traceHTML: traceHtml
        };
        
        var windowHeight = 400;
        if (Ext.getBody().getHeight(true) * 0.7 < windowHeight) {
            windowHeight = Ext.getBody().getHeight(true) * 0.7;
        }
        
        if (! Tine.Tinebase.exceptionDlg) {
            Tine.Tinebase.exceptionDlg = new Tine.Tinebase.ExceptionDialog({
                height: windowHeight,
                exception: data,
                listeners: {
                    close: function() {
                        Tine.Tinebase.exceptionDlg = null;
                    }
                }
            });
            Tine.Tinebase.exceptionDlg.show(Tine.Tinebase.exceptionDlg);
        }
        return true;
    };
    
    /**
     * @todo   make this working in safari
     * @return {string}
     */
    var getNormalisedError = function() {
        var error = {
            name       : 'unknown error',
            message    : 'unknown',
            number     : 'unknown',
            description: 'unknown',
            url        : 'unknown',
            line       : 'unknown'
        };
        
        // NOTE: Arguments is not always a real Array
        var args = [];
        for (var i=0; i<arguments.length; i++) {
            args[i] = arguments[i];
        }
        
        //var lines = ["The following JS error has occured:"];
        if (args[0] instanceof Error) { // Error object thrown in try...catch
            error.name        = args[0].name;
            error.message     = args[0].message;
            error.number      = args[0].number & 0xFFFF; //Apply binary arithmetic for IE number, firefox returns message string in element array element 0
            error.description = args[0].description;
            
        } else if ((args.length == 3) && (typeof(args[2]) == "number")) { // Check the signature for a match with an unhandled exception
            error.name    = 'catchable exception'
            error.message = args[0];
            error.url     = args[1];
            error.line    = args[2];
        } else {
            error.message     = "An unknown JS error has occured.";
            error.description = 'The following information may be useful:' + "\n";
            for (var x = 0; x < args.length; x++) {
                error.description += (Ext.encode(args[x]) + "\n");
            }
        }
        return error;
    };
    
    /**
     * generic request exception handling
     * 
     * NOTE: status codes 9xx are reserverd for applications and must not be handled here! 
     */
    var handleRequestException = function(exception) {
        switch(exception.code) {
            case 510:
                // if communication is lost, we can't create a nice ext window.
                alert(_('Connection lost, please check your network!'));
                break;
                
            // not authorised
            case 401:
                Ext.MessageBox.show({
                    title: _('Authorisation Required'), 
                    msg: _('Your session timed out. You need to login again.'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.WARNING,
                    fn: function() {
                        var redirect = (Tine.Tinebase.registry.get('redirectUrl'));
                        if (redirect && redirect != '') {
                            window.location = Tine.Tinebase.registry.get('redirectUrl');
                        } else {
                            window.location = window.location.href.replace(/#+.*/, '');
                        }
                    }
                });
                break;
            
            // insufficient rights
            case 403:
                Ext.MessageBox.show({
                    title: _('Insufficient Rights'), 
                    msg: _('Sorry, you are not permitted to perform this action'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.ERROR
                });
                break;
            
            // not found
            case 404:
                Ext.MessageBox.show({
                    title: _('Not Found'), 
                    msg: _('Sorry, your request could not be completed because the required data could not be found. In most cases this means that someone already deleted the data. Please refresh your current view.'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.ERROR
                });
                break;
            
            // concurrency conflict
            case 409:
                Ext.MessageBox.show({
                    title: _('Concurrent Updates'), 
                    msg: _('Someone else saved this record while you where editing the data. You need to reload and make your changes again.'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.WARNING
                });
                break;
            
            // generic failure -> notify developers
            default:
            
            var windowHeight = 400;
            if (Ext.getBody().getHeight(true) * 0.7 < windowHeight) {
                windowHeight = Ext.getBody().getHeight(true) * 0.7;
            }
            
            if (! Tine.Tinebase.exceptionDlg) {
                Tine.Tinebase.exceptionDlg = new Tine.Tinebase.ExceptionDialog({
                    height: windowHeight,
                    exception: exception,
                    listeners: {
                        close: function() {
                            Tine.Tinebase.exceptionDlg = null;
                        }
                    }
                });
                Tine.Tinebase.exceptionDlg.show();
            }
            break;
        }
    }
    
    // init window error handler
    window.onerror = !window.onerror ? 
        onWindowError :
        window.onerror.createSequence(onWindowError);
        
    return {
        handleRequestException: handleRequestException
    };
}();
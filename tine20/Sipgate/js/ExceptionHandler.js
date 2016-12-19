
Tine.Sipgate.handleRequestException = function(exception, callback, callbackScope) {
    if (! exception.code && exception.responseText) {
        // we need to decode the exception first
        var response = Ext.util.JSON.decode(exception.responseText);
        exception = response.data;
    }

    Tine.log.warn('Request exception :');
    Tine.log.warn(exception);
    
    var app = Tine.Tinebase.appMgr.get('Sipgate');
    
    switch(exception.code) {
        case 950: // Sipgate_Exception_Backend
            Ext.Msg.show({
               title:   app.i18n._hidden(exception.message),
               msg:     app.i18n._('A general backend error occurred. If this is a permanent error, please contact your administrator.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;
        case 951: // Sipgate_Exception_NoConnection
            Ext.Msg.show({
               title:   app.i18n._hidden(exception.message),
               msg:     app.i18n._('The application could not connect to the sipgate server. If this is a permanent error, please contact your administrator.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;
        case 952: // Sipgate_Exception_Authorization
            Ext.Msg.show({
               title:   app.i18n._hidden(exception.message),
               msg:     app.i18n._('The authentication data provided are not valid. Please configure or contact your administrator.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;
        case 953: // Sipgate_Exception_ResolveCredentials
            Ext.Msg.show({
               title:   app.i18n._hidden(exception.message),
               msg:     app.i18n._('The saved credentials and config data could not be resolved properly. Please reconfigure or contact your administrator.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;
        case 954: // Sipgate_Exception_MissingConfig
            Ext.Msg.show({
               title:   app.i18n._hidden(exception.message),
               msg:     app.i18n._('There are missing parameters to connect to sipgate. Please reconfigure or contact your administrator.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;
        case 629: // Tinebase_Exception_DuplicateRecord
            // handle special exception on account duplicate, dont break if not
            if(exception.hasOwnProperty('clientRecord') && exception.clientRecord.hasOwnProperty('accounttype')) {
                Ext.Msg.show({
               title:   app.i18n._hidden(exception.message),
               msg:     app.i18n._('The account you\'ve configured is already in use. Please contact your administrator or activate the account again if it\'s yours.'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;
            }
        default:
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception, callback, callbackScope);
                break;
    }
}
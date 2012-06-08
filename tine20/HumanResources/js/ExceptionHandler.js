/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.ExceptionHandler
 * 
 * <p>Exception Handler for HumanResources</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

Tine.HumanResources.handleRequestException = function(exception, callback, callbackScope) {
    if (! exception.code && exception.responseText) {
        // we need to decode the exception first
        var response = Ext.util.JSON.decode(exception.responseText);
        exception = response.data;
    }
    var defaults = {
        buttons: Ext.Msg.OK,
        icon: Ext.MessageBox.WARNING,
        fn: callback,
        scope: callbackScope
    };
        
    Tine.log.warn('Request exception :');
    Tine.log.warn(exception);
    
    var app = Tine.Tinebase.appMgr.get('HumanResources');
    switch(exception.code) {
        case 910:
            if(exception.nearestRecord) {
                callbackScope.editDialog.contractPicker.setValue(exception.nearestRecord);
                callbackScope.editDialog.contractPicker.selectedRecord = new Tine.HumanResources.Model.Contract(exception.nearestRecord); 
                callbackScope.editDialog.contractPicker.fireEvent('select');
                break;
            }
        case 911:
            Ext.MessageBox.show(Ext.apply(defaults, {
                    title: _('No contract could be found.'), 
                    msg: _('Please create a contract for this employee!'),
                    icon: Ext.MessageBox.ERROR
                }));
            break;
        default:
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
    }
}
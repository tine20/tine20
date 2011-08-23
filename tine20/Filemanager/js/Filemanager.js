/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.Application
 * @extends Tine.Tinebase.Application
 * 
 * @author Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Filemanager.Application = Ext.extend(Tine.Tinebase.Application, {

	hasMainScreen : true,

	/**
	 * Get translated application title of this application /test
	 * 
	 * @return {String}
	 */
	getTitle : function() {
		return this.i18n.gettext('Filemanager');
	}
});

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.MainScreen
 * @extends Tine.widgets.MainScreen
 * 
 * @author Martin Jatho <m.jatho@metaways.de>
 */
Tine.Filemanager.MainScreen = Ext.extend(Tine.widgets.MainScreen, {});


/**
 * generic exception handler for filemanager (used by folder and message backends and updateMessageCache)
 * 
 * @param {Tine.Exception} exception
 */
Tine.Filemanager.handleRequestException = function(exception) {
    Tine.log.warn('Request exception :');
    Tine.log.warn(exception);
    
    var app = Tine.Tinebase.appMgr.get('Filemanager');
    
    switch(exception.code) {
        case 901: 
            Ext.Msg.show({
               title:   app.i18n._('Failure on create folder/file'),
               msg:     app.i18n._('Item with this name allready exists!'),
               icon:    Ext.MessageBox.ERROR,
               buttons: Ext.Msg.OK
            });
            break;
  
        default:
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            break;
    }
};

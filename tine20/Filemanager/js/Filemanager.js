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
 * generic exception handler for filemanager (used by folder)
 * 
 * @param {Tine.Exception} exception
 */
Tine.Filemanager.handleRequestException = function(exception, request) {
    
    var app = Tine.Tinebase.appMgr.get('Filemanager');
    
    switch(exception.code) {
        case 901: 
            if(request) {
                
                var fileName = '';
                if(exception.existingnodesinfo) {
                    for(var i=0; i<exception.existingnodesinfo.length; i++) {
                        fileName += exception.existingnodesinfo[i].name + '<br />'; 
                    }                   
                }
                
                this.conflictConfirmWin = Tine.widgets.dialog.FileListDialog.openWindow({
                	modal: true,
                	allowCancel: false,
                	height: 180,
                	width: 300,
                	title: app.i18n._('Files already exists') + '. ' +app.i18n._('Do you want to replace the following file(s)?'),
                	text: fileName,
                	scope: this,
                	handler: function(button) {
	                	if (button == 'yes') {
	                		var params = request.params;
	                		params.forceOverwrite = true;
	                		params.method = request.method;
	                		
	                		if(params.method == 'Filemanager.copyNodes' || params.method == 'Filemanager.moveNodes' ) {
	                			Tine.Filemanager.fileRecordBackend.copyNodes(null, null, null, params);
	                		}
	                		else if (params.method == 'Filemanager.createNodes' ) {
	                			Tine.Filemanager.fileRecordBackend.createNodes(params, exception.uploadKeyArray, exception.addToGridStore);
	                		}
	                	}
	                	else {
	                		Ext.MessageBox.hide();
	                	}
	                }
                });
                
            }
            else {
                Ext.Msg.show({
                  title:   app.i18n._('Failure on create folder'),
                  msg:     app.i18n._('Item with this name already exists!'),
                  icon:    Ext.MessageBox.ERROR,
                  buttons: Ext.Msg.OK
               });
            }
            break;
            
//        case 403:    
            
        default:
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            break;
    }
};

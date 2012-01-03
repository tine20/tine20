/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.Application
 * @extends Tine.Tinebase.Application
 */
Tine.Filemanager.Application = Ext.extend(Tine.Tinebase.Application, {
	hasMainScreen : true,

	/**
	 * Get translated application title of this application
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
 */
Tine.Filemanager.MainScreen = Ext.extend(Tine.widgets.MainScreen, {});

/**
 * generic exception handler for filemanager
 * 
 * @param {Tine.Exception} exception
 */
Tine.Filemanager.handleRequestException = function(exception, request) {
    
    var app = Tine.Tinebase.appMgr.get('Filemanager');
    
    switch(exception.code) {
        case 901: 
            if (request) {
                Tine.log.debug('Tine.Filemanager.handleRequestException - request exception:');
                Tine.log.debug(exception);
                
                var existingFilenames = [];
                if (exception.existingnodesinfo) {
                    for (var i=0; i < exception.existingnodesinfo.length; i++) {
                        existingFilenames.push(exception.existingnodesinfo[i].name); 
                    }                   
                }
                
                this.conflictConfirmWin = Tine.widgets.dialog.FileListDialog.openWindow({
                	modal: true,
                	allowCancel: false,
                	height: 180,
                	width: 300,
                	title: app.i18n._('Files already exists') + '. ' + app.i18n._('Do you want to replace the following file(s)?'),
                	text: existingFilenames.join('<br />'),
                	scope: this,
                	handler: function(button) {
                        var params = request.params,
                            uploadKey = exception.uploadKeyArray;
                        params.method = request.method;
                        params.forceOverwrite = true;
                        
	                	if (button == 'no') {
                            Tine.log.debug('Tine.Filemanager.handleRequestException::' + params.method + ' -> only non-existant nodes.');
                            var nonExistantFilenames = [], filenameWithoutPath = null;
                            Ext.each(params.filenames, function(filename) {
                                filenameWithoutPath = filename.match(/[^\/]*$/);
                                if (filenameWithoutPath && existingFilenames.indexOf(filenameWithoutPath[0]) === -1) {
                                    nonExistantFilenames.push(filename);
                                }
                            });
                            params.filenames = nonExistantFilenames;
                            uploadKey = nonExistantFilenames;
	                	} else {
                            Tine.log.debug('Tine.Filemanager.handleRequestException::' + params.method + ' -> replace all existing nodes.');
                        }
                        
                        if (params.method == 'Filemanager.copyNodes' || params.method == 'Filemanager.moveNodes' ) {
                            Tine.Filemanager.fileRecordBackend.copyNodes(null, null, null, params);
                        } else if (params.method == 'Filemanager.createNodes' ) {
                            Tine.Filemanager.fileRecordBackend.createNodes(params, uploadKey, exception.addToGridStore);
                        }
	                }
                });
                
            } else {
                Ext.Msg.show({
                  title:   app.i18n._('Failure on create folder'),
                  msg:     app.i18n._('Item with this name already exists!'),
                  icon:    Ext.MessageBox.ERROR,
                  buttons: Ext.Msg.OK
               });
            }
            break;
            
        default:
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            break;
    }
};

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

/*
 * register additional action for genericpickergridpanel
 */
Tine.widgets.relation.MenuItemManager.register('Filemanager', 'Node', {
    text: 'Save locally',   // _('Save locally')
    iconCls: 'action_filemanager_save_all',
    requiredGrant: 'readGrant',
    actionType: 'download',
    allowMultiple: false,
    handler: function(action) {
        var node = action.grid.store.getAt(action.gridIndex).get('related_record');
        var downloadPath = node.path;
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Filemanager.downloadFile',
                requestType: 'HTTP',
                id: '',
                path: downloadPath
            }
        }).start();
    }
});

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.MainScreen
 * @extends Tine.widgets.MainScreen
 */
Tine.Filemanager.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Node'
});

/**
 * generic exception handler for filemanager
 * 
 * @param {Tine.Exception} exception
 */
Tine.Filemanager.handleRequestException = function(exception, request) {
    
    var app = Tine.Tinebase.appMgr.get('Filemanager'),
        existingFilenames = [],
        nonExistantFilenames = [],
        i,
        filenameWithoutPath = null;
    
    switch(exception.code) {
        // overwrite default 503 handling and add a link to the wiki
        case 503:
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING,
                title: _('Service Unavailable'), 
                msg: String.format(app.i18n._('The Filemanager is not configured correctly. Please refer to the {0}Tine 2.0 Admin FAQ{1} for configuration advice or contact your administrator.'),
                    '<a href="http://www.tine20.org/wiki/index.php/Admin_FAQ#The_message_.22filesdir_config_value_not_set.22_appears_in_the_logfile_and_I_can.27t_open_the_Filemanager" target="_blank">',
                    '</a>')
            });
            break;
            
        case 901: 
            if (request) {
                Tine.log.debug('Tine.Filemanager.handleRequestException - request exception:');
                Tine.log.debug(exception);
                
                if (exception.existingnodesinfo) {
                    for (i = 0; i < exception.existingnodesinfo.length; i++) {
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

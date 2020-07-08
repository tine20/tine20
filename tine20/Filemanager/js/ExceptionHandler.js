/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

/**
 * generic exception handler for filemanager
 * 
 * @namespace Tine.Filemanager
 * @param {Tine.Exception} exception
 * @param {Object} request
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
                title: i18n._('Service Unavailable'),
                msg: String.format(app.i18n._('The Filemanager is not configured correctly. Please refer to the {0}Tine 2.0 Admin FAQ{1} for configuration advice or contact your administrator.'),
                    '<a href="http://wiki.tine20.org/Admin_FAQ#The_message_.22filesdir_config_value_not_set.22_appears_in_the_logfile_and_I_can.27t_open_the_Filemanager" target="_blank">',
                    '</a>')
            });
            break;

        case 901:
            if (request) {
                Tine.log.debug('Tine.Filemanager.handleRequestException - request exception:');
                Tine.log.debug(exception);

                if (exception.existingnodesinfo) {
                    for (i = 0; i < exception.existingnodesinfo.length; i++) {
                        existingFilenames.push(Ext.util.Format.htmlEncode(exception.existingnodesinfo[i].name));
                    }
                }

                this.conflictConfirmWin = Tine.widgets.dialog.FileListDialog.openWindow({
                    modal: true,
                    allowCancel: false,
                    height: 180,
                    width: 300,
                    title: app.i18n._('File(s) already exists') + '. ' + app.i18n._('Do you want to replace the following file(s)?'),
                    text: existingFilenames.join('<br />'),
                    scope: this,
                    handler: function(button) {
                        var params = request.params,
                            uploadKey = exception.uploadKeyArray;
                        params.method = request.method;
                        params.forceOverwrite = true;

                        if (button == 'no') {
                            if (params.method == 'Filemanager.moveNodes') {
                                // reload grid
                                var app = Tine.Tinebase.appMgr.get('Filemanager');
                                app.getMainScreen().getCenterPanel().grid.getStore().reload();
                                // do nothing, other nodes has been moved already
                                return;
                            }
                            
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
                  title:   app.i18n._('An error occurred creating this folder'),
                  msg:     app.i18n._('Item with this name already exists!'),
                  icon:    Ext.MessageBox.ERROR,
                  buttons: Ext.Msg.OK
               });
            }
            break;
        case 902: // Filemanager_Exception_DestinationIsOwnChild
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.ERROR,
                title: app.i18n._(exception.title),
                msg: app.i18n._(exception.message)
            });
            break;
        case 903: // Filemanager_Exception_DestinationIsSameNode
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.INFO,
                title: app.i18n._(exception.title),
                msg: app.i18n._(exception.message)
            });
            break;
        default:
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            break;
    }
};
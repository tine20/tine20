/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.ImportEmlDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Message Compose Dialog</p>
 * <p>This dialog is for composing emails with recipients, body and attachments.
 * you can choose from which account you want to send the mail.</p>
 * <p>
 * TODO         make email note editable
 * </p>
 *
 * @author      Antonio Carlos da Silva  <antonio-carlos.silva@serpro.gov.br>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 * @constructor
 * Create a new ImportEmlDialog
 */
Tine.Expressomail.ImportEmlDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    windowNamePrefix: 'ImportEmlWindow_',
    appName: 'Expressomail',
    recordClass: Tine.Expressomail.Model.Message,
    recordProxy: Tine.Expressomail.messageBackend,
    loadRecord: false,
    evalGrants: false,
    bodyStyle: 'padding:0px',
    /**
     * overwrite update toolbars function (we don't have record grants)
     * @private
     */
    updateToolbars: Ext.emptyFn,
    //private
    initComponent: function() {
        Tine.Expressomail.ImportEmlDialog.superclass.initComponent.call(this);
    },
    /**
     * init buttons
     */
    initButtons: function() {
        this.fbar = [];

        this.action_cancel = new Ext.Action({
            text: this.app.i18n._('Cancel'),
            handler: this.onCancel,
            iconCls: 'action_cancel',
            disabled: false,
            scope: this
        });

        this.action_selectFile = new Ext.Action({
            text: this.app.i18n._('File to Import'),
            iconCls: 'action_import',
            scope: this,
            handler: this.onImportEML,
            plugins: [{
                    ptype: 'ux.browseplugin',
                    multiple: false,
                    dropElSelector: null
                }]
            
        });

        this.tbar = new Ext.Toolbar({
            defaults: {height: 50},
            items: [{
                    xtype: 'buttongroup',
                    columns: 3,
                    items: [
                        Ext.apply(new Ext.Button(this.action_cancel), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_selectFile), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        })
                    ]
                }]
        });

    },
            
    onImportEML: function(fileSelector, e) {
        var uploader = new Ext.ux.file.Upload({
            maxFileSize: 67108864, // 64MB
            fileSelector: fileSelector
        });

        uploader.on('uploadcomplete', function(x, fileRecord) {
            Ext.Ajax.request({
                params: {
                    method: 'Expressomail.importMessage',
                    accountId: this.account,
                    folderId: this.folderId,
                    file: fileRecord.get('tempFile').path
                },
                scope: this,
                success: function(_result, _request) {
                    Ext.MessageBox.hide();
                    this.onCancel();
                },
                failure: function(response, options) {
                    Ext.MessageBox.hide();
                    var responseText = Ext.util.JSON.decode(response.responseText);
                    if (responseText.data.code == 505) {
                        Ext.Msg.show({
                            title: i18n._('Error'),
                            msg: i18n._('Error import message eml!'),
                            icon: Ext.MessageBox.ERROR,
                            buttons: Ext.Msg.OK
                        });

                    } else {
                        // call default exception handler
                        var exception = responseText.data ? responseText.data : responseText;
                        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                    }
                }
            });
        }, this);

        Ext.MessageBox.wait(i18n._('Please wait'), i18n._('Loading'));
        var files = fileSelector.getFileList();
        Ext.each(files, function(file) {
            var fileRecord = uploader.upload(file);
        }, this);
    },
            
    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }

        this.window.setTitle(this.app.i18n._('Import msg(eml)'));
    },

    getFormItems: function() {

        this.southPanel = new Ext.Panel({
            region: 'south',
            layout: 'form',
            height: 1, // 150
            split: true,
            collapseMode: 'mini',
            header: false
        });

        return {
            border: false,
            frame: true,
            layout: 'border',
            items: [
                {
                    region: 'center',
                    layout: {
                        align: 'stretch', // Child items are stretched to full width
                        type: 'vbox'
                    }

                },
                this.southPanel]
        };
    }

});

/**
 * Expressomail Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Expressomail.ImportEmlDialog.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        width: 240,
        height: 65,
        resizable: false,
        name: Tine.Expressomail.ImportEmlDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Expressomail.ImportEmlDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};

/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

/**
 * Simple Import Dialog
 *
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.ImportDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * @constructor
 * @param       {Object} config The configuration options.
 * 
 * TODO add app grid to show results when dry run is selected
 */
Tine.Calendar.ImportDialog = Ext.extend(Tine.widgets.dialog.ImportDialog, {
    
    appName: 'Calendar',
    modelName: 'Event',
    
    /**
     * init import wizard
     */
    initComponent: function() {
        Tine.log.debug('Tine.Calendar.ImportDialog::initComponent');
        Tine.log.debug(this);

        Tine.Calendar.ImportDialog.superclass.initComponent.call(this);
    },

    getItems: function() {
        return [this.getPanel()];
    },
    
    /**
     * do import request
     * 
     * @param {Function} callback
     * @param {Object}   importOptions
     */
    doImport: function(callback, importOptions, clientRecordData) {
        var targetContainer = this.containerField.getValue() || this.containerCombo.getValue();
        var type = this.typeCombo.getValue();
        
        var params = {
            importOptions: Ext.apply({
                container_id: targetContainer,
                sourceType: this.typeCombo.getValue(),
                importFileByScheduler: (this.typeCombo.getValue() == 'remote_caldav') ? true : false
            }, importOptions || {})
        };

        if (this.typeCombo.getValue() == 'remote_caldav') {
            params.importOptions = Ext.apply({
                password: this.remotePassword.getValue(),
                username: this.remoteUsername.getValue()
            }, params.importOptions);
        }

        if (type == 'upload') {
            params = Ext.apply(params, {
                clientRecordData: clientRecordData,
                method: this.appName + '.import' + this.recordClass.getMeta('modelName')  + 's',
                tempFileId: this.uploadButton.getTempFileId(),
                definitionId: this.definitionCombo.getValue()
            });
        } else {
            params = Ext.apply(params, {
                method: this.appName + '.importRemote' + this.recordClass.getMeta('modelName')  + 's',
                remoteUrl: this.remoteLocation.getValue(),
                interval: this.ttlCombo.getValue()
            });
        }
       
        Ext.Ajax.request({
            scope: this,
            timeout: 1800000, // 30 minutes
            callback: this.onImportResponse.createDelegate(this, [callback], true),
            params: params
        });
    },
    
    /**
     * called when import request sends response
     * 
     * @param {Object}   request
     * @param {Boolean}  success
     * @param {Object}   response
     * @param {Function} callback
     */
    onImportResponse: function(request, success, response, callback) {
        var decoded = Ext.util.JSON.decode(response.responseText);
        
        Tine.log.debug('Tine.widgets.dialog.SimpleImportDialog::onImportResponse server response');
        Tine.log.debug(decoded);
        
        this.lastImportResponse = decoded;

        var that = this;
        
        if (success) {
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.INFO,
                fn: callback,
                scope: that,
                title: that.app.i18n._('Import Definition Success!'),
                msg: that.app.i18n._('The Ical Import definition has been created successfully! Please wait some minutes to get the events synced by the cronjob.')
            });
            
            var wp = this.app.mainScreen.getWestPanel(),
                tp = wp.getContainerTreePanel(),
                state = wp.getState();
                
            tp.getLoader().load(tp.getRootNode());
            wp.applyState(state);
            
        } else {
            Tine.Tinebase.ExceptionHandler.handleRequestException(response, callback, that);
        }
    },
    
    /**
     * Returns a panel with a upload field and descriptions
     * 
     * @returns {Object}
     */
    getUploadPanel: function () {
        return {
            xtype: 'panel',
            baseCls: 'ux-subformpanel',
            id: 'uploadPanel',
            hidden: true,
            title: _('Choose Import File'),
            height: 100,
            items: [{
                xtype: 'label',
                html: '<p>' + _('Please choose the file that contains the records you want to add to Tine 2.0') + '</p><br />'
            }, {
                xtype: 'tw.uploadbutton',
                ref: '../../uploadButton',
                text: String.format(_('Select file containing your {0}'), this.recordClass.getRecordsName()),
                handler: this.onFileReady,
                allowedTypes: this.allowedFileExtensions,
                scope: this
            }]
        };
    },
    
    /**
     * Returns a panel with a text field for a remote location and a description
     * 
     * @returns {Object}
     */    
    getRemotePanel: function () {
        var ttl = [
            ['once', this.app.i18n._('once')],
            ['hourly', this.app.i18n._('hourly')],
            ['daily', this.app.i18n._('daily')],
            ['weekly', this.app.i18n._('weekly')]
        ];

        var ttlStore = new Ext.data.ArrayStore({
            fields: ['ttl_id', 'ttl'],
            data: ttl
        });
        
        return {
            xtype: 'panel',
            baseCls: 'ux-subformpanel',
            id: 'remotePanel',
            hidden: false,
            title: this.app.i18n._('Choose Remote Location'),
            //height: 230,
            items: [{
                xtype: 'label',
                html: '<p>' + this.app.i18n._('Please choose a remote location you want to add to Tine 2.0') + '</p><br />'
            }, {
                ref: '../../remoteLocation',
                xtype: 'textfield',
                scope: this,
                enableKeyEvents: true,
                width: 400,
                listeners: {
                    scope: this,
                    keyup: function() {
                        this.manageButtons();
                    }
                }
            }, {
                xtype: 'label',
                html: '<p><br />' + this.app.i18n._('Username (CalDAV only)') + '</p><br />'
            }, {
                ref: '../../remoteUsername',
                xtype: 'textfield',
                scope: this,
                disabled: true,
                enableKeyEvents: true,
                width: 400,
                listeners: {
                    scope: this,
                    keyup: function() {
                        this.manageButtons();
                    }
                }
            }, {
                xtype: 'label',
                html: '<p><br />' + this.app.i18n._('Password (CalDAV only)') + '</p><br />'
            }, {
                ref: '../../remotePassword',
                xtype: 'textfield',
                inputType: 'password',
                scope: this,
                disabled: true,
                enableKeyEvents: true,
                width: 400,
                listeners: {
                    scope: this,
                    keyup: function() {
                        this.manageButtons();
                    }
                }
            }, {
                xtype: 'label',
                html: '<p><br />' + this.app.i18n._('Refresh time') + '</p><br />'
            }, {
                xtype: 'combo',
                mode: 'local',
                ref: '../../ttlCombo',
                value: 'once',
                scope: this,
                width: 400,
                listeners: {
                    scope: this,
                    'select': function() {
                        this.manageButtons();
                    }
                },
                editable: false,
                allowblank: false,
                valueField: 'ttl_id',
                displayField: 'ttl',
                store: ttlStore
            }]
        };
    },
    
    getImportOptionsPanel: function () {
        if (this.importOptionsPanel) {
            return this.importOptionsPanel;
        }
        
        return {
            xtype: 'panel',
            ref: '../../importOptionsPanel',
            baseCls: 'ux-subformpanel',
            title: this.app.i18n._('General Settings'),
            height: 100,
            width: 400,
            items: [{
                xtype: 'label',
                html: '<p>' + this.app.i18n._('Container name / New or existing if it already exists you need permissions to add to.') + '<br /><br /></p>'
            }, {
                xtype: 'panel',
                heigth: 150,
                layout: 'hbox',
                items: [{
                    id: this.app.appName + 'ContainerName',
                    xtype: 'textfield',
                    ref: '../../../containerField',
                    enableKeyEvents: true,
                    listeners: {
                        scope: this,
                        keyup: function() {
                            this.manageButtons();
                        }
                    },
                    flex: 1
                }, {
                    xtype: 'label',
                    html: ' - ' + this.app.i18n._('or') + ' - ',
                    style: {
                        'text-align': 'center'
                    },
                    width: 40
                }, {
                    xtype: 'panel',
                    flex: 1,
                    height: 20,
                    width: 200,
                    items: [new Tine.widgets.container.selectionComboBox({
                        id: this.app.appName + 'EditDialogContainerSelector',
                        ref: '../../../../containerCombo',
                        stateful: false,
                        containerName: this.recordClass.getContainerName(),
                        containersName: this.recordClass.getContainersName(),
                        appName: this.appName,
                        value: this.defaultImportContainer,
                        requiredGrant: false
                    })]
                }]
            }]
        };
    },
    
    getDefinitionPanel: function () {
        if (this.definitionPanel) {
            return this.definitionPanel;
        }
        
        var def = this.selectedDefinition,
            description = def ? def.get('description') : '',
            options = def ? def.get('plugin_options') : null,
            example = options && options.example ? options.example : '';
    
        return {
            xtype: 'panel',
            ref: '../../definitionPanel',
            id: 'definitionPanel',
            hidden: true,
            baseCls: 'ux-subformpanel',
            title: this.app.i18n._('What should the file you upload look like?'),
            flex: 1,
            items: [
            {
                xtype: 'label',
                html: '<p>' + this.app.i18n._('Tine 2.0 does not understand all kind of files you might want to upload. You will have to manually adjust your file so Tine 2.0 can handle it.') + '</p><br />'
            }, {
//                xtype: 'label',
//                html: '<p>' + this.app.i18n._('Following you find a list of all supported import formats and a sample file, how Tine 2.0 expects your file to look like.') + '</p><br />'
//            }, {
                xtype: 'label',
                html: '<p>' + this.app.i18n._('Please select the import format of the file you want to upload') + '<br /><br /></p>'
            }, {
                xtype: 'combo',
                ref: '../../definitionCombo',
                store: this.definitionsStore,
                displayField:'label',
                valueField:'id',
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                allowBlank: false,
                forceSelection: true,
                width: 400,
                value: this.selectedDefinition ? this.selectedDefinition.id : null,
                listeners: {
                    scope: this,
                    'select': this.onDefinitionSelect
                }
            }, {
                xtype: 'label',
                ref: '../../exampleLink',
                html: example ? ('<p><a href="' + example + '">' + this.app.i18n._('Download example file') + '</a></p>') : '<p>&nbsp;</p>'
            }, {
                xtype: 'displayfield',
                ref: '../../definitionDescription',
                height: 70,
                value: description,
                cls: 'x-ux-display-background-border',
                style: 'padding-left: 5px;'
            }]
        };
    },
    
    /**
     * returns the file panel of this wizard (step 1)
     */
    getPanel: function() {
        var def = this.selectedDefinition,
            description = def ? def.get('description') : '',
            options = def ? def.get('plugin_options') : null,
            example = options && options.example ? options.example : '';
        
        var types = [
            ['remote_ics', this.app.i18n._('Remote / ICS')],
            ['remote_caldav', _('Remote / CalDAV (BETA)')],
            ['upload', this.app.i18n._('Upload')]
        ]
        
        var typeStore = new Ext.data.ArrayStore({
            fields: [
                'type_id',
                'type_value'
            ],
            data: types,
            disabled: false
        });

        return {
            title: this.app.i18n._('Choose File and Format'),
            layout: 'vbox',
            border: false,
            xtype: 'ux.displaypanel',
            frame: true,
            ref: '../filePanel',
            items: [{
                xtype: 'panel',
                baseCls: 'ux-subformpanel',
                title: this.app.i18n._('Select type of source'),
                height: 100,
                items: [{
                        xtype: 'label',
                        html: '<p>' + this.app.i18n._('Please select the type of source you want to add to Tine 2.0') + '</p><br />'
                }, {
                    xtype: 'combo',
                    mode: 'local',
                    ref: '../../typeCombo',
                    width: 400,
                    listeners:{
                        scope: this,
                        'select': function (combo) {
                            if (combo.getValue() == 'upload') {
                                Ext.getCmp('uploadPanel').show();
                                Ext.getCmp('definitionPanel').show();
                                Ext.getCmp('remotePanel').hide();
                            } else if (combo.getValue() == 'remote_ics' || combo.getValue() == 'remote_caldav') {
                                Ext.getCmp('uploadPanel').hide();
                                Ext.getCmp('definitionPanel').hide();
                                Ext.getCmp('remotePanel').show();
                                if (combo.getValue() == 'remote_caldav') {
                                    this.remoteUsername.enable();
                                    this.remotePassword.enable();
                                    this.remoteLocation.emptyText = 'http://example/calendars';
                                } else {
                                    this.remoteUsername.disable();
                                    this.remotePassword.disable();
                                    this.remoteLocation.emptyText = 'http://example.ics';
                                }
                                this.remoteLocation.applyEmptyText();
                                this.remoteLocation.reset();
                            }
                            
                            this.doLayout();
                            this.manageButtons();
                        },
                        'render': function (combo) {
                            combo.setValue('upload');
                            Ext.getCmp('uploadPanel').show();
                            Ext.getCmp('definitionPanel').show();
                            Ext.getCmp('remotePanel').hide();
                        }
                    },
                    scope: this,
                    valueField: 'type_id',
                    displayField: 'type_value',
                    store: typeStore
                }]
            },
            this.getUploadPanel(),
            this.getRemotePanel(),
            this.getImportOptionsPanel(),
            this.getDefinitionPanel()],

            /**
            * finish button handler for this panel
            */
            onFinishButton: (function() {
                if (! this.importMask) {
                    this.importMask = new Ext.LoadMask(this.getEl(), {msg: String.format(_('Importing {0}'), this.recordClass.getRecordsName())});
                }
                this.importMask.show();

                // collect client data
                var clientRecordData = [];
                var importOptions = {};

                this.doImport(function(request, success, response) {
                    this.importMask.hide();

                    this.fireEvent('finish', this, this.layout.activeItem);

                    if (Ext.isArray(response.exceptions) && response.exceptions.length > 0) {
                        this.backButton.setDisabled(true);
                        this.finishButton.setHandler(function() {this.window.close()}, this);
                    } else {
                        this.window.close();
                    }
                }, importOptions, clientRecordData);
            }).createDelegate(this),

            finishIsAllowed: (function() {
                var credentialsCheck = false;

                if (this.typeCombo.getValue() == 'remote_caldav') {
                    if (this.remoteUsername.getValue() != '' && this.remotePassword.getValue() != '') {
                        credentialsCheck = true;
                    }
                } else if (this.typeCombo.getValue() == 'remote_ics') {
                    credentialsCheck = true;
                }

                return (
                    ((this.typeCombo && (this.typeCombo.getValue() == 'remote_ics' || this.typeCombo.getValue() == 'remote_caldav'))
                    && (this.remoteLocation && this.remoteLocation.getValue())
                    && (this.ttlCombo && (this.ttlCombo.getValue() || this.ttlCombo.getValue() === 0))))
                    && credentialsCheck
                    || ((this.typeCombo && (this.typeCombo.getValue() == 'upload'))
                    && (this.definitionCombo && this.definitionCombo.getValue())
                    && (this.uploadButton && this.uploadButton.upload))
                    && ((this.containerField && this.containerField.getValue()) || (this.containerCombo && this.containerCombo.getValue())
                );

            }).createDelegate(this)
        };
    }
});

/**
 * Create new import window
 */
Tine.Calendar.ImportDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 600,
        name: Tine.Calendar.ImportDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Calendar.ImportDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

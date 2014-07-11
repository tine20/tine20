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
    /**
     * init import wizard
     */
    initComponent: function() {
        Tine.log.debug('Tine.widgets.dialog.SimpleImportDialog::initComponent this');
        Tine.log.debug(this);

        this.app = Tine.Tinebase.appMgr.get(this.appName);
        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.appName, this.modelName);

        this.allowedFileExtensions = [];

        // init definitions
        this.definitionsStore = new Ext.data.JsonStore({
            fields: Tine.Tinebase.Model.ImportExportDefinition,
            root: 'results',
            totalProperty: 'totalcount',
            remoteSort: false
        });

        if (Tine[this.appName].registry.get('importDefinitions')) {
            Ext.each(Tine[this.appName].registry.get('importDefinitions').results, function(defData) {
                var options = defData.plugin_options,
                    extension = options ? options.extension : null;
                
                defData.label = this.app.i18n._hidden(options && options.label ? options.label : defData.name);
                
                if (this.allowedFileExtensions.indexOf(extension) == -1) {
                    this.allowedFileExtensions = this.allowedFileExtensions.concat(extension);
                }
                
                this.definitionsStore.addSorted(new Tine.Tinebase.Model.ImportExportDefinition(defData, defData.id));
            }, this);
            this.definitionsStore.sort('label');
        }
        if (! this.selectedDefinition && Tine[this.appName].registry.get('defaultImportDefinition')) {
            this.selectedDefinition = this.definitionsStore.getById(Tine[this.appName].registry.get('defaultImportDefinition').id);
        }

        this.items = [
            this.getPanel()
        ];
        
        Tine.widgets.dialog.ImportDialog.superclass.initComponent.call(this);
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
        
        var method = null;
        if (type == 'upload') {
            method = this.appName + '.import' + this.recordClass.getMeta('modelName')  + 's';
        } else {
            this.appName + '.remoteImport' + this.recordClass.getMeta('modelName')  + 's'
        }
       
        Ext.Ajax.request({
            scope: this,
            timeout: 1800000, // 30 minutes
            callback: this.onImportResponse.createDelegate(this, [callback], true),
            params: {
                method: method,
                tempFileId: this.uploadButton.getTempFileId() || null,
                type: type,
                remoteUrl: this.remoteLocation.getValue() || null,
                definitionId: this.definitionCombo.getValue() || null,
                importOptions: Ext.apply({
                    container_id: targetContainer
                }, importOptions || {}),
                clientRecordData: clientRecordData
            }
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
        response = Ext.util.JSON.decode(response.responseText);
        
        Tine.log.debug('Tine.widgets.dialog.SimpleImportDialog::onImportResponse server response');
        Tine.log.debug(response);
        
        this.lastImportResponse = response;
        
        // finlay apply callback
        if (Ext.isFunction(callback)) { 
           callback.call(this, request, success, response);
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
                html: '<p>' + _('Please choose the file that contains the records you want to add to Tine 2.0').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
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
            [0, _('Once')],
            [1, _('hourly')],
            [2, _('daily')],
            [3, _('weekly')]
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
            title: _('Choose Remote Location'),
            height: 150,
            items: [{
                xtype: 'label',
                html: '<p>' + _('Please choose a remote location you want to add to Tine 2.0').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
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
                html: '<p>' + _('Refresh time').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
            }, {
                xtype: 'combo',
                mode: 'local',
                ref: '../../ttlCombo',
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
            title: _('General Settings'),
            height: 100,
            items: [{
                xtype: 'label',
                html: '<p>' + _('Container name / New or existing if it already exists you need permissions to add to.') + '</p>'
            }, {
                xtype: 'panel',
                heigth: 150,
                layout: 'hbox',
                items: [{
                    id: this.app.appName + 'ContainerName',
                    xtype: 'textfield',
                    width: 400,
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
                    html: ' - ' + _('or') + ' - ',
                    style: {
                        'text-align': 'center'
                    },
                    width: 40
                }, {
                    xtype: 'panel',
                    flex: 1,
                    height: 20,
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
            title: _('What should the file you upload look like?'),
            flex: 1,
            items: [
            {
                xtype: 'label',
                html: '<p>' + _('Tine 2.0 does not understand all kind of files you might want to upload. You will have to manually adjust your file so Tine 2.0 can handle it.').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
            }, {
                xtype: 'label',
                html: '<p>' + _('Following you find a list of all supported import formats and a sample file, how Tine 2.0 expects your file to look like.').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
            }, {
                xtype: 'label',
                html: '<p>' + _('Please select the import format of the file you want to upload').replace(/Tine 2\.0/g, Tine.title) + '</p>'
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
                html: example ? ('<p><a href="' + example + '">' + _('Download example file') + '</a></p>') : '<p>&nbsp;</p>'
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
            ['remote_ics', _('Remote / ICS')],
            ['remote_caldav', _('Remote / CALDav')],
            ['upload', _('Upload')]
        ]
        
        var typeStore = new Ext.data.ArrayStore({
            fields: [
                'type_id',
                'type_value'
            ],
            data: types
        });

        return {
            title: _('Choose File and Format'),
            layout: 'vbox',
            border: false,
            xtype: 'ux.displaypanel',
            frame: true,
            ref: '../filePanel',
            items: [{
                xtype: 'panel',
                baseCls: 'ux-subformpanel',
                title: _('Select type of source'),
                height: 100,
                items: [{
                        xtype: 'label',
                        html: '<p>' + _('Please select the type of source you want to add to Tine 2.0') + '</p><br />'
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
                            }
                            
                            this.doLayout();
                            this.manageButtons();
                        },
                        'render': function (combo) {
                            /**
                            * @todo enable and allow remotes
                            *  ~mspahn
                            */
                            combo.setValue('upload');
                            combo.setDisabled(true);
                            this.containerField.setDisabled(true);
                           
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

            /**
             * @todo fix later
             */
            finishIsAllowed: (function() {
                return (
                    ((this.typeCombo && (this.typeCombo.getValue() == 'remote_ics' || this.typeCombo.getValue() == 'remote_caldav'))
                    && (this.remoteLocation && this.remoteLocation.getValue())
                    && (this.ttlCombo && (this.ttlCombo.getValue() || this.ttlCombo.getValue() === 0))))
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

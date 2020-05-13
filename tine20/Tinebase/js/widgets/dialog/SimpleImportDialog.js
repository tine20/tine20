/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.dialog');

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
Tine.widgets.dialog.SimpleImportDialog = Ext.extend(Tine.widgets.dialog.ImportDialog, {
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
            Ext.each(Tine.widgets.importAction.getImports(this.recordClass), function(defData) {
                var options = defData.plugin_options_json,
                    extension = options ? options.extension : null;
                
                defData.label = this.app.i18n._hidden(options && options.label ? options.label : defData.name);
                
                if (this.allowedFileExtensions.indexOf(extension) == -1) {
                    this.allowedFileExtensions = this.allowedFileExtensions.concat(extension);
                }
                
                this.definitionsStore.addSorted(new Tine.Tinebase.Model.ImportExportDefinition(defData, defData.id));
            }, this);
            this.definitionsStore.sort('label');
        }
        if (! this.selectedDefinition) {
            var defaultConfig = Tine[this.appName].registry.get('defaultImportDefinition'),
                defaultExists = defaultConfig && this.definitionsStore.getById(defaultConfig.id);

            this.selectedDefinition = defaultExists ?
                this.definitionsStore.getById(defaultConfig.id) :
                this.definitionsStore.getAt(0);
        }

        this.items = [this.getFilePanel()];

        Tine.widgets.dialog.ImportDialog.superclass.initComponent.call(this);
    },

    /**
     * do import request
     * 
     * @param {Function} callback
     * @param {Object}   importOptions
     */
    doImport: function(callback, importOptions, clientRecordData) {
        Ext.Ajax.request({
            scope: this,
            timeout: 1800000, // 30 minutes
            callback: this.onImportResponse.createDelegate(this, [callback], true),
            params: {
                method: this.appName + '.import' + this.recordClass.getMeta('modelName')  + 's',
                tempFileId: this.uploadButton.getTempFileId(),
                definitionId: this.definitionCombo.getValue(),
                importOptions: Ext.apply({
                    container_id: this.containerCombo.getValue()
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
    
    getTypeSelection: function() {
        
    },
    
    /**
     * returns the file panel of this wizard (step 1)
     */
    getFilePanel: function() {
        if (this.filePanel) {
            return this.filePanel;
        }
        
        var def = this.selectedDefinition,
            description = def ? this.app.i18n._hidden(def.get('description')) : '',
            options = def ? def.get('plugin_options_json') : null,
            example = options && options.example ? options.example : '';
            
        return {
            title: i18n._('Choose File and Format'),
            layout: 'vbox',
            border: false,
            xtype: 'ux.displaypanel',
            frame: true,
            ref: '../filePanel',
            items: [{
                xtype: 'panel',
                baseCls: 'ux-subformpanel',
                title: i18n._('Choose Import File'),
                height: 100,
                items: [{
                    xtype: 'label',
                    html: '<p>' + i18n._('Please choose the file that contains the records you want to add to Tine 2.0').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
                }, {
                    xtype: 'tw.uploadbutton',
                    ref: '../../uploadButton',
                    text: String.format(i18n._('Select file containing your {0}'), this.recordClass.getRecordsName()),
                    handler: this.onFileReady,
                    allowedTypes: this.allowedFileExtensions,
                    scope: this
                }]
            },{
                xtype: 'panel',
                baseCls: 'ux-subformpanel',
                title: i18n._('Set Import Options'),
                height: 100,
                items: [{
                    xtype: 'label',
                    html: '<p>' + String.format(i18n._('Select {0} to add you {1} to:'), this.recordClass.getContainerName(), this.recordClass.getRecordsName()) + '</p>'
                }, new Tine.widgets.container.SelectionComboBox({
                    id: this.app.appName + 'EditDialogContainerSelector',
                    width: 300,
                    ref: '../../containerCombo',
                    stateful: false,
                    recordClass: this.recordClass,
                    containerName: this.recordClass.getContainerName(),
                    containersName: this.recordClass.getContainersName(),
                    appName: this.appName,
                    value: this.defaultImportContainer,
                    requiredGrant: false
                })]
            },
            {
                xtype: 'panel',
                baseCls: 'ux-subformpanel',
                title: i18n._('What should the file you upload look like?'),
                flex: 1,
                items: [
                {
                    xtype: 'label',
                    html: '<p>' + i18n._('Tine 2.0 does not understand all kind of files you might want to upload. You will have to manually adjust your file so Tine 2.0 can handle it.').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
                }, {
                    xtype: 'label',
                    html: '<p>' + i18n._('Following you find a list of all supported import formats and a sample file, how Tine 2.0 expects your file to look like.').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
                }, {
                    xtype: 'label',
                    html: '<p>' + i18n._('Please select the import format of the file you want to upload').replace(/Tine 2\.0/g, Tine.title) + '</p>'
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
                    html: example ? ('<p><a href="' + example + '">' + i18n._('Download example file') + '</a></p>') : '<p>&nbsp;</p>'
                }, {
                    xtype: 'displayfield',
                    ref: '../../definitionDescription',
                    height: 70,
                    value: description,
                    cls: 'x-ux-display-background-border',
                    style: 'padding-left: 5px;'
                }]
            }],
            
            /**
            * finish button handler for this panel
            */
            onFinishButton: (function() {
                if (! this.importMask) {
                    this.importMask = new Ext.LoadMask(this.getEl(), {msg: String.format(i18n._('Importing {0}'), this.recordClass.getRecordsName())});
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
                return this.definitionCombo && this.definitionCombo.getValue() && this.uploadButton && this.uploadButton.fileRecord && this.containerCombo && this.containerCombo.getValue();
            }).createDelegate(this)
        };
    }
});

/**
 * Create new import window
 */
Tine.widgets.dialog.SimpleImportDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 600,
        name: Tine.widgets.dialog.SimpleImportDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.SimpleImportDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

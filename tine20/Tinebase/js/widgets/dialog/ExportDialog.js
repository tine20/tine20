/**
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

import '../../Model/ImportExportDefinition';

/**
 * Generic 'Export' dialog
 *
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.ExportDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * @constructor
 * @param       {Object} config The configuration options
 * 
 * TODO         add template for def combo (shows description, format?, ...)
 *              -> add panel with it
 * 
 */
Tine.widgets.dialog.ExportDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @cfg {String} appName
     */
    appName: null,
    /**
     * @cfg {Number} height
     */
    height: 600,
    /**
     * @cfg {Number} width
     */
    width: 800,

    // private
    windowNamePrefix: 'ExportWindow_',
    checkUnsavedChanges: false,
    loadMask: false,
    tbarItems: [],
    evalGrants: false,
    sendRequest: true,
    mode: 'local',
    
    //private
    initComponent: function(){
        this.app = Tine.Tinebase.appMgr.get(this.appName);
        
        this.recordClass = Tine.Tinebase.Model.ExportJob;
        this.saveAndCloseButtonText = i18n._('Export');
        

        this.definitionsStore = new Ext.data.JsonStore({
            fields: Tine.Tinebase.Model.ImportExportDefinition,
            root: 'results',
            totalProperty: 'totalcount',
            idProperty: 'id',
            remoteSort: false
        });

        var recordClass = Tine.Tinebase.data.RecordMgr.get(this.record.get('model')),
            scope = this.record.get('scope'),
            exportDefinitions = Tine.widgets.exportAction.getExports(recordClass, null, scope);

        Ext.each(exportDefinitions, function(defData) {
            defData.label = this.app.i18n._hidden(defData.label ? defData.label : defData.name);
            this.definitionsStore.addSorted(new Tine.Tinebase.Model.ImportExportDefinition(defData, defData.id));
        }, this);
        this.definitionsStore.sort('label');
        
        this.record.set('returnFileLocation', true);
        
        Tine.widgets.dialog.ExportDialog.superclass.initComponent.call(this);
    },
    
    /**
     * returns dialog
     */
    getFormItems: function() {
        return {
            bodyStyle: 'padding:5px;',
            buttonAlign: 'right',
            labelAlign: 'top',
            border: false,
            layout: 'form',
            ref: 'formPanel',
            defaults: {
                anchor: '100%'
            },
            items: [{
                xtype: 'combo',
                fieldLabel: i18n._('Export'),
                name:'definitionId',
                store: this.definitionsStore,
                displayField:'label',
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                allowBlank: false,
                forceSelection: true,
                emptyText: i18n._('Select Export Definition ...'),
                valueField: 'id',
                checkState: this.checkDefinitionState.createDelegate(this)
            }, {
                xtype: 'displayfield',
                fieldLabel: i18n._('Description'),
                ref: '../definitionDescription',
                height: 70,
                cls: 'x-ux-display-background-border',
                style: 'padding-left: 5px;'
            }]
        };
    },

    checkDefinitionState: function() {
        const definitionId = this.record.get('definitionId');
        const definition = this.definitionsStore.getById(definitionId);
        
        if (definitionId && definitionId !== this.definitionId) {
            this.definitionDescription.setValue(
                this.app.i18n._hidden(definition.get('description')));
            
            this.formPanel.items.each((item) => {
                if (item.definitionId && item.definitionId === this.definitionId) {
                    item.ownerCt.remove(item);
                }
            });

            const optionsDefinitions = _.get(definition, 'data.plugin_options_definition', {});
            const options =  _.get(definition, 'data.plugin_options_json', {});

            _.each(optionsDefinitions, (fieldDefinition, fieldName) => {
                _.assign(fieldDefinition, {
                    appName: this.appName,
                    fieldName: fieldName,
                });
                
                const config = {
                    value: _.get(options, 'fieldName'),
                    definitionId: definitionId
                };
                
                const field = Ext.create(Tine.widgets.form.FieldManager.getByFieldDefinition(fieldDefinition,
                    Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,{
                        value: _.get(options, 'fieldName'),
                        definitionId: definitionId
                    }
                ));
                
                this.formPanel.add(field);
                this.relayEvents(field, ['change', 'select']);
            });
            
            this.doLayout();
            this.definitionId = definitionId;
        }
    },

    onRecordUpdate: function() {
        if (this.definitionId) {
            this.formPanel.items.each((item) => {
                if (item.definitionId && item.definitionId === this.definitionId) {
                    // this.record is a exportJob not an definition! :-(
                    _.set(this.record, 'data.options.' +item.name, item.getValue());
                }
            });
        }
        
        return Tine.widgets.dialog.ExportDialog.superclass.onRecordUpdate.call(this);
    },
    
    /**
     * apply changes handler
     */
    onApplyChanges: function(closeWindow) {
        var form = this.getForm();
        if (form.isValid()) {
            this.onRecordUpdate();

            const exportMask = new Ext.LoadMask(this.getEl(), {msg: i18n._('Exporting...')});
            exportMask.show();

            Tine.widgets.exportAction.downloadExport(this.record).then((raw) => {
                const response = JSON.parse(raw.responseText);

                if (_.get(response, 'location.type') === 'download') {
                    new Ext.ux.file.Download({
                        params: {
                            method: 'Tinebase.downloadTempfile',
                            requestType: 'HTTP',
                            tmpfileId: _.get(response, 'location.tempfile_id')
                        }
                    }).start();
                }
                
                Ext.Msg.show({
                    title: i18n._('Success'),
                    msg: i18n._('Export created successfully.'),
                    icon: Ext.MessageBox.INFO,
                    buttons: Ext.Msg.OK,
                    scope: this.window,
                    fn: this.window.close
                });
                
            }).catch((error) => {
                Ext.Msg.show({
                    title: i18n._('Failure'),
                    msg: i18n._('Export could not be created. Please try again later'),
                    icon: Ext.MessageBox.ERROR,
                    buttons: Ext.Msg.OK,
                    scope: this.window,
                    fn: this.window.close
                });
            });
            
        } else {
            Ext.Msg.show({
                title: i18n._('Errors'),
                msg: this.getValidationErrorMessage(),
                icon: Ext.MessageBox.ERROR,
                buttons: Ext.Msg.OK
            });
        }
    }
});

Tine.widgets.dialog.ExportDialog.openWindow = function (config) {
    return Tine.WindowFactory.getWindow({
        width: Tine.widgets.dialog.ExportDialog.prototype.width,
        height: Tine.widgets.dialog.ExportDialog.prototype.height,
        name: Tine.widgets.dialog.ExportDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.ExportDialog',
        contentPanelConstructorConfig: config
    });
};

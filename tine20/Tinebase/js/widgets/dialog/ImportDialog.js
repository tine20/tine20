/**
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * Generic 'Import' dialog
 *
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.ImportDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * @constructor
 * @param       {Object} config The configuration options.
 * 
 * TODO add app grid to show results when dry run is selected
 */
Tine.widgets.dialog.ImportDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'ImportWindow_',
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,
    sendRequest: true,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Tinebase.Model.ImportJob;
        this.definitionsStore = new Ext.data.JsonStore({
            fields: Tine.Tinebase.Model.ImportExportDefinition,
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        // check if initial data available
        if (Tine[this.appName].registry.get('importDefinitions')) {
            this.definitionsStore.loadData(Tine[this.appName].registry.get('importDefinitions'));
        }
        
        Tine.widgets.dialog.ImportDialog.superclass.initComponent.call(this);
    },
    
    /**
     * executed when record gets updated from form
     * - add files to record here
     * 
     * @private
     */
    onRecordUpdate: function() {

        this.record.data.files = [];
        this.uploadGrid.store.each(function(record) {
            this.record.data.files.push(record.data);
        }, this);
        
        Tine.widgets.dialog.ImportDialog.superclass.onRecordUpdate.call(this);
    },
    
    /**
     * returns dialog
     */
    getFormItems: function() {
        this.uploadGrid = new Tine.widgets.grid.FileUploadGrid({
            fieldLabel: _('Files'),
            record: this.record,
            hideLabel: true,
            height: 150,
            frame: true
        });
        
        var containerName = this.app.i18n.n_hidden(this.record.get('model').getMeta('containerName'), this.record.get('model').getMeta('containersName'), 1);
        
        return {
            bodyStyle: 'padding:5px;',
            buttonAlign: 'right',
            labelAlign: 'top',
            border: false,
            layout: 'form',
            defaults: {
                anchor: '100%'
            },
            items: [{
                xtype: 'combo',
                fieldLabel: _('Import definition'), 
                name:'import_definition_id',
                store: this.definitionsStore,
                displayField:'name',
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                allowBlank: false,
                forceSelection: true,
                valueField:'id'
            }, new Tine.widgets.container.selectionComboBox({
                id: this.app.appName + 'EditDialogContainerSelector',
                fieldLabel: String.format(_('Import into {0}'), containerName),
                width: 300,
                name: 'container_id',
                stateful: false,
                containerName: containerName,
                containersName: this.app.i18n._hidden(this.record.get('model').getMeta('containersName')),
                appName: this.app.appName,
                requiredGrant: false
            }), {
                xtype: 'checkbox',
                name: 'dry_run',
                fieldLabel: _('Dry run'),
                checked: true
            },
                this.uploadGrid
            ]
        };
    },
    
    /**
     * apply changes handler
     */
    onApplyChanges: function(button, event, closeWindow) {
        var form = this.getForm();
        if(form.isValid()) {
            this.onRecordUpdate();
            
            if (this.record.get('files').length == 0) {
                Ext.MessageBox.alert(_('No files added'), _('You need to add files to import.'));
                return;
            }
            
            if (this.sendRequest) {
                this.loadMask.show();
                
                var params = {
                    method: this.appName + '.import' + this.record.get('model').getMeta('recordsName'),
                    files: this.record.get('files'),
                    definitionId: this.record.get('import_definition_id'),
                    importOptions: {
                        container_id: this.record.get('container_id'),
                        dryrun: this.record.get('dry_run')
                    }
                };
                
                Ext.Ajax.request({
                    params: params,
                    scope: this,
                    timeout: 1800000, // 30 minutes
                    success: function(_result, _request){
                        this.loadMask.hide();
                        
                        var response = Ext.util.JSON.decode(_result.responseText);
                        if (this.record.get('dry_run')) {
                            // uncheck dry run and show results
                            form.findField('dry_run').setValue(false);
                            
                            Ext.MessageBox.alert(
                                _('Dry run results'), 
                                String.format(_('Import test successful for {0} records, import test failed for {1} records.'), response.totalcount, response.failcount)
                            );
                        } else {
                            Ext.MessageBox.alert(
                                _('Import results'), 
                                String.format(_('Import successful for {0} records / import failed for {1} records / {2} duplicates found'),
                                    response.totalcount, response.failcount, response.duplicatecount),
                                function() {
                                    // import done
                                    this.fireEvent('update', response);
                                    if (closeWindow) {
                                        this.purgeListeners();
                                        this.window.close();
                                    }                                    
                                },
                                this
                            );                            
                        }
                    }
                });
            } else {
                this.fireEvent('update', values);
                this.window.close();
            }
            
        } else {
            Ext.MessageBox.alert(_('Errors'), _('Please fix the errors noted.'));
        }
    }
});

/**
 * credentials dialog popup / window
 */
Tine.widgets.dialog.ImportDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 500,
        name: Tine.widgets.dialog.ImportDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.ImportDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};

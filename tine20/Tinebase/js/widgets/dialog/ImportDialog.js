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

Ext.namespace('Tine.widgets', 'Tine.widgets.dialog');

/**
 * Generic 'Import' dialog
 */
/**
 * @class Tine.widgets.dialog.ImportPanel
 * @extends Tine.widgets.dialog.EditDialog
 * @constructor
 * @param {Object} config The configuration options.
 * 
 * TODO add form fields (dry run)
 * TODO add app grid to show results when dry run is selected
 * TODO fix js exception on cancel: 'this.container.remove is not a function'
 */
Tine.widgets.dialog.ImportDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {String} title of window
     */
    windowTitle: '',
    
    /**
     * @private
     */
    windowNamePrefix: 'ImportWindow_',
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,
    sendRequest: true,
    getDefaultsAgain: false,
    
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
        
        // check if initital data available
        if (Tine[this.appName].registry.get('importDefinitions')) {
            this.definitionsStore.loadData(Tine[this.appName].registry.get('importDefinitions'));
        }
        
        Tine.widgets.dialog.ImportDialog.superclass.initComponent.call(this);
    },
    
    onRender: function() {
        this.supr().onRender.apply(this, arguments);
        this.window.setTitle(this.windowTitle);
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
                fieldLabel: _('Import into container'),
                width: 300,
                name: 'container_id',
                stateful: false,
                containerName: this.app.i18n.n_hidden(this.record.get('model').getMeta('containerName'), this.record.get('model').getMeta('containersName'), 1),
                containersName: this.app.i18n._hidden(this.record.get('model').getMeta('containersName')),
                appName: this.app.appName,
                requiredGrant: false
            }),
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
                        container_id: this.record.get('container_id').id,
                        dryrun: false
                    }
                };
                
                Ext.Ajax.request({
                    params: params,
                    scope: this,
                    success: function(_result, _request){
                        this.loadMask.hide();
                        this.fireEvent('update', _result);
                        
                        if (closeWindow) {
                            this.purgeListeners();
                            this.window.close();
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

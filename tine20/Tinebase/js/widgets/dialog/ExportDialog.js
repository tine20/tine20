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
    height: 150,
    /**
     * @cfg {Number} width
     */
    width: 400,

    // private
    windowNamePrefix: 'ExportWindow_',
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
            exportDefinitions = Tine.widgets.exportAction.getExports(recordClass, false, scope);

        // check if initial data available
        if (Tine[this.appName].registry.get('exportDefinitions')) {
            Ext.each(exportDefinitions, function(defData) {
                var options = defData.plugin_options_json,
                    extension = options ? options.extension : null;
                
                defData.label = this.app.i18n._hidden(defData.label ? defData.label : defData.name);
                this.definitionsStore.addSorted(new Tine.Tinebase.Model.ImportExportDefinition(defData, defData.id));
            }, this);
            this.definitionsStore.sort('label');
        }
        
        Tine.widgets.dialog.ExportDialog.superclass.initComponent.call(this);
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
        
        this.window.setTitle(String.format(i18n._('Export {0} {1}'), this.record.get('count'), this.record.get('recordsName')));
    },

    /**
     * returns dialog
     */
    getFormItems: function() {
        if (this.record) {
            // remove all definitions that does not share the (grid) model / store.filter() did not do the job
            this.definitionsStore.each(function(record) {
                if (record.get('model') !== this.record.get('model') || record.get('favorite') == '1') {
                    this.definitionsStore.remove(record);
                }
            }, this);
        }
            
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
                fieldLabel: i18n._('Export definition'),
                name:'export_definition_id',
                store: this.definitionsStore,
                displayField:'label',
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                allowBlank: false,
                forceSelection: true,
                emptyText: i18n._('Select Export Definition ...'),
                valueField: 'id',
                value: (this.definitionsStore.getCount() > 0) ? this.definitionsStore.getAt(0).id : null 
            }]
        };
    },
    
    /**
     * apply changes handler
     */
    onApplyChanges: function(closeWindow) {
        var form = this.getForm();
        if (form.isValid()) {
            this.onRecordUpdate();

            Tine.widgets.exportAction.downloadExport(this.record);
            this.window.close();
            
        } else {
            Ext.MessageBox.alert(i18n._('Errors'), i18n._('Please fix the errors noted.'));
        }
    }
});

Tine.widgets.dialog.ExportDialog.openWindow = function (config) {
    return Tine.WindowFactory.getWindow({
        width: Tine.widgets.dialog.ExportDialog.prototype.width,
        height: Tine.widgets.dialog.ExportDialog.prototype.height,
        name: Tine.widgets.dialog.ExportDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.ExportDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
};

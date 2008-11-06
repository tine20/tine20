/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Tasks');

/**
 * Tasks Edit Dialog
 */
Tine.Tasks.EditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {Number}
     */
    containerId: -1,
    /**
     * @cfg {String}
     */
    relatedApp: '',
    /**
     * @private
     */
    labelAlign: 'side',
    
    /**
     * @private
     */
    windowNamePrefix: 'TasksEditWindow_',
    appName: 'Tasks',
    modelName: 'Task',
    recordClass: Tine.Tasks.Task,
    recordProxy: Tine.Tasks.JsonBackend,
    titleProperty: 'summary',
    containerItemName: 'Task',
    containerItemsName: 'Tasks',
    containerName: 'to do list',
    containesrName: 'to do lists',
    showContainerSelector: true,
    
    /**
     * reqests all data needed in this dialog
     */
    requestData: function() {
        this.loadRequest = Ext.Ajax.request({
            scope: this,
            success: function(response) {
                this.record = this.recordProxy.recordReader(response);
                this.onRecordLoad();
            },
            params: {
                method: 'Tasks.getTask',
                uid: this.record.id,
                containerId: this.containerId,
                relatedApp: this.relatedApp
            }
        });
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() { return {
        layout:'column',
        autoHeight: true,
        labelWidth: 90,
        border: false,

        items: [{
            columnWidth: 0.65,
            border:false,
            layout: 'form',
            defaults: {
                anchor: '95%',
                hideLabel: true,
                xtype: 'textfield'
            },
            items:[{
                fieldLabel: this.translation._('Summary'),
                name: 'summary',
                emptyText: this.translation._('Enter short name...'),
                listeners: {render: function(field){field.focus(false, 250);}},
                allowBlank: false
            }, {
                fieldLabel: this.translation._('Notes'),
                emptyText: this.translation._('Enter description...'),
                name: 'description',
                xtype: 'textarea',
                height: 150
            }]
        }, {
            columnWidth: 0.35,
            border:false,
            layout: 'form',
            defaults: {
                anchor: '95%'
            },
            items:[ 
                new Ext.ux.PercentCombo({
                    fieldLabel: this.translation._('Percentage'),
                    editable: false,
                    name: 'percent'
                }), 
                new Tine.Tasks.status.ComboBox({
                    fieldLabel: this.translation._('Status'),
                    name: 'status_id'
                }), 
                new Tine.widgets.Priority.Combo({
                    fieldLabel: this.translation._('Priority'),
                    name: 'priority'
                }), 
                new Ext.ux.form.ClearableDateField({
                    fieldLabel: this.translation._('Due date'),
                    name: 'due'
                })
            ]
        }]
    };}
});

/**
 * Tasks Edit Popup
 */
Tine.Tasks.EditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 700,
        height: 300,
        name: Tine.Tasks.EditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Tasks.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
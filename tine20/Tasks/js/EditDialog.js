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
        Ext.Ajax.request({
            scope: this,
            success: this.onDataLoad,
            params: {
                method: 'Tasks.getTask',
                uid: this.record.id,
                containerId: this.containerId,
                relatedApp: this.relatedApp
            }
        });
    },
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Tasks.EditDialog.superclass.onRender.call(this, ct, position);
        Ext.MessageBox.wait(this.translation._('Loading Task...'), _('Please Wait'));
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
                xtype: 'textfield'
            },
            items:[{
                fieldLabel: this.translation._('Summary'),
                hideLabel: true,
                xtype: 'textfield',
                name: 'summary',
                emptyText: this.translation._('Enter short name...'),
                listeners: {render: function(field){field.focus(false, 250);}},
                allowBlank: false
            }, {
                fieldLabel: this.translation._('Notes'),
                hideLabel: true,
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
                })/*, 
                new Tine.widgets.container.selectionComboBox({
                    fieldLabel: this.translation._('Saved in'),
                    name: 'container_id',
                    itemName: 'Tasks',
                    appName: 'Tasks'
                })*/
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
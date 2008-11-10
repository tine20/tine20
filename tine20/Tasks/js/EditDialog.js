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
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    
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
     * executed when record is loaded
     */
    onRecordLoad: function() {
        Tine.Tasks.EditDialog.superclass.onRecordLoad.call(this);
        this.handleCompletedDate();
    },
    
    /**
     * handling for the completed field
     */
    handleCompletedDate: function() {
        var status = Tine.Tasks.status.getStatus(this.getForm().findField('status_id').getValue());
        var completed = this.getForm().findField('completed');
        
        if (status.get('status_is_open') == 1) {
            completed.setValue(null);
            completed.setDisabled(true);
        } else {
            if (! Ext.isDate(completed.getValue())){
                completed.setValue(new Date());
            }
            completed.setDisabled(false);
        }
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() { 
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items:[{
                title: this.translation._('Task'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: .333
                    },
                    items: [[{
                        columnWidth: 1,
                        fieldLabel: this.translation._('Summary'),
                        name: 'summary',
                        emptyText: this.translation._('Enter short name...'),
                        listeners: {render: function(field){field.focus(false, 250);}},
                        allowBlank: false
                    }], [ new Ext.ux.form.ClearableDateField({
                        fieldLabel: this.translation._('Due date'),
                        name: 'due'
                    }), new Tine.widgets.Priority.Combo({
                        fieldLabel: this.translation._('Priority'),
                        name: 'priority'
                    }), new Tine.widgets.AccountpickerField({
                        fieldLabel: this.translation._('Responsible'),
                    })], [{
                        columnWidth: 1,
                        fieldLabel: this.translation._('Notes'),
                        emptyText: this.translation._('Enter description...'),
                        name: 'description',
                        xtype: 'textarea',
                        height: 200
                    }], [new Ext.ux.PercentCombo({
                        fieldLabel: this.translation._('Percentage'),
                        editable: false,
                        name: 'percent'
                    }), new Tine.Tasks.status.ComboBox({
                        fieldLabel: this.translation._('Status'),
                        name: 'status_id',
                        listeners: {scope: this, 'change': this.handleCompletedDate}
                    }), new Ext.form.DateField({
                        fieldLabel: this.translation._('Completed'),
                        name: 'completed'
                    })]]
                }, {
                    // activities and tags
                    layout: 'accordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Tine.widgets.activities.ActivitiesPanel({
                            app: 'Tasks',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.idProperty,
                record_model: this.appName + '_Model_' + this.modelName
            })]
        };
    }
});

/**
 * Tasks Edit Popup
 */
Tine.Tasks.EditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Tasks.EditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Tasks.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
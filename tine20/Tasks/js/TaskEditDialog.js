/*
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Tasks');

/**
 * @namespace   Tine.Tasks
 * @class       Tine.Tasks.TaskEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Tasks Edit Dialog</p>
 * <p>
 * TODO         refactor this: remove initRecord/containerId/relatedApp, 
 *              adopt to normal edit dialog flow and add getDefaultData to task model
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tasks.TaskEditDialog
 */
 Tine.Tasks.TaskEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {Number} containerId
     */
    containerId: -1,
    
    /**
     * @cfg {String} relatedApp
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
    recordClass: Tine.Tasks.Model.Task,
    recordProxy: Tine.Tasks.JsonBackend,
    showContainerSelector: true,
    displayNotes: true,

    /**
     * @private
     */
    initComponent: function() {
        
        if(!this.record) {
            this.record = new this.recordClass(this.recordClass.getDefaultData(), 0);
        }
        
        this.alarmPanel = new Tine.widgets.dialog.AlarmPanel({});
        Tine.Tasks.TaskEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * executed when record is loaded
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        Tine.Tasks.TaskEditDialog.superclass.onRecordLoad.apply(this, arguments);
        this.handleCompletedDate();
        
        // update tabpanels
        this.alarmPanel.onRecordLoad(this.record);
        
        if (! this.copyRecord && ! this.record.id) {
            this.window.setTitle(this.app.i18n._('Add New Task'));
        }
    },
    
    /**
     * executed when record is updated
     * @private
     */
    onRecordUpdate: function() {
        Tine.Tasks.TaskEditDialog.superclass.onRecordUpdate.apply(this, arguments);
        this.alarmPanel.onRecordUpdate(this.record);
    },
    
    /**
     * handling for the completed field
     * @private
     */
    handleCompletedDate: function() {
        
        var statusStore = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Tasks', 'taskStatus'),
            status = this.getForm().findField('status').getValue(),
            statusRecord = statusStore.getById(status),
            completedField = this.getForm().findField('completed');
        
        if (statusRecord) {
            if (statusRecord.get('is_open') !== 0) {
                completedField.setValue(null);
                completedField.setDisabled(true);
            } else {
                if (! Ext.isDate(completedField.getValue())){
                    completedField.setValue(new Date());
                }
                completedField.setDisabled(false);
            }
        }
        
    },
    
    /**
     * checks if form data is valid
     * 
     * @return {Boolean}
     */
    isValid: function() {
        var isValid = true;
        
        var dueField = this.getForm().findField('due'),
            dueDate = dueField.getValue(),
            alarms = this.alarmPanel.alarmGrid.getFromStoreAsArray();
            
        if (! Ext.isEmpty(alarms) && ! Ext.isDate(dueDate)) {
            dueField.markInvalid(this.app.i18n._('You have to supply a due date, because an alarm ist set!'));
            
            isValid = false;
        }
        
        return isValid && Tine.Tasks.TaskEditDialog.superclass.isValid.apply(this, arguments);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * @private
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            items:[{
                title: this.app.i18n.n_('Task', 'Tasks', 1),
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
                        fieldLabel: this.app.i18n._('Summary'),
                        name: 'summary',
                        listeners: {render: function(field){field.focus(false, 250);}},
                        allowBlank: false
                    }], [new Ext.ux.form.DateTimeField({
                            allowBlank: true,
                            defaultTime: '12:00',
                            fieldLabel: this.app.i18n._('Due date'),
                            name: 'due'
                        }), 
                        new Tine.Tinebase.widgets.keyfield.ComboBox({
                            fieldLabel: this.app.i18n._('Priority'),
                            name: 'priority',
                            app: 'Tasks',
                            keyFieldName: 'taskPriority',
                        }),
                        Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                            userOnly: true,
                            fieldLabel: this.app.i18n._('Organizer'),
                            emptyText: i18n._('Add Responsible ...'),
                            useAccountRecord: true,
                            name: 'organizer',
                            allowEmpty: true
                        })
                    ], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Notes'),
                        emptyText: this.app.i18n._('Enter description...'),
                        name: 'description',
                        xtype: 'textarea',
                        height: 200
                    }], [
                        new Ext.ux.PercentCombo({
                            fieldLabel: this.app.i18n._('Percentage'),
                            editable: false,
                            name: 'percent'
                        }), 
                        new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Tasks',
                            keyFieldName: 'taskStatus',
                            fieldLabel: this.app.i18n._('Status'),
                            name: 'status',
                            value: 'NEEDS-ACTION',
                            allowBlank: false,
                            listeners: {scope: this, 'change': this.handleCompletedDate}
                        }), 
                        new Ext.ux.form.DateTimeField({
                            allowBlank: true,
                            defaultTime: '12:00',
                            fieldLabel: this.app.i18n._('Completed'),
                            name: 'completed'
                        })
                    ]]
                }, {
                    // activities and tags
                    layout: 'ux.multiaccordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Tine.widgets.tags.TagPanel({
                            app: 'Tasks',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (this.record) ? this.record.id : '',
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            }), this.alarmPanel
            ]
        };
    }
});

/**
 * Tasks Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Tasks.TaskEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 900,
        height: 490,
        name: Tine.Tasks.TaskEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Tasks.TaskEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

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
    recordClass: Tine.Tasks.Task,
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
        //this.getForm().findField('summary').focus(false, 250);
    },
    
    /**
     * execuded after record got updated
     */
    onRecordLoad: function() {
        if (! this.record.id) {
            this.window.setTitle(this.translation.gettext('Add New Task'));
        } else {
            this.window.setTitle(sprintf(this.translation._('Edit Task "%s"'), this.record.get('summary')));
        }
        
        this.getForm().loadRecord(this.record);
        this.updateToolbars(this.record);
        Ext.MessageBox.hide();
    },
    
    /**
     * @private
     */
    handlerApplyChanges: function(_button, _event) {
        var closeWindow = arguments[2] ? arguments[2] : false;

        var form = this.getForm();
        if(form.isValid()) {
            Ext.MessageBox.wait(this.translation._('Please wait'), this.translation._('Saving Task'));
            
            // merge changes from form into task record
            form.updateRecord(this.record);
            
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Tasks.saveTask', 
                    task: Ext.util.JSON.encode(this.record.data)
                },
                success: function(response) {
                    // override task with returned data
                    this.onDataLoad(response);
                    this.fireEvent('update', this.record);
                    
                    // free 0 namespace if record got created
                    this.window.rename(this.windowNamePrefix + this.record.id);

                    if (closeWindow) {
                        this.purgeListeners();
                        this.window.close();
                    } else {
                        // update form with this new data
                        form.loadRecord(this.record);
                        this.action_delete.enable();
                        Ext.MessageBox.hide();
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not save task.')); 
                } 
            });
        } else {
            Ext.MessageBox.alert(this.translation._('Errors'), this.translation._('Please fix the errors noted.'));
        }
    },
    
    /**
     * @private
     */
    handlerDelete: function(_button, _event) {
        Ext.MessageBox.confirm(this.translation._('Confirm'), this.translation._('Do you really want to delete this task?'), function(_button) {
            if(_button == 'yes') {
                Ext.MessageBox.wait(this.translation._('Please wait a moment...'), this.translation._('Saving Task'));
                Ext.Ajax.request({
                    params: {
                        method: 'Tasks.deleteTask',
                        identifier: this.record.id
                    },
                    success: function(_result, _request) {
                        this.fireEvent('update', this.record);
                        this.purgeListeners();
                        this.window.close();
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not delete task(s).'));
                        Ext.MessageBox.hide();
                    }
                });
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
                xtype: 'textfield'
            },
            items:[{
                fieldLabel: this.translation._('Summary'),
                hideLabel: true,
                xtype: 'textfield',
                name: 'summary',
                emptyText: this.translation._('Enter short name...'),
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
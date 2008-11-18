/**
 * Tine 2.0
 * 
 * @package     Erp
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Erp');

/**
 * Erp Edit Dialog
 */
Tine.Erp.ContractEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {Number}
     */
    containerId: -1,
    /**
     * @private
     */
    labelAlign: 'side',
    
    /**
     * @private
     */
    windowNamePrefix: 'ContractEditWindow_',
    appName: 'Erp',
    recordClass: Tine.Erp.Contract,
    recordProxy: Tine.Erp.JsonBackend,
    //showContainerSelector: true,
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
                method: 'Erp.getContract',
                uid: this.record.id
                //containerId: this.containerId
            }
        });
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
                title: this.app.i18n._('Contract'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: []
                /*,
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
                    }], [ new Ext.ux.form.ClearableDateField({
                        fieldLabel: this.app.i18n._('Due date'),
                        name: 'due'
                    }), new Tine.widgets.Priority.Combo({
                        fieldLabel: this.app.i18n._('Priority'),
                        name: 'priority'
                    }), new Tine.widgets.AccountpickerField({
                        fieldLabel: this.app.i18n._('Responsible'),
                        name: 'organizer'
                    })], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Notes'),
                        emptyText: this.app.i18n._('Enter description...'),
                        name: 'description',
                        xtype: 'textarea',
                        height: 200
                    }], [new Ext.ux.PercentCombo({
                        fieldLabel: this.app.i18n._('Percentage'),
                        editable: false,
                        name: 'percent'
                    }), new Tine.Erp.status.ComboBox({
                        fieldLabel: this.app.i18n._('Status'),
                        name: 'status_id',
                        listeners: {scope: this, 'change': this.handleCompletedDate}
                    }), new Ext.form.DateField({
                        fieldLabel: this.app.i18n._('Completed'),
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
                            app: 'Erp',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]*/
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});

/**
 * Erp Edit Popup
 */
Tine.Erp.ContractEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Erp.ContractEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Erp.ContractEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
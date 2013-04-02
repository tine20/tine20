/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.FreeTimeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>FreeTime Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * Create a new Tine.HumanResources.FreeTimeEditDialog
 */
Tine.HumanResources.FreeTimeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    mode: 'local',
    loadRecord: false,
    windowWidth: 440,
    windowHeight: 450,
    /**
     * show private Information (autoset due to rights)
     * @type 
     */
    showPrivateInformation: null,
    
    type: null,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: Ext.emptyFn,
    
    /**
     * inits the component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources')
        this.showPrivateInformation = (Tine.Tinebase.common.hasRight('edit_private','HumanResources')) ? true : false;
        this.initDatePicker();
        this.mode = 'local';
        Tine.HumanResources.FreeTimeEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        if (Ext.isString(this.record)) {
            this.record = this.recordProxy.recordReader({responseText: this.record});
        }
        this.record.set('employee_id', this.fixedFields.get('employee_id'));
        
        this.datePicker.onRecordLoad(this.record, this.fixedFields.get('employee_id').id);
        
        if (this.record.get('employee_id')) {
            this.window.setTitle(String.format(_('Edit {0} "{1}"'), this.i18nRecordName, this.record.getTitle()));
        }
        
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordLoad.call(this);
        
        var typeString = this.type == 'SICKNESS' ? 'Sickness Days' : 'Vacation Days';
        
        if (this.record.id) {
            this.window.setTitle(String.format(this.app.i18n._('Add {0} for {1}'), typeString, this.record.get('employee_id').n_fn));
        } else {
            this.window.setTitle(String.format(this.app.i18n._('Edit {0} for {1}'), typeString, this.record.get('employee_id').n_fn));
        }
    },

    /**
     * executed when record gets updated from form
     * @private
     */
    onRecordUpdate: function() {
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordUpdate.call(this);
        this.record.set('freedays', this.datePicker.getData());
        this.record.set('type', this.fixedFields.get('type').toLowerCase());
        this.record.set('firstday_date', new Date(this.datePicker.store.getFirstDay().get('date')));
    },
    
    /**
     * creates the date picker
     */
    initDatePicker: function() {
        this.datePicker = new Tine.HumanResources.DatePicker({
            recordClass: Tine.HumanResources.Model.FreeDay,
            app: this.app,
            editDialog: this,
            dateProperty: 'date',
            recordsProperty: 'freedays',
            foreignIdProperty: 'freeday_id'
        });
    },
    
    /**
     * validates day length
     * 
     * @param {Float/Integer} value
     * @return {Boolean}
     */
    isDayLengthValid: function(value) {
        return (value <= 1 && value >=0.25);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        this.type = this.fixedFields.get('type');
        var statusBoxDefaults = {
            fieldLabel: this.app.i18n._('Status'),
            xtype: 'widget-keyfieldcombo',
            app: 'HumanResources',
            name: 'status',
            columnWidth: 1
        };
        
        this.statusBox = this.type == 'SICKNESS' ? 
                Ext.apply({
                    keyFieldName: 'sicknessStatus',
                    value: 'EXCUSED'
                }, statusBoxDefaults)
            :   Ext.apply({
                    keyFieldName: 'vacationStatus',
                    value: 'REQUESTED'
                }, statusBoxDefaults);
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n._('FreeTime'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        autoHeight: true,
                        title: this.app.i18n._('FreeTime'),
                        items: [{
                            xtype: 'columnform',
                            style: { 'float': 'left', width: '50%', 'min-width': '178px' },
                            labelAlign: 'top',
                            formDefaults: {
                                xtype:'textfield',
                                anchor: '100%',
                                labelSeparator: '',
                                allowBlank: false,
                                columnWidth: 1
                            },
                            items: [
                                [this.statusBox],
                                [{
                                        xtype: 'panel',
                                        cls: 'HumanResources x-form-item',
                                        width: 220,
                                        style: {
                                            'float': 'right',
                                            margin: '0 5px 10px 0'
                                        },
                                        items: [{html: '<label style="display:block; margin-bottom: 5px">' + this.app.i18n._('Select Days') + '</label>'}, this.datePicker]
                                    }]
                                ]
                        }]
                    }]
                }, {
                    // activities and tags
                    layout: 'accordion',
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
                        new Ext.Panel({
                            title: this.app.i18n._('Description'),
                            iconCls: 'descriptionIcon',
                            layout: 'form',
                            labelAlign: 'top',
                            border: false,
                            items: [{
                                style: 'margin-top: -4px; border 0px;',
                                labelSeparator: '',
                                xtype: 'textarea',
                                name: 'description',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars: false,
                                anchor: '100% 100%',
                                emptyText: _('Enter description'),
                                requiredGrant: 'editGrant'
                            }]
                        }),
                        new Tine.widgets.activities.ActivitiesPanel({
                            app: 'HumanResources',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'HumanResources',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, 
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
                }) 
            ]
        };
    }
});
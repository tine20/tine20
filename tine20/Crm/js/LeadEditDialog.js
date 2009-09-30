/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:LeadEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Crm');

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.LeadEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Lead Edit Dialog</p>
 * <p>
 * TODO         make marking of invalid fields work again
 * TODO         add link grids
 * TODO         add export button
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:LeadEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Crm.LeadEditDialog
 */
Tine.Crm.LeadEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'LeadEditWindow_',
    appName: 'Crm',
    recordClass: Tine.Crm.Model.Lead,
    recordProxy: Tine.Crm.leadBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // you can do something here

        Tine.Crm.LeadEditDialog.superclass.onRecordLoad.call(this);        
    },
    
    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * 
     * @private
     */
    onRecordUpdate: function() {
        Tine.Crm.LeadEditDialog.superclass.onRecordUpdate.call(this);
        
        // you can do something here    
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
        
        this.combo_probability = new Ext.ux.PercentCombo({
            fieldLabel: this.app.i18n._('Probability'), 
            id: 'combo_probability',
            anchor:'95%',            
            name:'probability'
        });
        
        this.date_end = new Ext.ux.form.ClearableDateField({
            fieldLabel: this.app.i18n._('End'), 
            id: 'end',
            anchor: '95%'
        });
        
        this.contactGrid = new Tine.Crm.ContactGridPanel({
            record: this.record,
            height: 210
        });
    
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n._('Lead'),
                autoScroll: true,
                border: true,
                frame: true,
                layout: 'border',
                id: 'editCenterPanel',
                defaults: {
                    border: true,
                    frame: true            
                },
                items: [{
                    region: 'center',
                    labelAlign: 'top',
                    layout: 'form',
                    height: '100%',
                    defaults: {
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 1
                    },
                    items: [{
                        xtype:'textfield',
                        hideLabel: true,
                        id: 'lead_name',
                        emptyText: this.app.i18n._('Enter short name'),
                        name:'lead_name',
                        allowBlank: false,
                        selectOnFocus: true
                    }, this.contactGrid 
                    /*{
                        xtype: 'panel',
                        id: 'linkPanelTop',
                        height: 210,
                        items: [
                            this.contactGrid
                        ]
                    }*/, {
                        xtype: 'panel',
                        layout:'column',
                        height: 140,
                        id: 'lead_combos',
                        anchor:'100%',
                        items: [{
                            columnWidth: 0.33,
                            items:[{
                                layout: 'form',
                                defaults: {
                                    valueField:'id',
                                    typeAhead: true,
                                    mode: 'local',
                                    triggerAction: 'all',
                                    editable: false,
                                    allowBlank: false,
                                    forceSelection: true,
                                    anchor:'95%',
                                    xtype: 'combo'
                                },
                                items: [{
                                    fieldLabel: this.app.i18n._('Leadstate'), 
                                    id:'leadstatus',
                                    name:'leadstate_id',
                                    store: Tine.Crm.LeadState.getStore(),
                                    displayField:'leadstate',
                                    lazyInit: false,
                                    value: Tine.Crm.LeadState.getStore().getAt(0).id,
                                    listeners: {
                                        'select': function(combo, record, index) {
                                            if (this.record.data.probability !== null) {
                                                this.combo_probability.setValue(record.data.probability);
                                            }
                                    
                                            if (this.record.data.endslead == '1') {
                                                this.combo_endDate.setValue(new Date());
                                            }
                                        },
                                        scope: this
                                    }
                                }, {
                                    fieldLabel: this.app.i18n._('Leadtype'), 
                                    id:'leadtype',
                                    name:'leadtype_id',
                                    store: Tine.Crm.LeadType.getStore(),
                                    value: Tine.Crm.LeadType.getStore().getAt(0).id,
                                    displayField:'leadtype'
                                }, {
                                    fieldLabel: this.app.i18n._('Leadsource'), 
                                    id:'leadsource',
                                    name:'leadsource_id',
                                    store: Tine.Crm.LeadSource.getStore(),
                                    value: Tine.Crm.LeadSource.getStore().getAt(0).id,
                                    displayField:'leadsource'
                                }]
                            }]
                        }, {
                            columnWidth: 0.33,
                            items:[{
                                layout: 'form',
                                border:false,
                                items: [
                                {
                                    xtype:'numberfield',
                                    fieldLabel: this.app.i18n._('Expected turnover'), 
                                    name: 'turnover',
                                    selectOnFocus: true,
                                    anchor: '95%'
                                },  
                                    this.combo_probability
                                ]
                            }]
                        }, {
                            columnWidth: 0.33,
                            items:[{
                                layout: 'form',
                                border:false,
                                items: [
                                    new Ext.form.DateField({
                                        fieldLabel: this.app.i18n._('Start'), 
                                        allowBlank: false,
                                        id: 'start',             
                                        anchor: '95%'
                                    }),
                                    new Ext.ux.form.ClearableDateField({
                                        fieldLabel: this.app.i18n._('Estimated end'), 
                                        id: 'end_scheduled',
                                        anchor: '95%'
                                    }),
                                    this.date_end   
                                ]
                            }]
                        }]
                    }, {
                        xtype: 'tabpanel',
                        id: 'linkPanelBottom',
                        activeTab: 0,
                        height: 250,
                        items: [
                            //_linkTabpanels.tasksPanel,
                            //_linkTabpanels.productsPanel
                        ]
                    }] // end of center lead panel
                    }, {
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
                            new Ext.Panel({
                                title: this.app.i18n._('Description'),
                                iconCls: 'descriptionIcon',
                                layout: 'form',
                                labelAlign: 'top',
                                border: false,
                                items: [{
                                    style: 'margin-top: -4px; border 0px;',
                                    labelSeparator: '',
                                    xtype:'textarea',
                                    name: 'description',
                                    hideLabel: true,
                                    grow: false,
                                    preventScrollbars:false,
                                    anchor:'100% 100%',
                                    emptyText: this.app.i18n._('Enter description')                        
                                }]
                            }),
                            new Tine.widgets.activities.ActivitiesPanel({
                                app: 'Crm',
                                showAddNoteForm: false,
                                border: false,
                                bodyStyle: 'border:1px solid #B5B8C8;'
                            }),
                            new Tine.widgets.tags.TagPanel({
                                app: 'Crm',
                                border: false,
                                bodyStyle: 'border:1px solid #B5B8C8;'
                            })
                        ]} // end of accordion panel (east)
                    ] // end of lead tabpanel items
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                    app: this.appName,
                    record_id: this.record.id,
                    record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
               }) // end of activities tabpanel
            ] // end of main tabpanel items
        }; // end of return
    } // end of getFormItems
});

/**
 * Crm Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Crm.LeadEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 750,
        name: Tine.Crm.LeadEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Crm.LeadEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

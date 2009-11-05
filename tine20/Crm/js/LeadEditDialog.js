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
 * TODO         simplify relation handling (move init of stores to relation grids and get data from there later?)
 * TODO         make marking of invalid fields work again
 * TODO         add export button
 * TODO         disable link grids if user has no run right for the app (adb/tasks/sales)
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
     * linked contacts grid
     * 
     * @type Tine.Crm.Contact.GridPanel
     * @property contactGrid
     */
    contactGrid: null,
    
    /**
     * linked tasks grid
     * 
     * @type Tine.Crm.Task.GridPanel
     * @property tasksGrid
     */
    tasksGrid: null,
    
    /**
     * @private
     */
    windowNamePrefix: 'LeadEditWindow_',
    appName: 'Crm',
    recordClass: Tine.Crm.Model.Lead,
    recordProxy: Tine.Crm.leadBackend,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    showContainerSelector: true,
    getDefaultsAgain: false,

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

        // load contacts/tasks/products into link grid (only first time this function gets called/store is empty)
        if (this.contactGrid && this.tasksGrid && this.productsGrid 
            && this.contactGrid.store.getCount() == 0 
            && this.tasksGrid.store.getCount() == 0 
            && this.productsGrid.store.getCount() == 0) {
                    
            var relations = this.splitRelations();
            //console.log(relations);
            
            this.contactGrid.store.loadData(relations.contacts, true);
            this.tasksGrid.store.loadData(relations.tasks, true);
            this.productsGrid.store.loadData(relations.products, true);
        }
        
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
        
        this.getAdditionalData();
    },
    
    /**
     * getRelationData
     * get the record relation data (switch relation and related record)
     * 
     * @param   Object record with relation data
     * @return  Object relation with record data
     */
    getRelationData: function(record) {
        var relation = null; 
        
        if (record.data.relation) {
            relation = record.data.relation;
        } else {
            // empty relation for new record
            relation = {};
        }

        // set the relation type
        if (!relation.type) {
            relation.type = record.data.relation_type.toUpperCase();
        }
        
        // do not do recursion!
        delete record.data.relation;
        //delete record.data.relation_type;
        
        // save record data        
        relation.related_record = record.data;
        
        // add remark values
        relation.remark = {};
        if (record.data.remark_price) {
            relation.remark.price = record.data.remark_price;
        }
        if (record.data.remark_description) {
            relation.remark.description = record.data.remark_description;
        }
        if (record.data.remark_quantity) {
            relation.remark.quanity = record.data.remark_quantity;
        }
        
        return relation;
    },

    /**
     * getAdditionalData
     * collects additional data (start/end dates, linked contacts, ...)
     * 
     */
    getAdditionalData: function() {
        
        // collect data of relations
        var relations = [];
        this.contactGrid.store.each(function(record) {                     
            relations.push(this.getRelationData(record));
        }, this);
        this.tasksGrid.store.each(function(record) {
            relations.push(this.getRelationData(record));
        }, this);
        this.productsGrid.store.each(function(record) {
            relations.push(this.getRelationData(record));
        }, this);
        
        this.record.data.relations = relations;
    },
    
    /**
     * split the relations array in contacts and tasks and switch related_record and relation objects
     * 
     * @return {Array}
     */
    splitRelations: function() {
        
        var contacts = [];
        var tasks = []
        var products = []
        
        var relations = this.record.get('relations');
        
        for (var i=0; i < relations.length; i++) {
            var newLinkObject = relations[i]['related_record'];
            delete relations[i]['related_record']['relation'];
            newLinkObject.relation = relations[i];
            newLinkObject.relation_type = relations[i]['type'].toLowerCase();
    
            //console.log(newLinkObject);
            if ((newLinkObject.relation_type === 'responsible' 
              || newLinkObject.relation_type === 'customer' 
              || newLinkObject.relation_type === 'partner')) {
                contacts.push(newLinkObject);
            } else if (newLinkObject.relation_type === 'task') {                
                tasks.push(newLinkObject);
            } else if (newLinkObject.relation_type === 'product') {
                newLinkObject.remark_description = relations[i].remark.description;
                newLinkObject.remark_price = relations[i].remark.price;
                newLinkObject.remark_quantity = relations[i].remark.quantity;
                products.push(newLinkObject);
            }
        }

        return {
            contacts: contacts,
            tasks: tasks,
            products: products
        };
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
        
        this.contactGrid = new Tine.Crm.Contact.GridPanel({
            record: this.record,
            anchor: '100% 98%'
        });

        this.tasksGrid = new Tine.Crm.Task.GridPanel({
            record: this.record,
            height: '100%'
        });
        
        this.productsGrid = new Tine.Crm.Product.GridPanel({
            record: this.record,
            height: '100%'
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
                    layout: 'border',
                    items: [{
                        region: 'north',
                        height: 40,
                        layout: 'form',
                        labelAlign: 'top',
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
                            selectOnFocus: true,
                            // TODO make this work
                            listeners: {render: function(field){field.focus(false, 250);}}
                        }]
                    }, {
                        region: 'center',
                        layout: 'form',
                        items: [ this.contactGrid ]
                    }, {
                        region: 'south',
                        height: 390,
                        split: true,
                        collapseMode: 'mini',
                        collapsible: true,
                        items: [{
                            xtype: 'panel',
                            layout:'column',
                            height: 140,
                            id: 'lead_combos',
                            anchor:'100%',
                            labelAlign: 'top',
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
                                                if (record.data.endslead == '1') {
                                                    this.date_end.setValue(new Date());
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
                                this.tasksGrid,
                                this.productsGrid
                            ]
                        }]
                    }] // end of center lead panel with border layout
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
    
    // obsolete code
//        exportLead: function(_button, _event) {         
//            
//            var leadId = Ext.util.JSON.encode([_button.leadId]);
//            
//            Tine.Tinebase.common.openWindow('exportWindow', 'index.php?method=Crm.exportLead&_format=pdf&_leadIds=' + leadId, 768, 1024);
//        }
    
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

/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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

    // problems with relations here
    checkUnsavedChanges: false,
    /**
     * @private
     */
    windowNamePrefix: 'LeadEditWindow_',
    appName: 'Crm',
    recordClass: Tine.Crm.Model.Lead,
    recordProxy: Tine.Crm.leadBackend,
    showContainerSelector: true,
    displayNotes: true,

    /**
     * ignore these models in relation grid
     * @type {Array}
     */
    ignoreRelatedModels: ['Sales_Model_Product', 'Addressbook_Model_Contact', 'Tasks_Model_Task'],

    initComponent: function() {
        this.tbarItems = [new Ext.Button(new Ext.Action({
            text: Tine.Tinebase.appMgr.get('Crm').i18n._('Mute Notification'),
            handler: this.onMuteNotificationOnce,
            iconCls: 'action_mute_noteification',
            disabled: false,
            scope: this,
            enableToggle: true,
            pressed: this.record.get('mute')
        }))];
        Tine.Crm.LeadEditDialog.superclass.initComponent.call(this);
        this.on('recordUpdate', this.onAfterRecordUpdate, this);
    },

    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onAfterRecordLoad: function() {
        Tine.Crm.LeadEditDialog.superclass.onAfterRecordLoad.call(this);
        // load contacts/tasks/products into link grid (only first time this function gets called/store is empty)
        if (this.contactGrid && this.tasksGrid && this.productsGrid 
            && this.contactGrid.store.getCount() == 0 
            && (! this.tasksGrid.store || this.tasksGrid.store.getCount() == 0) 
            && (! this.productsGrid.store || this.productsGrid.store.getCount() == 0)) {
            
            var relations = this.splitRelations();
            
            this.contactGrid.store.loadData(relations.contacts, true);
            
            if (this.tasksGrid.store) {
                this.tasksGrid.store.loadData(relations.tasks, true);
            }
            if (this.productsGrid.store) {
                this.productsGrid.store.loadData(relations.products, true);
            }
        }
    },

    /**
     * mute first alert
     *
     * @param {} button
     * @param {} e
     */
    onMuteNotificationOnce: function (button, e) {
        this.record.set('mute', button.pressed);
    },
    
    onAfterRecordUpdate: function(closeWindow) {
        this.getAdditionalData();
        
        var relations = [].concat(this.record.get('relations'));
        this.record.data.relations = relations;
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
        record.data.relation = null;
        delete record.data.relation;
        
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
            relation.remark.quantity = record.data.remark_quantity;
        }
        
        Tine.log.debug('Tine.Crm.LeadEditDialog::getRelationData() -> relation:');
        Tine.log.debug(relation);
        
        return relation;
    },

    /**
     * getAdditionalData
     * collects additional data (start/end dates, linked contacts, ...)
     */
    getAdditionalData: function() {
        var relations = this.record.get('relations'),
            grids = [this.contactGrid, this.tasksGrid, this.productsGrid];
            
        Ext.each(grids, function(grid) {
            if (grid.store) {
                grid.store.each(function(record) {
                    relations.push(this.getRelationData(record.copy()));
                }, this);
            }
        }, this);
        
        this.record.data.relations = relations;
    },

    /**
     * split the relations array in contacts and tasks and switch related_record and relation objects
     * 
     * @return {Array}
     */
    splitRelations: function() {
        
        var contacts = [],
            tasks = [],
            products = [];
        
        var relations = this.record.get('relations');
        
        for (var i=0; i < relations.length; i++) {
            var newLinkObject = relations[i]['related_record'];
            if (newLinkObject) {
                relations[i]['related_record']['relation'] = null;
                delete relations[i]['related_record']['relation'];
                // this creates a circular structure which could not be converted to json!
                // newLinkObject.relation = relations[i];
                newLinkObject.relation_type = relations[i]['type'].toLowerCase();

                if ((newLinkObject.relation_type === 'responsible'
                        || newLinkObject.relation_type === 'customer'
                        || newLinkObject.relation_type === 'partner')) {
                    contacts.push(newLinkObject);
                } else if (newLinkObject.relation_type === 'task') {
                    tasks.push(newLinkObject);
                } else if (newLinkObject.relation_type === 'product') {
                    newLinkObject.remark_description = (relations[i].remark) ? relations[i].remark.description : '';
                    newLinkObject.remark_price = (relations[i].remark) ? relations[i].remark.price : 0;
                    newLinkObject.remark_quantity = (relations[i].remark) ? relations[i].remark.quantity : 1;
                    products.push(newLinkObject);
                }
            }
        }
        
        return {
            contacts: contacts,
            tasks: tasks,
            products: products
        };
    },

    /**
     * generic apply changes handler
     * @param {Boolean} closeWindow
     */
    onApplyChanges: function(closeWindow) {
        if (this.app.featureEnabled('featureLeadNotificationConfirmation') && !this.record.get('mute')) {
            Ext.MessageBox.confirm(
                this.app.i18n._('Send Notification?'),
                this.app.i18n._('Changes to this lead might send notifications.'),
                function (button) {
                    if (button === 'yes') {
                        Tine.Crm.LeadEditDialog.superclass.onApplyChanges.call(this,closeWindow);
                    }
                },
                this
            );
            return;
        }
        Tine.Crm.LeadEditDialog.superclass.onApplyChanges.call(this,closeWindow);
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

        if (Tine.Tasks && Tine.Tinebase.common.hasRight('run', 'Tasks')) {
            this.tasksGrid = new Tine.Crm.Task.GridPanel({
                record: this.record
            });
        } else {
            this.tasksGrid = new Ext.Panel({
                title: this.app.i18n._('Tasks'),
                html: this.app.i18n._('You do not have the run right for the Tasks application or it is not activated.')
            })
        }
        
        if (Tine.Sales && Tine.Tinebase.common.hasRight('run', 'Sales')) {
            this.productsGrid = new Tine.Crm.Product.GridPanel({
                record: this.record
            });
        } else {
            this.productsGrid = new Ext.Panel({
                title: this.app.i18n._('Products'),
                html: this.app.i18n._('You do not have the run right for the Sales application or it is not activated.')
            })
        }

        // Don't show item if it's a archived source!
        var sourceStore = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Crm', 'leadsources');

        var preserveRecords = [];

        sourceStore.each(function(record) {
            preserveRecords.push(record.copy());
        });

        var copiedStore = new Ext.data.Store({
            recordType: sourceStore.recordType
        });

        copiedStore.add(preserveRecords);

        sourceStore.each(function(item) {
            if (item.json.archived == true) {
                sourceStore.remove(item);
            }
        });

        var setdeffered = function (combo) {
            var rawValue = parseInt(combo.getRawValue());

            if (Ext.isNumber(rawValue)) {
                combo.setRawValue(copiedStore.getById(combo.getValue()).get('value'));
            }
        };

        return {
            xtype: 'tabpanel',
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
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
                            xtype: 'textfield',
                            hideLabel: true,
                            id: 'lead_name',
                            emptyText: this.app.i18n._('Enter lead name'),
                            name: 'lead_name',
                            allowBlank: false,
                            selectOnFocus: true,
                            maxLength: 255,
                            // TODO make this work
                            listeners: {render: function(field){field.focus(false, 2000);}}
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
                        header: false,
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
                                        typeAhead: false,
                                        mode: 'local',
                                        triggerAction: 'all',
                                        editable: false,
                                        allowBlank: false,
                                        forceSelection: true,
                                        anchor:'95%',
                                        xtype: 'combo'
                                    },
                                    items: [new Tine.Tinebase.widgets.keyfield.ComboBox({
                                        app: 'Crm',
                                        keyFieldName: 'leadstates',
                                        fieldLabel: this.app.i18n._('Leadstate'),
                                        name: 'leadstate_id',
                                        showIcon: false,
                                        listeners: {
                                            'select': function(combo, record, index) {
                                                if (this.record.json.probability !== null) {
                                                    this.combo_probability.setValue(record.data.probability);
                                                }
                                                if (record.json.endslead == '1') {
                                                    this.date_end.setValue(new Date());
                                                }
                                            },
                                            scope: this
                                        }
                                    }), new Tine.Tinebase.widgets.keyfield.ComboBox({
                                        app: 'Crm',
                                        keyFieldName: 'leadtypes',
                                        fieldLabel: this.app.i18n._('Leadtype'),
                                        name: 'leadtype_id',
                                        showIcon: false
                                    }), new Tine.Tinebase.widgets.keyfield.ComboBox({
                                        app: 'Crm',
                                        keyFieldName: 'leadsources',
                                        fieldLabel: this.app.i18n._('Leadsource'),
                                        name: 'leadsource_id',
                                        showIcon: false,
                                        listeners: {
                                            scope: this,
                                            // When loading
                                            'beforerender': function (combo) {
                                                setdeffered.defer(5, this, [combo]);
                                            },
                                            // When focus changed
                                            'blur': function(combo) {
                                                setdeffered.defer(5, this, [combo]);
                                            }
                                        }
                                    })]
                                }]
                            }, {
                                columnWidth: 0.33,
                                items:[{
                                    layout: 'form',
                                    border:false,
                                    items: [
                                    {
                                        xtype:'extuxmoneyfield',
                                        fieldLabel: this.app.i18n._('Expected turnover'),
                                        name: 'turnover',
                                        selectOnFocus: true,
                                        anchor: '95%',
                                        minValue: 0
                                    },  
                                        this.combo_probability,
                                        new Ext.ux.form.ClearableDateField({
                                            fieldLabel: this.app.i18n._('Resubmission Date'), 
                                            id: 'resubmission_date',
                                            anchor: '95%'
                                        })
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
            })] // end of main tabpanel items
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

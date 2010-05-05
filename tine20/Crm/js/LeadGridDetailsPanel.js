/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Crm');

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.LeadGridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 * 
 * <p>Lead Grid Details Panel</p>
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 */
Tine.Crm.LeadGridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    border: false,
    
    /**
     * renders contact names
     * 
     * @param {String} type
     * @return {String}
     * 
     * TODO add mail link
     * TODO all labels should have the same width
     */
    contactRenderer: function(type) {
        
        var relations = this.record.get('relations');
        
        var a = [];
        var fields = [{
            label: (type == 'CUSTOMER') ? this.app.i18n._('Customer') : this.app.i18n._('Partner'),
            dataField: 'n_fileas'
        }, {
            label: this.app.i18n._('Phone'),
            dataField: 'tel_work'
        }, {
            label: this.app.i18n._('Mobile'),
            dataField: 'tel_cell'
        }, {
            label: this.app.i18n._('Fax'),
            dataField: 'tel_fax'
        }, {
            label: this.app.i18n._('E-Mail'),
            dataField: 'email'
        }, {
            label: this.app.i18n._('Web'),
            dataField: 'url'
        }];
        var labelMarkup = '<label class="x-form-item x-form-item-label">';
        
        if (Ext.isArray(relations) && relations.length > 0) {
            // get correct relation type from relations (contact) array
            for (var i = 0; i < relations.length; i++) {
                if (relations[i].type == type) {
                    var data = relations[i].related_record;
                    for (var j=0; j < fields.length; j++) {
                        if (data[fields[j].dataField]) {
                            if (fields[j].dataField == 'url') {
                                a.push(labelMarkup + fields[j].label + ':</label> '
                                    + '<a href="' + Ext.util.Format.htmlEncode(data[fields[j].dataField]) + '" target="_blank">' 
                                    + Ext.util.Format.htmlEncode(data[fields[j].dataField]) + '</a>');
                            } else {
                                a.push(labelMarkup + fields[j].label + ':</label> '  + Ext.util.Format.htmlEncode(data[fields[j].dataField]));
                            }
                        }
                    }
                    a.push('');
                }
            }
        }
        
        return a.join("\n");
        
        /*
        getMailLink: function(email, felamimail) {
                    if (! email) {
                        return '';
                    }
                    
                    var link = (felamimail) ? '#' : 'mailto:' + email;
                    var id = Ext.id() + ':' + email;
                    
                    return '<a href="' + link + '" class="tinebase-email-link" id="' + id + '">'
                        + Ext.util.Format.ellipsis(email, 18); + '</a>';
                }
         */
    },
    
    /**
     * renders container name
     * 
     * @param {Array} container
     * @return {String} html
     * 
     * TODO generalize this?
     * TODO add button/link to switch to container?
     */
    containerRenderer: function(container) {
        return this.containerTpl.apply({
            name: Ext.util.Format.htmlEncode(container && container.name ? container.name : '')
        });
    },
    
    /**
     * lead state renderer
     * 
     * @param   {Number} id
     * @param   {Store} store
     * @return  {String} label
     * @return  {String} label
     */
    sourceTypeRenderer: function(id, store, definitionsLabel) {
        record = store.getById(id);
        if (record) {
            return record.data[definitionsLabel];
        } else {
            return 'undefined';
        }
    },
    
    /**
     * inits this component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Crm');
        
        // define piechart stores
        this.leadstatePiechartStore = new Ext.data.JsonStore({
            fields: ['id', 'label', 'total'],
            id: 'id'
        });
        this.leadsourcePiechartStore = new Ext.data.JsonStore({
            fields: ['id', 'label', 'total'],
            id: 'id'
        });
        this.leadtypePiechartStore = new Ext.data.JsonStore({
            fields: ['id', 'label', 'total'],
            id: 'id'
        });
        
        // we need to define the chart url
        Ext.chart.Chart.CHART_URL = 'library/ExtJS/resources/charts.swf';
        
        /*
        this.defaultPanel = this.getDefaultPanel();
        this.leadDetailsPanel = this.getLeadGridDetailsPanel();
        
        this.cardPanel = new Ext.Panel({
            layout: 'card',
            border: false,
            activeItem: 0,
            items: [
                this.defaultPanel,
                this.leadDetailsPanel
            ]
        });
        
        this.items = [
            this.cardPanel
        ];
        */
        
        // TODO generalize this
        this.containerTpl = new Ext.XTemplate(
            '<div class="x-tree-node-leaf x-unselectable file">',
                '<img class="x-tree-node-icon" unselectable="on" src="', Ext.BLANK_IMAGE_URL, '">',
                '<span style="color: {color};">&nbsp</span>',
                '<span>{name}</span>',
            '</div>'
        ).compile();
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * default panel w.o. data
     * 
     * @return {Ext.ux.display.DisplayPanel}
     * 
     * TODO add legend?
     */
    getDefaultInfosPanel: function() {
        if (! this.defaultInfosPanel) {
            this.defaultInfosPanel = new Ext.ux.display.DisplayPanel({
                layout: 'fit',
                border: false,
                items: [{
                    layout: 'hbox',
                    border: false,
                    defaults:{
                        margins:'0 5 0 0',
                        padding: 2,
                        style: {
                            cursor: 'crosshair'
                        },
                        flex: 1,
                        layout: 'ux.display',
                        border: false
                    },
                    layoutConfig: {
                        padding:'5',
                        align:'stretch'
                    },
                    items: [{
                        layoutConfig: {
                            background: 'border',
                            declaration: this.app.i18n._('Leadstates')
                        },
                        items: [{
                            store: this.leadstatePiechartStore,
                            xtype: 'piechart',
                            dataField: 'total',
                            categoryField: 'label'
                        }]
                    }, {
                        layoutConfig: {
                            background: 'border',
                            declaration: this.app.i18n._('Leadsources')
                        },
                        items: [{
                            store: this.leadsourcePiechartStore,
                            xtype: 'piechart',
                            dataField: 'total',
                            categoryField: 'label'
                        }]
                    }, {
                        layoutConfig: {
                            background: 'border',
                            declaration: this.app.i18n._('Leadtypes')
                        },
                        items: [{
                            store: this.leadtypePiechartStore,
                            xtype: 'piechart',
                            dataField: 'total',
                            categoryField: 'label'
                        }]
                    }]
                }]
                /*
                    fieldLabel: this.app.i18n._('Leadstates'), // ??
                    xtype: 'piechart',
                    store: this.leadstatePiechartStore,
                    dataField: 'total',
                    categoryField: 'label',
                    backgroundColor: '#eeeeee' // ??
                    //extra styles get applied to the chart defaults
                    extraStyle: {
                        legend: {
                            //display: 'right',
                            display: 'top',
                            padding: 5,
                            font: {
                                family: 'Tahoma',
                                size: 8
                            }
                        }
                    } 
                */               
            });
        }
        
        return this.defaultInfosPanel;
    },
    
    /**
     * fill the piechart stores (calls loadPiechartStore() for all piecharts)
     */
    setPiechartStores: function(getFromRequest) {
        
        if (! this.getDefaultInfosPanel().isVisible()) {
            return;
        }
        
        if (getFromRequest === false) {
            var data = this.getCountFromSelection();
        } else {
            var data = {
                leadstate: this.grid.store.proxy.jsonReader.jsonData.totalleadstates,
                leadsource: this.grid.store.proxy.jsonReader.jsonData.totalleadsources,
                leadtype: this.grid.store.proxy.jsonReader.jsonData.totalleadtypes
            };
        }
        
        //console.log(data);
        
        var storesConfig = [{
            store: this.leadstatePiechartStore,
            data: data.leadstate,
            definitionsStore: Tine.Crm.LeadState.getStore(),
            definitionsLabel: 'leadstate'
        }, {
            store: this.leadsourcePiechartStore,
            data: data.leadsource,
            definitionsStore: Tine.Crm.LeadSource.getStore(),
            definitionsLabel: 'leadsource'
        }, {
            store: this.leadtypePiechartStore,
            data: data.leadtype,
            definitionsStore: Tine.Crm.LeadType.getStore(),
            definitionsLabel: 'leadtype'
        }];
        
        for (var i = 0; i < storesConfig.length; i++) {
            this.loadPiechartStore(storesConfig[i]);
        }
    },
    
    /**
     * get leadstzate/source/type count for charts from selection
     * 
     * @return {}
     */
    getCountFromSelection: function() {
      
        var result = {
            leadstate: {},
            leadsource: {},
            leadtype: {}
        };
        
        var selectedRows = this.grid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            //console.log(selectedRows[i]);
            if (! result.leadstate[selectedRows[i].get('leadstate_id')]) {
                result.leadstate[selectedRows[i].get('leadstate_id')] = 1;
            } else {
                result.leadstate[selectedRows[i].get('leadstate_id')]++;
            }

            if (! result.leadsource[selectedRows[i].get('leadsource_id')]) {
                result.leadsource[selectedRows[i].get('leadsource_id')] = 1;
            } else {
                result.leadsource[selectedRows[i].get('leadsource_id')]++;
            }

            if (! result.leadtype[selectedRows[i].get('leadtype_id')]) {
                result.leadtype[selectedRows[i].get('leadtype_id')] = 1;
            } else {
                result.leadtype[selectedRows[i].get('leadtype_id')]++;
            }
        }
        
        return result;
    },
    
    /**
     * load data into piechart store
     * 
     * @param {} config
     */
    loadPiechartStore: function(config) {
        try {
            if (config.store.getCount() > 0) {
                config.store.removeAll();
            }
            
            // get records from defintion / grid store request
            var records = []; 
            if (config.data) {
                config.definitionsStore.each(function(definition) {
                    if (config.data[definition.id]) {
                        records.push(new config.store.recordType({
                            id: definition.id,
                            label: definition.get(config.definitionsLabel),
                            total: config.data[definition.id]
                        }, definition.id));
                    }
                }, this);
            }
            
            // add new records
            if (records.length > 0) {
                config.store.add(records);
            }
        } catch (e) {
            //console.log('error while setting ' + config.definitionsLabel + ' piechart data');
            //console.log(e);
            
            // some error with the piechart occurred, try it again ...
            this.loadPiechartStore.defer(500, this, [config]);
        }
    },
    
    /**
     * get panel for multi selection aggregates/information
     * 
     * @return {Ext.Panel}
     */
    getMultiRecordsPanel: function() {
        return this.getDefaultInfosPanel();
    },
    
    /**
     * main lead details panel
     * 
     * @return {Ext.ux.display.DisplayPanel}
     * 
     * TODO add tasks / products?
     * TODO add contact icons?
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Ext.ux.display.DisplayPanel ({
                //xtype: 'displaypanel',
                layout: 'fit',
                border: false,
                items: [{
                    layout: 'vbox',
                    border: false,
                    layoutConfig: {
                        align:'stretch'
                    },
                    items: [
                        {
                        layout: 'hbox',
                        flex: 0,
                        height: 16,
                        border: false,
                        style: 'padding-left: 5px; padding-right: 5px',
                        layoutConfig: {
                            align:'stretch'
                        },
                        items: [{
                            flex: 1,
                            xtype: 'ux.displayfield',
                            cls: 'x-ux-display-header',
                            //style: 'padding-top: 2px',
                            name: 'lead_name'
                        }, {
                            flex: 1,
                            xtype: 'ux.displayfield',
                            style: 'text-align: right;',
                            name: 'container_id',
                            cls: 'x-ux-display-header',
                            htmlEncode: false,
                            renderer: this.containerRenderer.createDelegate(this)
                        }]
                    }, {
                        layout: 'hbox',
                        flex: 1,
                        border: false,
                        layoutConfig: {
                            padding:'5',
                            align:'stretch'
                        },
                        defaults:{margins:'0 5 0 0'},
                        items: [{
                            flex: 1,
                            layout: 'ux.display',
                            labelWidth: 90,
                            layoutConfig: {
                                background: 'solid',
                                declaration: this.app.i18n._('Status')
                            },
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'start',
                                fieldLabel: this.app.i18n._('Start'),
                                renderer: Tine.Tinebase.common.dateRenderer
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'end_scheduled',
                                fieldLabel: this.app.i18n._('Estimated end'),
                                renderer: Tine.Tinebase.common.dateRenderer
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'leadtype_id',
                                fieldLabel: this.app.i18n._('Leadtype'),
                                renderer: this.sourceTypeRenderer.createDelegate(this, [Tine.Crm.LeadType.getStore(), 'leadtype'], true)
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'leadsource_id',
                                fieldLabel: this.app.i18n._('Leadsource'),
                                renderer: this.sourceTypeRenderer.createDelegate(this, [Tine.Crm.LeadSource.getStore(), 'leadsource'], true)
                            }]
                        }, {
                            flex: 1,
                            layout: 'ux.display',
                            labelAlign: 'top',
                            autoScroll: true,
                            //cls: 'contactIconPartner',
                            layoutConfig: {
                                background: 'solid'
                                //declaration: this.app.i18n._('Partner')
                            },
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'partner',
                                nl2br: true,
                                htmlEncode: false,
                                renderer: this.contactRenderer.createDelegate(this, ['PARTNER'])
                            }]
                        }, {
                            flex: 1,
                            layout: 'ux.display',
                            labelAlign: 'top',
                            autoScroll: true,
                            //cls: 'contactIconCustomer',
                            layoutConfig: {
                                background: 'solid'
                                //declaration: this.app.i18n._('Customer')
                            },
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'customer',
                                nl2br: true,
                                htmlEncode: false,
                                renderer: this.contactRenderer.createDelegate(this, ['CUSTOMER'])
                            }]
                        }, {
                            flex: 1,
                            layout: 'fit',
                            border: false,
                            items: [{
                                cls: 'x-ux-display-background-border',
                                xtype: 'ux.displaytextarea',
                                name: 'description'
                            }]
                        }]
                    }]
                }]
            });
        }
        
        return this.singleRecordPanel
    },
    
    /**
     * update lead details panel
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        this.getSingleRecordPanel().loadRecord.defer(100, this.getSingleRecordPanel(), [record]);
    },
    
    /**
     * show default panel
     * 
     * @param {Mixed} body
     */
    showDefault: function(body) {
        this.setPiechartStores.defer(500, this, [true]);
    },
    
    /**
     * show template for multiple rows
     * 
     * @param {Ext.grid.RowSelectionModel} sm
     * @param {Mixed} body
     */
    showMulti: function(sm, body) {
        this.setPiechartStores.defer(1000, this, [false]);
    }
    
    /*
    TODO move this to generic grid panel?
    onClick: function(e) {
        var target = e.getTarget('a[class=tinebase-email-link]');
        if (target) {
            var email = target.id.split(':')[1];
            var defaults = Tine.Felamimail.Model.Message.getDefaultData();
            defaults.to = [email];
            defaults.body = Tine.Felamimail.getSignature();
            
            var record = new Tine.Felamimail.Model.Message(defaults, 0);
            var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
                record: record
            });
        }
    }
    */
});

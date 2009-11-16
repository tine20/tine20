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
 * @extends     Tine.Tinebase.widgets.grid.DetailsPanel
 * 
 * <p>Lead Grid Details Panel</p>
 * <p>
 * TODO         make it work for single leads
 * TODO         add charts for multiple selected leads
 * TODO         remove obsolete code
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 */
Tine.Crm.LeadGridDetailsPanel = Ext.extend(Tine.Tinebase.widgets.grid.DetailsPanel, {
    
    border: false,
    
    /**
     * renders contact names
     * 
     * @param {Array} contactData
     * @return {String}
     */
        /*
    contactRenderer: function(contactData) {
        var contactStore = Tine.Crm.Model.Attender.getAttendeeStore(contactData);
        
        var a = [];
        contactStore.each(function(attender) {
            a.push(Tine.Crm.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Crm.AttendeeGridPanel.prototype, attender.get('user_id'), false, attender));
        });
        
        return a.join("\n");
    },
        */
    
    /**
     * renders container name + color
     * 
     * @param {Array} container
     * @return {String} html
     */
    /*
    containerRenderer: function(container) {
        return this.containerTpl.apply({
            color: Tine.Crm.colorMgr.getColor(this.record).color,
            name: Ext.util.Format.htmlEncode(container && container.name ? container.name : '')
        });
    },
    */
    
    /**
     * renders datetime
     * 
     * @param {Date} dt
     * @return {String}
     */
    /*
    datetimeRenderer: function(dt) {
        return String.format(this.app.i18n._("{0} {1} o'clock"), Tine.Tinebase.common.dateRenderer(dt), dt.format('H:i'));
    },
    */
    
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
        
        this.containerTpl = new Ext.XTemplate(
            '<div class="x-tree-node-el x-tree-node-leaf x-unselectable file">',
                '<img class="x-tree-node-icon" unselectable="on" src="', Ext.BLANK_IMAGE_URL, '">',
                '<span style="color: {color};">&nbsp;&#9673;&nbsp</span>',
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
     * TODO do some styling (borders of first section, font sizes, legends (?), alignments)
     */
    getDefaultPanel: function() {
        
        return new Ext.ux.display.DisplayPanel({
            layout: 'hbox',
            border: false,
            defaults:{
                margins:'0 5 0 0',
                padding: 2,
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
                    // TODO: align: 'right', ?
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
    },
    
    /**
     * fill the piechart stores (calls loadPiechartStore() for all piecharts)
     */
    setPiechartStores: function() {
        
        if (! this.defaultPanel.isVisible()) {
            return;
        }
        
        var storesConfig = [{
            store: this.leadstatePiechartStore,
            jsonData: this.grid.store.proxy.jsonReader.jsonData.totalleadstates,
            definitionsStore: Tine.Crm.LeadState.getStore(),
            definitionsLabel: 'leadstate'
        }, {
            store: this.leadsourcePiechartStore,
            jsonData: this.grid.store.proxy.jsonReader.jsonData.totalleadsources,
            definitionsStore: Tine.Crm.LeadSource.getStore(),
            definitionsLabel: 'leadsource'
        }, {
            store: this.leadtypePiechartStore,
            jsonData: this.grid.store.proxy.jsonReader.jsonData.totalleadtypes,
            definitionsStore: Tine.Crm.LeadType.getStore(),
            definitionsLabel: 'leadtype'
        }];
        
        for (var i = 0; i < storesConfig.length; i++) {
            this.loadPiechartStore(storesConfig[i]);
        }
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
            if (config.jsonData) {
                config.definitionsStore.each(function(definition) {
                    if (config.jsonData[definition.id]) {
                        records.push(new config.store.recordType({
                            id: definition.id,
                            label: definition.get(config.definitionsLabel),
                            total: config.jsonData[definition.id]
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
     * main lead details panel
     * 
     * @return {Ext.ux.display.DisplayPanel}
     */
    getLeadGridDetailsPanel: function() {
        return new Ext.ux.display.DisplayPanel ({
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
                    /*{
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
                        name: 'summary'
                        //fieldLabel: this.app.i18n._('Summary')
                    }, {
                        flex: 1,
                        xtype: 'ux.displayfield',
                        style: 'text-align: right;',
                        name: 'container_id',
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
                        flex: 2,
                        layout: 'ux.display',
                        labelWidth: 60,
                        layoutConfig: {
                            background: 'solid'
                        },
                        items: [{
                            xtype: 'ux.displayfield',
                            name: 'dtstart',
                            fieldLabel: this.app.i18n._('Start Time'),
                            renderer: this.datetimeRenderer.createDelegate(this)
                        }, {
                            xtype: 'ux.displayfield',
                            name: 'dtend',
                            fieldLabel: this.app.i18n._('End Time'),
                            renderer: this.datetimeRenderer.createDelegate(this)
                        }, {
                            xtype: 'ux.displayfield',
                            name: 'location',
                            fieldLabel: this.app.i18n._('Location')
                        }]
                    }, {
                        flex: 2,
                        layout: 'ux.display',
                        labelAlign: 'top',
                        autoScroll: true,
                        layoutConfig: {
                            background: 'solid'
                        },
                        items: [{
                            xtype: 'ux.displayfield',
                            name: 'contact',
                            nl2br: true,
                            fieldLabel: this.app.i18n._('Attendee'),
                            renderer: this.contactRenderer
                        }]
                    }, {
                        flex: 3,
                        layout: 'fit',
                        
                        border: false,
                        items: [{
                            cls: 'x-ux-display-background-border',
                            xtype: 'ux.displaytextarea',
                            name: 'description'
                        }]
                    }]
                }*/]
            }]
        });
    },
    
    /**
     * update lead details panel
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        this.cardPanel.layout.setActiveItem(this.cardPanel.items.getKey(this.leadDetailsPanel));
        
        this.leadDetailsPanel.loadRecord(record);
        
        /*
        // don't mess up record
        var data = {};
        Ext.apply(data, record.data);
        
        data.customer = this.getContactData(record, 'CUSTOMER') || {};
        data.partner = this.getContactData(record, 'PARTNER') || {};
        
        var leadtype = Tine.Crm.LeadType.getStore().getById(data.leadtype_id);
        data.leadtype = leadtype ? leadtype.get('leadtype') : '';
        
        var leadsource = Tine.Crm.LeadSource.getStore().getById(data.leadsource_id);
        data.leadsource = leadsource ? leadsource.get('leadsource') : '';
        
        this.tpl.overwrite(body, data);
         */
    },
    
    /**
     * show default panel
     * 
     * @param {Mixed} body
     */
    showDefault: function(body) {
        
        //console.log('show default');
        this.cardPanel.layout.setActiveItem(this.cardPanel.items.getKey(this.defaultPanel));
        
        // fill piechart stores
        this.setPiechartStores.defer(500, this);

        //console.log(this.leadstatePiechartStore);
    },
    
    /**
     * show template for multiple rows
     * 
     * @param {Ext.grid.RowSelectionModel} sm
     * @param {Mixed} body
     * 
     * TODO add charts here
     */
    showMulti: function(sm, body) {
        //if (this.multiTpl) {
        //    this.multiTpl.overwrite(body);
        //}
    }
    
    // old functions follow
    
    /*
    getContactData: function(lead, type) {
        var data = lead.get('relations');
        
        if( Ext.isArray(data) && data.length > 0) {
            var index = 0;
            
            // get correct relation type from data (contact) array
            while (index < data.length && data[index].type != type) {
                index++;
            }
            if (data[index]) {
                return data[index].related_record;
            }
        }
    },

    initTemplate: function() {
        this.tpl = new Ext.XTemplate(
            '<div class="crm-leadgrid-detailspanel">',
                '<!-- status details -->',
                '<div class="preview-panel preview-panel-left crm-leadgrid-detailspanel-status">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="crm-leadgrid-detailspanel-status-inner preview-panel-left">',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Start'), '</span>{[this.encode(values.start, "date")]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Estimated end'), '</span>{[this.encode(values.end_scheduled, "date")]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Leadtype'), '</span>{[this.encode(values.leadtype)]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Leadsource'), '</span>{[this.encode(values.leadsource)]}<br/>',
                        '<!-- ',
                        '<br />',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Open tasks'), '</span>{values.numtasks}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Tasks status'), '</span>{values.tasksstatushtml}<br/>',
                        '-->',
                    '</div>',
                '</div>',
            
                '<!-- contact details -->',
                '<div class="preview-panel preview-panel-left crm-leadgrid-detailspanel-contacts">',                
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="crm-leadgrid-detailspanel-contact">',
                        '<div class="preview-panel-declaration">', this.il8n._('Customer'), '</div>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Phone'), '</span>{[this.encode(values.customer.tel_work)]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Mobile'), '</span>{[this.encode(values.customer.tel_cell)]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Fax'), '</span>{[this.encode(values.customer.tel_fax)]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('E-Mail'), 
                            '</span>{[this.getMailLink(values.customer.email, ', this.felamimail, ')]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Web') + '</span><a href="{[this.encode(values.customer.url)]}" target="_blank">{[this.encode(values.customer.url, "shorttext")]}</a><br/>',
                    '</div>',
                    '<div class="crm-leadgrid-detailspanel-contact">',
                        '<div class="preview-panel-declaration">' + this.il8n._('Partner') + '</div>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Phone') + '</span>{[this.encode(values.partner.tel_work)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Mobile') + '</span>{[this.encode(values.partner.tel_cell)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Fax') + '</span>{[this.encode(values.partner.tel_fax)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('E-Mail'), 
                            '</span>{[this.getMailLink(values.partner.email, ' + this.felamimail + ')]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Web') + '</span><a href="{[this.encode(values.partner.url)]}" target="_blank">{[this.encode(values.partner.url, "shorttext")]}</a><br/>',
                    '</div>',
                '</div>',

                '<!-- description -->',
                '<div class="preview-panel-description preview-panel-left" ext:qtip="{[this.encode(values.description)]}">',
                    '<div class="bordercorner_gray_1"></div>',
                    '<div class="bordercorner_gray_2"></div>',
                    '<div class="bordercorner_gray_3"></div>',
                    '<div class="bordercorner_gray_4"></div>',
                    '<div class="preview-panel-declaration">' + this.il8n._('Description') + '</div>',
                    '{[this.encode(values.description)]}',
                '</div>',
                '</div>',
                //  '{[this.getTags(values.tags)]}',
            {
                encode: function(value, type, prefix) {
                    //var metrics = Ext.util.TextMetrics.createInstance('previewPanel');
                    if (value) {
                        if (type) {
                            switch (type) {
                                case 'country':
                                    value = Locale.getTranslationData('CountryList', value);
                                    break;
                                case 'longtext':
                                    value = Ext.util.Format.ellipsis(value, 135);
                                    break;
                                case 'mediumtext':
                                    value = Ext.util.Format.ellipsis(value, 30);
                                    break;
                                case 'shorttext':
                                    //console.log(metrics.getWidth(value));
                                    value = Ext.util.Format.ellipsis(value, 18);
                                    break;
                                case 'date' :
                                    value = Tine.Tinebase.common.dateRenderer(value);
                                    break;
                                case 'prefix':
                                    if (prefix) {
                                        value = prefix + value;
                                    }
                                    break;
                                default:
                                    value += type;
                            }                           
                        }
                        value = Ext.util.Format.htmlEncode(value);
                        return Ext.util.Format.nl2br(value);
                    } else {
                        return '';
                    }
                },

                getMailLink: function(email, felamimail) {
                    if (! email) {
                        return '';
                    }
                    
                    var link = (felamimail) ? '#' : 'mailto:' + email;
                    var id = Ext.id() + ':' + email;
                    
                    return '<a href="' + link + '" class="tinebase-email-link" id="' + id + '">'
                        + Ext.util.Format.ellipsis(email, 18); + '</a>';
                }
            }
        );
    },
    
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

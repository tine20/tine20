/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.AddressEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Address Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.AddressEditDialog
 */
Tine.Sales.AddressEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    tbarItems: null,
    evalGrants: false,
    
    windowWidth: 600,
    windowHeight: 430,
    
    /**
     * just update the contract grid panel, no persisten
     * 
     * @type String
     */
    mode: 'local',
    loadRecord: false,
    
    initComponent: function() {
        if (Ext.isString(this.additionalConfig)) {
            Ext.apply(this, Ext.decode(this.additionalConfig));
        }
        
        Tine.Sales.AddressEditDialog.superclass.initComponent.call(this);
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
        
        this.record.set('customer_id', this.fixedFields.get('customer_id'));
        this.record.set('type',        this.fixedFields.get('type').toLowerCase());
        
        Tine.Sales.AddressEditDialog.superclass.onRecordLoad.call(this);
    },
    
    /**
     * executed when record gets updated from form
     * 
     * @private
     */
    onRecordUpdate: function() {
        Tine.Sales.AddressEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('type',        this.fixedFields.get('type').toLowerCase());
        this.record.set('customer_id', this.fixedFields.get('customer_id'));
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
        var formFieldDefaults = {
            xtype:'textfield',
            anchor: '100%',
            labelSeparator: '',
            columnWidth: 1/3
        };
        
        
        var lastRow = [{
            xtype: 'widget-countrycombo',
            name: 'countryname',
            fieldLabel: this.app.i18n._('Country')
        }, {
            name: 'pobox',
            fieldLabel: this.app.i18n._('Postbox')
        }];
        
        if (this.addressType == 'billing') {
            lastRow.push({
                name: 'custom1',
                fieldLabel: this.app.i18n._('Number Debit')
            });
        }
        
        return {
            xtype: 'tabpanel',
            defaults: {
                hideMode: 'offsets'
            },
            border: false,
            plain: true,
            activeTab: 0,
            items: [{
                title: this.app.i18n._('Address'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    xtype: 'fieldset',
                    layout: 'hfit',
                    region: 'center',
                    autoHeight: true,
                    title: this.app.i18n._('Address'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: formFieldDefaults,
                        items: [
                            [{
                                name: 'prefix1',
                                fieldLabel: this.app.i18n._('Prefix')
                            }, {
                                name: 'prefix2',
                                fieldLabel: this.app.i18n._('Additional Prefix')
                            },{
                                name: 'street',
                                fieldLabel: this.app.i18n._('Street')
                            }], [{
                                name: 'postalcode',
                                fieldLabel: this.app.i18n._('Postalcode')
                            }, {
                                name: 'locality',
                                fieldLabel: this.app.i18n._('Locality')
                            }, {
                                name: 'region',
                                fieldLabel: this.app.i18n._('Region')
                            }], lastRow
                        ]
                    }]
                }]
            }]
        };
    }
});

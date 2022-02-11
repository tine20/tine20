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
    
    windowWidth: 700,
    windowHeight: 500,

    displayNotes: true,
    
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
     * returns canonical path part
     * @returns {string}
     */
    getCanonicalPathSegment: function () {
        return [
            this.supr().getCanonicalPathSegment.call(this),
            Ext.util.Format.capitalize(this.addressType)
        ].join(Tine.Tinebase.CanonicalPath.separator);
    },

    
    /**
     * executed when record gets updated from form
     * 
     * @private
     */
    onRecordUpdate: function() {
        Tine.Sales.AddressEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('type',        String(this.fixedFields.get('type')).toLowerCase());
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
            columnWidth: 1/2
        };
        
        
        var items = [
            [{
                name: 'name_shorthand',
                columnWidth: .2,
                fieldLabel: this.app.i18n._('Name shorthand')
            }, {
                xtype: 'widget-keyfieldcombo',
                columnWidth: .2,
                app:   'Sales',
                keyFieldName: 'languagesAvailable',
                fieldLabel: this.app.i18n._('Language'),
                name: 'language',
                requiredGrant: 'editGrant'
            }, {
                name: 'email',
                fieldLabel: this.app.i18n._('Email'),
                columnWidth: .6
            }/*, this.clipboardButton*/],[{
               columnWidth: .045,
               xtype:'button',
               iconCls: 'applyContactData',
               tooltip: Ext.util.Format.htmlEncode(this.app.i18n._('Apply postal address')),
               fieldLabel: '&nbsp;',
               lazyLoading: false,
               listeners: {
                    scope: this,
                    click: function() {
                        Ext.iterate(this.fixedFields.get('parentRecord'), function(property, value) {
                            var split = property.split(/_/);
                            if (split[0] == 'adr') {
                                if (value) {
                                    this.getForm().findField(split[1]).setValue(value);
                                }
                            }
                        }, this);
                    }
               }
            }, {
                columnWidth: .95,
                name: 'name',
                fieldLabel: this.app.i18n._('Name')
            }], [{
                columnWidth: 1,
                name: 'prefix1',
                fieldLabel: this.app.i18n._('Prefix')
            }], [{
                columnWidth: 1,
                name: 'prefix2',
                fieldLabel: this.app.i18n._('Additional Prefix')
            }], [{
                name: 'street',
                fieldLabel: this.app.i18n._('Street')
            }, {
                name: 'pobox',
                fieldLabel: this.app.i18n._('Postbox')
            }], [{
                name: 'postalcode',
                fieldLabel: this.app.i18n._('Postalcode')
            }, {
                name: 'locality',
                fieldLabel: this.app.i18n._('Locality')
            }], [{
                name: 'region',
                fieldLabel: this.app.i18n._('Region')
            }, {
                xtype: 'widget-countrycombo',
                name: 'countryname',
                fieldLabel: this.app.i18n._('Country')
            }]
        ];
        
        if (this.addressType == 'billing') {
            items.push([{
                name: 'custom1',
                fieldLabel: this.app.i18n._('Number Debit')
            }]);
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
                        items: items
                    }]
                }]
            }]
        };
    }
});

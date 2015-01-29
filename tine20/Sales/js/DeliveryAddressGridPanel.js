/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * Address grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.DeliveryAddressGridPanel
 * @extends     Tine.Sales.AddressGridPanel
 * 
 * <p>Delivery Address Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * 
 * Create a new Tine.Sales.DeliveryAddressGridPanel
 */
Tine.Sales.DeliveryAddressGridPanel = Ext.extend(Tine.Sales.AddressGridPanel, {
    /**
     * inits this cmp
     * 
     * @private
     */
    initComponent: function() {
        this.addressType = 'delivery';
        
        // TODO use singular/plural translations here
        this.i18nRecordName  = this.app.i18n._('Delivery Address');
        this.i18nRecordsName = this.app.i18n._('Delivery Addresses');
        
        this.modelConfig = Ext.decode(Ext.encode(this.modelConfig));
        
        this.modelConfig.fields.custom1.label = null;
        
        Tine.Sales.DeliveryAddressGridPanel.superclass.initComponent.call(this);
    }
});

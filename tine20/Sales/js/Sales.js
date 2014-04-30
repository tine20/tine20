/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * address renderer, not a default renderer
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @constructor
 * Constructs mainscreen of the Sales application
 */
Tine.Sales.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    appName: 'Sales',
    activeContentType: 'Product',
    contentTypes: [
        {modelName: 'Product', requiredRight: 'manage_products', singularContainerMode: true},
        {modelName: 'Contract', requiredRight: 'manage_contracts', singularContainerMode: true, genericCtxActions: ['grants']},
        {modelName: 'Customer', requiredRight: 'manage_customers', singularContainerMode: true},
        {modelName: 'Invoice', requiredRight: 'manage_invoices', singularContainerMode: true},
        {modelName: 'CostCenter', requiredRight: 'manage_costcenters', singularContainerMode: true},
        {modelName: 'Division', requiredRight: 'manage_divisions', singularContainerMode: true},
        {modelName: 'OrderConfirmation', requiredRight: 'manage_orderconfirmations', singularContainerMode: true}
    ]
});

Tine.Sales.addToClipboard = function(record, companyName) {
    // this is called either from the edit dialog or from the grid, so we have different record types
    var fieldPrefix = record.data.hasOwnProperty('bic') ? 'adr_' : '';
    
    companyName = companyName ? companyName : (record.get('name') ? record.get('name') : '');

    var lines = companyName + "\n";
    
        lines += (record.get((fieldPrefix + 'prefix1')) ? record.get((fieldPrefix + 'prefix1')) + "\n" : '');
        lines += (record.get((fieldPrefix + 'prefix2')) ? record.get((fieldPrefix + 'prefix2')) + "\n" : '');
        lines += (record.get(fieldPrefix + 'pobox') ? record.get(fieldPrefix + 'pobox') + "\n" : (record.get(fieldPrefix + 'street') ? record.get(fieldPrefix + 'street') + "\n" : ''));
        lines += (record.get((fieldPrefix + 'postalcode')) ? (record.get((fieldPrefix + 'postalcode')) + ' ') : '') + (record.get((fieldPrefix + 'locality')) ? record.get((fieldPrefix + 'locality')) : '');
        
        if (record.get('countryname')) {
            lines += "\n" + record.get('countryname');
        }
    
    var app = Tine.Tinebase.appMgr.get('Sales');
    
    Tine.Sales.CopyAddressDialog.openWindow({winTitle: app.i18n._('Copy address to the clipboard'), app: app, content: lines});
};

/** @param {Tine.Tinebase.data.Record} record
 * @param {String} companyName
 * 
 * @return {String}
 */
Tine.Sales.renderAddress = function(record, companyName) {
    // this is called either from the edit dialog or from the grid, so we have different record types
    var fieldPrefix = record.data.hasOwnProperty('bic') ? 'adr_' : '';
    
    companyName = companyName ? companyName : (record.get('name') ? record.get('name') : '');

    var lines = companyName + "\n";
    
    lines += (record.get((fieldPrefix + 'prefix1')) ? record.get((fieldPrefix + 'prefix1')) + "\n" : '');
    lines += (record.get((fieldPrefix + 'prefix2')) ? record.get((fieldPrefix + 'prefix2')) + "\n" : '');
    lines += (record.get((fieldPrefix + 'pobox')) ? (record.get(fieldPrefix + 'pobox') + "\n") : ((record.get(fieldPrefix + 'street') ? record.get(fieldPrefix + 'street') + "\n" : '')));
    lines += (record.get((fieldPrefix + 'postalcode')) ? (record.get((fieldPrefix + 'postalcode')) + ' ') : '') + (record.get((fieldPrefix + 'locality')) ? record.get((fieldPrefix + 'locality')) : '');
    
    if (record.get('countryname')) {
        lines += "\n" + record.get('countryname');
    }
    
    return lines;
};

/**
 * opens the Copy Address Dialog and adds the rendered address
 * 
 * @param {Tine.Tinebase.data.Record} record
 * @param {String} companyName
 */
Tine.Sales.addToClipboard = function(record, companyName) {
    var app = Tine.Tinebase.appMgr.get('Sales');
    
    Tine.Sales.CopyAddressDialog.openWindow({
        winTitle: 'Copy address to the clipboard', 
        app: app, 
        content: Tine.Sales.renderAddress(record, companyName)
    });
};

Tine.Sales.renderAddressAsLine = function(values) {
    var ret = '';
    var app = Tine.Tinebase.appMgr.get('Sales');
    if (values.customer_id) {
        ret += '<b>' + Ext.util.Format.htmlEncode(values.customer_id.name) + '</b> - ';
    }
    
    ret += Ext.util.Format.htmlEncode((values.postbox ? values.postbox : values.street));
    ret += ', ';
    ret += Ext.util.Format.htmlEncode(values.postalcode);
    ret += ' ';
    ret += Ext.util.Format.htmlEncode(values.locality);
    ret += ' (';
    ret += app.i18n._(values.type)
    
    if (values.type == 'billing') {
        ret += ' - ' + Ext.util.Format.htmlEncode(values.custom1);
    }
    
    ret += ')';
    
    return ret;
};

/**
 * register special renderer for invoice address_id
 */
Tine.widgets.grid.RendererManager.register('Sales', 'Invoice', 'address_id', Tine.Sales.renderAddressAsLine);

/**
 * renders the model of the invoice position
 * 
 * @param {String} value
 * @param {Object} row
 * @param {Tine.Tinebase.data.Record} rec
 * @return {String}
 */
Tine.Sales.renderInvoicePositionModel = function(value, row, rec) {
    if (! value) {
        return '';
    }
    var split = value.split('_Model_');
    var model = Tine[split[0]].Model[split[1]];
    
    return '<span class="tine-recordclass-gridicon ' + model.getMeta('appName') + model.getMeta('modelName') + '">&nbsp;</span>' + model.getRecordName() + ' (' + model.getAppName() + ')';
};

/**
 * register special renderer for the invoice position
 */
Tine.widgets.grid.RendererManager.register('Sales', 'InvoicePosition', 'model', Tine.Sales.renderInvoicePositionModel);


Tine.Sales.renderBillingPoint = function(v) {
    var app = Tine.Tinebase.appMgr.get('Sales');
    return v ? app.i18n._hidden(v) : '';
}

Tine.widgets.grid.RendererManager.register('Sales', 'Contract', 'billing_point', Tine.Sales.renderBillingPoint);

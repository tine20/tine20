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
        {modelName: 'Offer', requiredRight: 'manage_offers', singularContainerMode: true},
        {modelName: 'OrderConfirmation', requiredRight: 'manage_orderconfirmations', singularContainerMode: true}
    ]
});

// rendered sums registry for the invoiceposition grid panel
Tine.Sales.renderedSumsPerMonth = {};

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
    if (values.customer_id && values.customer_id.hasOwnProperty('name')) {
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

/**
 * renders the quantity of the invoice position
 */
Tine.Sales.InvoicePositionQuantityRendererRegistry = function() {
    var renderers = {};
    
    return {
        /**
         * return renderer
         * 
         * @param {String} phpModelName
         * @return {Function}
         */
        get: function(phpModelName, unit) {
            var unit = unit.replace(/\s/, '');
            if (renderers.hasOwnProperty(phpModelName+unit)) {
                return renderers[phpModelName+unit];
            } else {
                // default function
                return function(value, row, rec) {
                    return value;
                }
            }
        },
        
        /**
         * register renderer
         * 
         * @param {String} phpModelName
         * @param {Function} func
         */
        register: function(phpModelName, unit, func) {
            var unit = unit.replace(/\s/, '');
            renderers[phpModelName+unit] = func;
        },
        
        /**
         * check if a renderer is explicitly registered
         * 
         * @param {String} phpModelName
         * @return {Boolean}
         */
        has: function(phpModelName, unit) {
            var unit = unit.replace(/\s/, '');
            return renderers.hasOwnProperty(phpModelName+unit);
        }
    }
}();

/**
 * renders the unit of the invoice position
 * 
 * @param {String} value
 * @param {Object} row
 * @param {Tine.Tinebase.data.Record} rec
 * @return {String}
 */
Tine.Sales.renderInvoicePositionUnit = function(value, row, rec) {
    
    if (! value) {
        return '';
    }
    
    var model = rec.get('model');
    var split = model.split('_Model_');
    
    var app = Tine.Tinebase.appMgr.get(split[0]);
    
    return app.i18n._(value);
};
/**
 * renders the unit of the invoice position
 * @param {} value
 * @param {} row
 * @param {} rec
 * @return {}
 */
Tine.Sales.renderInvoicePositionQuantity = function(value, row, rec) {
    var model = rec.data.model;
    if (Tine.Sales.InvoicePositionQuantityRendererRegistry.has(model, rec.data.unit)) {
        var renderer = Tine.Sales.InvoicePositionQuantityRendererRegistry.get(model, rec.data.unit);
        return renderer(value, row, rec);
    } else {
        return value;
    }
};

/**
 * register special renderer for the invoice position
 */
Tine.widgets.grid.RendererManager.register('Sales', 'InvoicePosition', 'unit', Tine.Sales.renderInvoicePositionUnit);
Tine.widgets.grid.RendererManager.register('Sales', 'InvoicePosition', 'quantity', Tine.Sales.renderInvoicePositionQuantity);


Tine.Sales.renderBillingPoint = function(v) {
    var app = Tine.Tinebase.appMgr.get('Sales');
    return v ? app.i18n._hidden(v) : '';
}

Tine.widgets.grid.RendererManager.register('Sales', 'Contract', 'billing_point', Tine.Sales.renderBillingPoint);

Tine.Sales.renderCostCenter = function(value, row, rec) {
    if (Ext.isObject(value)) {
        return value.number + ' - ' + value.remark;
    }
    
    return '';
};

Tine.widgets.grid.RendererManager.register('Sales', 'Invoice', 'costcenter_id', Tine.Sales.renderCostCenter);

/**
 * allows all accountables to register (needed for accountable combo box)
 */
Tine.Sales.AccountableRegistry = function() {
    var accountables = {};
    
    return {
        /**
         * return all accountables as array
         * 
         * @return {Array}
         */
        getArray: function() {
            var ar = [];
            Ext.iterate(accountables, function(key, value) {
                ar.push(value);
            });
            
            return ar;
        },
        
        /**
         * register accountable
         * 
         * @param {String} appName
         * @param {String} modelName
         */
        register: function(appName, modelName) {
            var key = appName + modelName;
            if (! accountables.hasOwnProperty(key)) {
                accountables[key] = {appName: appName, modelName: modelName};
            }
        },
        
        /**
         * check if a renderer is explicitly registered
         * 
         * @param {String} appName
         * @param {String} modelName
         * @return {Boolean}
         */
        has: function(appName, modelName) {
            var key = appName + modelName;
            return accountables.hasOwnProperty(key);
        }
    }
}();

Tine.Sales.AccountableRegistry.register('Sales', 'Product');


Tine.Sales.renderAccountable = function(values) {
    if (Ext.isEmpty(values)) {
        return '';
    }
    var split = values.split('_Model_');
    var ret = '';
    var app = Tine.Tinebase.appMgr.get(split[0]);
    
    return app.i18n._(split[0] + split[1]);
};

/**
 * register special renderer for invoice address_id
 */
Tine.widgets.grid.RendererManager.register('Sales', 'Product', 'accountable', Tine.Sales.renderAccountable);

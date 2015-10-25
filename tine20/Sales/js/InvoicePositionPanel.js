/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * InvoicePosition panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.InvoicePositionPanel
 * @extends     Ext.Panel
 * 
 * <p>InvoicePosition Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.InvoiceGridPanel
 */
Tine.Sales.InvoicePositionPanel = Ext.extend(Ext.Panel, {
    /**
     * holds positions from the edit dialog records positions property
     * 
     * @type array
     */
    positions: null,
    
    /**
     * holds the id of the invoice
     * 
     * @type string
     */
    invoiceId: null,
    
    positionsPerAccountable: null,
    
    app: null,
    
    autoScroll: true,
    
    /**
     * holds all accountables needed
     * 
     * @type {Array}
     */
    accountables: null,
    /**
     * 
     * @type {Array}
     */
    modelsOfAccountable: null,
    sumsPerAccountable: null,
    
    initComponent: function(deferred) {
        
        // defer this, otherwise renderers won't have been registered, yet.
        if (! deferred) {
            this.initComponent.defer(150, this, [true]);
            return false;
        }

        this.accountables = [];
        this.positionsPerAccountable = {};
        this.sumsPerAccountable = {};
        this.modelsOfAccountable = [];
        
        Tine.Sales.InvoicePositionPanel.superclass.initComponent.call(this);
        
        this.initStoreAndPanels();
    },
    
    /**
     * creates a store containing all positions
     */
    initStoreAndPanels: function() {
        // interrupt process flow until we got the positions from the editdialog set
        if (! this.positions) {
            this.initStoreAndPanels.defer(50, this);
            return;
        }
        
        if (! Ext.isArray(this.positions)) {
            Tine.log.error('No Invoice Positions given:');
            Tine.log.error(this.positions);
            return;
        }
        
        for (var index = 0; index < this.positions.length; index++) {
            var key = this.positions[index].model + this.positions[index].unit;
            var key = key.replace(/\s/g, "");
            if (this.accountables.indexOf(key) == -1) {
                this.accountables.push(key);
                this.modelsOfAccountable[key] = this.positions[index].model;
                this.positionsPerAccountable[key] = [];
                this.sumsPerAccountable[key] = 0.0;
            }
            
            this.positionsPerAccountable[key].push(this.positions[index]);
            this.sumsPerAccountable[key] += parseFloat(this.positions[index].quantity);
        }
        
        // prepeare rendered sums registry
        Tine.Sales.renderedSumsPerMonth[this.invoiceId] = {};
        
        // not needed anymore
        this.positions = null;
        
        for (var index = 0; index < this.accountables.length; index++) {
            
            var mypositions = this.positionsPerAccountable[this.accountables[index]];
            
            var pseudoRecord     = new Tine.Sales.Model.Invoice({
                positions: mypositions,
                id: this.invoiceId
            });
            
            // find out months of the accountable type to decide if grouping is required
            var mymonths = [];
            var mysumspermonth = {};
            for (var index2 = 0; index2 < mypositions.length; index2++) {
                if (! mysumspermonth['month-' + mypositions[index2].month]) {
                    mysumspermonth['month-' + mypositions[index2].month] = 0.0;
                }
                
                mysumspermonth['month-' + mypositions[index2].month] += parseFloat(mypositions[index2].quantity);
                
                if (mymonths.indexOf(mypositions[index2].month) == -1) {
                    mymonths.push(mypositions[index2].month);
                }
            }
            var pseudoRecord2 = {data: {unit: mypositions[0]['unit'], model: this.modelsOfAccountable[this.accountables[index]]}};
            var renderedSumsPerMonth = {};
            
            Ext.iterate(mysumspermonth, function(month, sum) {
                renderedSumsPerMonth[month] = Tine.Sales.renderInvoicePositionQuantity(mysumspermonth[month], null, pseudoRecord2);
            });
            
            Tine.Sales.renderedSumsPerMonth[this.invoiceId][this.accountables[index]] = renderedSumsPerMonth;
            
            var pseudoEditDialog = {record: pseudoRecord, app: this.app};
            
            var split = this.modelsOfAccountable[this.accountables[index]].split('_Model_');
            if (Tine[split[0]]) {
                var model = Tine[split[0]].Model[split[1]];
                var accountableApplication = Tine.Tinebase.appMgr.get(split[0]);
                var renderedSum = Tine.Sales.renderInvoicePositionQuantity(this.sumsPerAccountable[this.accountables[index]], null, pseudoRecord2);
                var grid = new Tine.Sales.InvoicePositionGridPanel({
                    collapsible: true,
                    collapsed: true,
                    editDialog: pseudoEditDialog,
                    height: (mymonths.length > 1) ? 350 : 250, // if grid will be grouped, make it bigger
                    title: accountableApplication.i18n._(model.getRecordsName()) +
                    ' (' + mypositions.length + ')' + ' - ' +
                    accountableApplication.i18n._(mypositions[0]['unit']) + ': ' + renderedSum,
                    app: this.app,
                    i18nModelsName: accountableApplication.i18n._(model.getRecordsName()),
                    i18nUnitName: accountableApplication.i18n._(mypositions[0]['unit']),
                    accountable: this.modelsOfAccountable[this.accountables[index]],
                    accountableApplication: accountableApplication,
                    invoiceId: this.invoiceId,
                    renderedSum: renderedSum,
                    renderedSumsPerMonth: renderedSumsPerMonth,
                    unitName: mypositions[0]['unit'],
                    groupField: (mymonths.length > 1) ? 'month' : null
                });

                this.add(grid);
            } else {
                Tine.log.warn('Application for accountable ' + this.accountables[index] + ' not found!');
            }
        }
    }
});

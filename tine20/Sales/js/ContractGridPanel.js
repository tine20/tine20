/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * Contract grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ContractGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Contract Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.ContractGridPanel
 */
Tine.Sales.ContractGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    multipleEdit: true,
    initComponent: function() {
        Tine.Sales.ContractGridPanel.superclass.initComponent.call(this);
        this.action_addInNewWindow.actionUpdater = function() {
            var defaultContainer = this.app.getRegistry().get('defaultContainer');
            this.action_addInNewWindow.setDisabled(! defaultContainer.account_grants[this.action_addInNewWindow.initialConfig.requiredGrant]);
        }
    },
    
    /**
     * @todo: make this generally available (here its more general: Tine.HumanResources.EmployeeGridPanel)
     * 
     * returns additional toobar items
     * 
     * @return {Array} of Ext.Action
     */
    getActionToolbarItems: function() {
        this.actions_bill = new Ext.Action({
            text: this.app.i18n._('Bill Contract'),
            iconCls: 'action_bill',
            scope: this,
            disabled: true,
            allowMultiple: false,
            handler: this.onBillContract,
            actionUpdater: function(action, grants, records) {
                if (records.length == 1) {
                    action.enable();
                } else {
                    action.disable();
                }
            }
        });

        var billButton = Ext.apply(new Ext.Button(this.actions_bill), {
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top'
        });
        
        var additionalActions = [this.actions_bill];
        this.actionUpdater.addActions(additionalActions);
        
        return [billButton];
    },
    
    /**
     * is called when the component is rendered
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        this.billMask = new Ext.LoadMask(ct, {msg: this.app.i18n._('Billing Contract...')});
        Tine.Sales.ContractGridPanel.superclass.onRender.call(this, ct, position);
    },
    
    /**
     * 
     */
    onBillContract: function() {
        var rows = this.getGrid().getSelectionModel().getSelections();
        
        if (rows.length != 1) {
            return;
        }
        
        var window = Tine.Sales.BillingDateDialog.openWindow({
            winTitle: String.format(this.app.i18n._('Bill Contract "{0} - {1}"'), rows[0].data.number, rows[0].data.title),
            panelDialog: this.app.i18n._('Select the date to generate the bill for'),
            contractId: rows[0].id
        });
        
        window.on('submit', this.doBillContract, this);
    },
    
    /**
     * 
     * @param {} date
     * @param {} contractId
     */
    doBillContract: function(date, contractId) {
        var that = this;
        
        this.billMask.show();
        
        var req = Ext.Ajax.request({
            url : 'index.php',
            params : { 
                method: 'Sales.billContract', 
                id:     contractId,
                date:   date
            },
            success : function(result, request) {
                that.getGrid().store.reload();
                that.billMask.hide();
            },
            failure : function(exception) {
                that.billMask.hide();
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            },
            scope: that
        });
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.actions_bill
            ];
        
        return items;
    }
});

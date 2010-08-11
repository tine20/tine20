/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Felamimail');

/**
 * @namespace Tine.Felamimail
 * @class     Tine.Felamimail.RulesGridPanel
 * @extends   Tine.widgets.grid.GridPanel
 * Rules Grid Panel <br>
 * TODO         make buttons + save work
 * TODO         remove paging toolbar?
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */
Tine.Felamimail.RulesGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * @cfg {Tine.Felamimail.Model.Account}
     */
    account: null,
    
    // model generics
    recordClass: Tine.Felamimail.Model.Rule,
    recordProxy: Tine.Felamimail.rulesBackend,
    
    // grid specific
    defaultSortInfo: {field: 'id', dir: 'ASC'},
    
    // not yet
    evalGrants: false,
    
    //newRecordIcon: 'cal-resource',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        
        this.initColumns();
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * init columns
     */
    initColumns: function() {
        this.gridConfig = {};
        
        this.gridConfig.columns = [{
            id: 'id',
            header: this.app.i18n._("ID"),
            width: 40,
            sortable: false,
            dataIndex: 'id'
        }, {
            id: 'conditions',
            header: this.app.i18n._("Conditions"),
            width: 200,
            sortable: false,
            dataIndex: 'conditions',
            renderer: this.conditionsRenderer
        }, {
            id: 'action',
            header: this.app.i18n._("Action"),
            width: 120,
            sortable: false,
            dataIndex: 'action',
            renderer: this.actionRenderer
        }, new Ext.ux.grid.CheckColumn({
            header: this.app.i18n._('Enabled'),
            dataIndex: 'enabled',
            width: 55
        })];
    },
    
    /**
     * init layout
     */
    initLayout: function() {
        this.supr().initLayout.call(this);
        
        this.items.push({
            region : 'north',
            height : 55,
            border : false,
            items  : this.actionToolbar
        });
    },
    
    /**
     * preform the initial load of grid data
     */
    initialLoad: function() {
        this.store.load.defer(10, this.store, [
            typeof this.autoLoad == 'object' ?
                this.autoLoad : undefined]);
    },
    
    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        Tine.Felamimail.RulesGridPanel.superclass.onStoreBeforeload.call(this, store, options);
        
        options.params.filter = this.account.id;
    },
    
    /**
     * action renderer
     * 
     * @param {Object} value
     * @return {String}
     */
    actionRenderer: function(value) {
        return value.type + ' ' + value.argument;
    },
    
    /**
     * conditions renderer
     * 
     * @param {Object} value
     * @return {String}
     */
    conditionsRenderer: function(value) {
        var result = '';
        
        // show only first condition
        if (value.length > 0) {
            var condition = value[0]; 
            result = '[' + condition.test + '] ' + condition.header + ' ' + condition.comperator + ' "' + condition.key + '"';
        }
        
        return result;
    },
    
    /**
     * generic edit in new window handler
     */
    onEditInNewWindow: function(button, event) {
        
        Tine.log.info('not yet implemented');
        
        /*
        var record; 
        if (button.actionType == 'edit') {
            if (! this.action_editInNewWindow || this.action_editInNewWindow.isDisabled()) {
                // if edit action is disabled or not available, we also don't open a new window
                return false;
            }
            var selectedRows = this.grid.getSelectionModel().getSelections();
            record = selectedRows[0];
            
        } else if (button.actionType == 'copy') {
            var selectedRows = this.grid.getSelectionModel().getSelections();
            record = this.copyRecord(selectedRows[0].data);

        } else {
            record = new this.recordClass(this.recordClass.getDefaultData(), 0);
        }
        
        var popupWindow = Tine[this.app.appName][this.recordClass.getMeta('modelName') + 'EditDialog'].openWindow({
            record: record,
            listeners: {
                scope: this,
                'update': function(record) {
                    // TODO add to store
                }
            }
        });
        */
    },
    
    /**
     * generic delete handler
     */
    onDeleteRecords: function(btn, e) {
        var sm = this.grid.getSelectionModel();
        var records = sm.getSelections();
        Ext.each(records, function(record) {
            this.store.remove(record);
        });
    }
});

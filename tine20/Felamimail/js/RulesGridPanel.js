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
 * TODO         make it possible to determine order of rules
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
    storeRemoteSort: false,
    
    // not yet
    evalGrants: false,
    usePagingToolbar: false,
    
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
        var cb = new Ext.ux.grid.CheckColumn({
            header: this.app.i18n._('Enabled'),
            dataIndex: 'enabled',
            width: 55
        });
        
        this.gridConfig.columns = [{
            id: 'id',
            header: this.app.i18n._("ID"),
            width: 40,
            sortable: true,
            dataIndex: 'id'
        }, {
            id: 'conditions',
            header: this.app.i18n._("Conditions"),
            width: 250,
            sortable: false,
            dataIndex: 'conditions',
            renderer: this.conditionsRenderer
        }, {
            id: 'action',
            header: this.app.i18n._("Action type"),
            width: 100,
            sortable: false,
            dataIndex: 'action_type'
            //renderer: this.actionRenderer
        }, {
            id: 'action',
            header: this.app.i18n._("Action argument"),
            width: 100,
            sortable: false,
            dataIndex: 'action_argument'
            //renderer: this.actionRenderer
        }, cb];
        
        this.gridConfig.plugins = [cb]; 
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
     * 
     * TODO activate again? or remove ...
     */
    actionRenderer: function(value) {
        return (value) ? value.type + ' ' + value.argument : '';
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
        if (value && value.length > 0) {
            var condition = value[0]; 
            result = '[' + condition.test + '] ' + condition.header + ' ' + condition.comperator + ' "' + condition.key + '"';
        }
        
        return result;
    },
    
    /**
     * on update after edit
     * 
     * @param {String} encodedRecordData (json encoded)
     * 
     * TODO there must be a simpler way to do this!
     */
    onUpdateRecord: function(encodedRecordData) {
        var recordData = Ext.util.JSON.decode(encodedRecordData);
        if (! recordData.id) {
            recordData.id = this.store.getCount()+1;
        } else {
            this.store.remove(this.store.getById(recordData.id));
        }
        
        Tine.log.debug(recordData);
        
        this.store.loadData({
            totalcount: 1,
            results: [recordData]
        }, true);
        
        // TODO it should be done like this:
        /*
        var recordData = Ext.copyTo({}, folderData, Tine.Felamimail.Model.Folder.getFieldNames());
        var newRecord = Tine.Felamimail.folderBackend.recordReader({responseText: Ext.util.JSON.encode(recordData)});
        this.folderStore.add([newRecord]);
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

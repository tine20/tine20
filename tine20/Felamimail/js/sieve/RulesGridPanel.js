/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Felamimail.sieve');

/**
 * @namespace Tine.Felamimail
 * @class     Tine.Felamimail.sieve.RulesGridPanel
 * @extends   Tine.widgets.grid.GridPanel
 * Rules Grid Panel <br>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */
Tine.Felamimail.sieve.RulesGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
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
    splitAddButton: false,
    
    newRecordIcon: 'action_new_rule',
    editDialogClass: Tine.Felamimail.sieve.RuleEditDialog,
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.initColumns();
        
        this.editDialogConfig = {
            account: this.account
        };
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * Return CSS class to apply to rows depending on enabled status
     * 
     * @param {Tine.Felamimail.Model.Rule} record
     * @param {Integer} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var className = '';
        
        if (! record.get('enabled')) {
            className += ' felamimail-sieverule-disabled';
        }
        
        return className;
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {
        this.action_moveup = new Ext.Action({
            text: this.app.i18n._('Move up'),
            handler: this.onMoveRecord.createDelegate(this, ['up']),
            scope: this,
            iconCls: 'action_move_up'
        });

        this.action_movedown = new Ext.Action({
            text: this.app.i18n._('Move down'),
            handler: this.onMoveRecord.createDelegate(this, ['down']),
            scope: this,
            iconCls: 'action_move_down'
        });

        this.action_enable = new Ext.Action({
            text: this.app.i18n._('Enable'),
            handler: this.onEnableDisable.createDelegate(this, [true]),
            scope: this,
            iconCls: 'action_enable'
        });

        this.action_disable = new Ext.Action({
            text: this.app.i18n._('Disable'),
            handler: this.onEnableDisable.createDelegate(this, [false]),
            scope: this,
            iconCls: 'action_disable'
        });
        
        this.supr().initActions.call(this);
    },
    
    /**
     * enable / disable rule
     * 
     * @param {Boolean} state
     */
    onEnableDisable: function(state) {
        var selectedRows = this.grid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; i++) {
            selectedRows[i].set('enabled', state);
        }
    },
    
    /**
     * move record up or down
     * 
     * @param {String} dir (up|down)
     */
    onMoveRecord: function(dir) {
        var sm = this.grid.getSelectionModel();
            
        if (sm.getCount() == 1) {
            var selectedRows = sm.getSelections();
            record = selectedRows[0];
            
            // get next/prev record
            var index = this.store.indexOf(record),
                switchRecordIndex = (dir == 'down') ? index + 1 : index - 1,
                switchRecord = this.store.getAt(switchRecordIndex);
            
            if (switchRecord) {
                // switch ids and resort store
                var oldId = record.id;
                    switchId = switchRecord.id;

                record.set('id', Ext.id());
                record.id = Ext.id();
                switchRecord.set('id', oldId);
                switchRecord.id = oldId;
                record.set('id', switchId);
                record.id = switchId;
                
                this.store.commitChanges();
                this.store.sort('id', 'ASC');
                sm.selectRecords([record]);
            }
        }
    },

    /**
     * add custom items to action toolbar
     * 
     * @return {Object}
     */
    getActionToolbarItems: function() {
        return [
            {
                xtype: 'buttongroup',
                columns: 1,
                frame: false,
                items: [
                    this.action_moveup,
                    this.action_movedown
                ]
            }
        ];
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.action_moveup,
            this.action_movedown,
            '-',
            this.action_enable,
            this.action_disable
        ];
        
        return items;
    },
    
    /**
     * init columns
     */
    initColumns: function() {
        this.gridConfig.columns = [
        {
            id: 'id',
            header: this.app.i18n._("ID"),
            width: 40,
            sortable: false,
            dataIndex: 'id',
            hidden: true
        }, {
            id: 'conditions',
            header: this.app.i18n._("Conditions"),
            width: 200,
            sortable: false,
            dataIndex: 'conditions',
            scope: this,
            renderer: this.conditionsRenderer
        }, {
            id: 'action',
            header: this.app.i18n._("Action"),
            width: 250,
            sortable: false,
            dataIndex: 'action_type',
            scope: this,
            renderer: this.actionRenderer
        }];
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
        Tine.Felamimail.sieve.RulesGridPanel.superclass.onStoreBeforeload.call(this, store, options);
        
        options.params.filter = this.account.id;
    },
    
    /**
     * action renderer
     * 
     * @param {Object} type
     * @param {Object} metadata
     * @param {Object} record
     * @return {String}
     */
    actionRenderer: function(type, metadata, record) {
        var types = Tine.Felamimail.sieve.RuleEditDialog.getActionTypes(this.app),
            result = type;
        
        for (i=0; i < types.length; i++) {
            if (types[i][0] == type) {
                result = types[i][1];
            }
        }
        
        if (record.get('action_argument') && record.get('action_argument') != '') {
            result += ' ' + record.get('action_argument');
        }
            
        return Ext.util.Format.htmlEncode(result);
    },

    /**
     * conditions renderer
     * 
     * @param {Object} value
     * @return {String}
     * 
     * TODO show more conditions?
     */
    conditionsRenderer: function(value) {
        var result = '';
        
        // show only first condition
        if (value && value.length > 0) {
            var condition = value[0];
            
            // get header/comperator translation
            var filterModels = Tine.Felamimail.sieve.RuleConditionsPanel.getFilterModel(this.app),
                header, 
                comperator, 
                found = false;
            Ext.each(filterModels, function(filterModel) {
                if (condition.header == filterModel.field) {
                    header = filterModel.label;
                    if (condition.header == 'size') {
                        comperator = (condition.comperator == 'over') ? _('is greater than') : _('is less than');
                    } else {
                        comperator = _(condition.comperator);
                    }
                    found = true;
                }
            }, this);
            
            if (found === true) {
                result = header + ' ' + comperator + ' "' + condition.key + '"';
            } else {
                result = (condition.comperator == 'contains') ? this.app.i18n._('Header "{0}" contains "{1}"') : this.app.i18n._('Header "{0}" matches "{1}"');
                result = String.format(result, condition.header, condition.key);
            }
        }
        
        return Ext.util.Format.htmlEncode(result);
    },
    
    /**
     * on update after edit
     * 
     * @param {String} encodedRecordData (json encoded)
     */
    onUpdateRecord: function(encodedRecordData) {
        var newRecord = Tine.Felamimail.rulesBackend.recordReader({responseText: encodedRecordData});
        
        if (! newRecord.id) {
            var lastRecord = null,
                nextId = null;
            do {
                // get next free id
                lastRecord = this.store.getAt(this.store.getCount()-1);
                nextId = (lastRecord) ? (parseInt(lastRecord.id, 10) + 1).toString() : '1';
            } while (this.store.getById(newRecord.id));
            
            newRecord.set('id', nextId);
            newRecord.id = nextId;
        } else {
            this.store.remove(this.store.getById(newRecord.id));
        }
        
        this.store.addSorted(newRecord);
        
        // some eyecandy
        var row = this.getView().getRow(this.store.indexOf(newRecord));
        Ext.fly(row).highlight();
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

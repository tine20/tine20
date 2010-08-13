/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */

Ext.ns('Tine.widgets.account');

/**
 * Account Picker GridPanel
 * 
 * @namespace   Tine.widgets.account
 * @class       Tine.widgets.account.PickerGridPanel
 * @extends     Tine.widgets.grid.PickerGridPanel
 * 
 * <p>Account Picker GridPanel</p>
 * <p><pre>
 * TODO         use selectAction config?
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor Create a new Tine.widgets.account.PickerGridPanel
 */
Tine.widgets.account.PickerGridPanel = Ext.extend(Tine.widgets.grid.PickerGridPanel, {
    /**
     * @cfg {String} one of 'user', 'group', 'both'
     * selectType
     */
    selectType: 'user',
    
    /**
     * @cfg{String} selectTypeDefault 'user' or 'group' defines which accountType is selected when  {selectType} is true
     */
    selectTypeDefault: 'user',
    
    /**
     * @cfg {Ext.Action}
     * selectAction
     */
    //selectAction: false,
    
    /**
     * @cfg {bool}
     * add 'anyone' selection if selectType == 'both'
     */
    selectAnyone: true,

    /**
     * get only users with defined status (enabled, disabled, expired)
     * get all -> 'enabled expired disabled'
     * 
     * @type String
     * @property userStatus
     */
    userStatus: 'enabled',
    
    /**
     * @cfg {bool} have the record account properties an account prefix?
     */
    hasAccountPrefix: false,
    
    /**
     * @cfg {String} recordPrefix
     */
    recordPrefix: '',

    /**
     * grid config
     * @private
     */
    autoExpandColumn: 'name',
    
    /**
     * @private
     */
    initComponent: function() {
        this.recordPrefix = (this.hasAccountPrefix) ? 'account_' : '';
        this.recordClass = (this.recordClass !== null) ? this.recordClass : Tine.Tinebase.Model.Account;
        
        Tine.widgets.account.PickerGridPanel.superclass.initComponent.call(this);

        this.store.sort(this.recordPrefix + 'name');
    },

    /**
     * init actions and toolbars
     */
    initActionsAndToolbars: function() {
        
        Tine.widgets.account.PickerGridPanel.superclass.initActionsAndToolbars.call(this);
        
        this.accountTypeSelector = this.getAccountTypeSelector();
        this.contactSearchCombo = this.getContactSearchCombo();
        this.groupSearchCombo = this.getGroupSearchCombo();
        
        var items = [];
        switch (this.selectType) {
            case 'both':
                items = items.concat([this.contactSearchCombo, this.groupSearchCombo]);
                if (this.selectTypeDefault == 'user') {
                    this.groupSearchCombo.hide();
                } else {
                    this.contactSearchCombo.hide();
                }
                break;
            case 'user':
                items = this.contactSearchCombo;
                break;
            case 'group':
                items = this.groupSearchCombo;
                break;
        }
        
        this.comboPanel = new Ext.Panel({
            layout: 'hfit',
            border: false,
            items: items,
            columnWidth: 1
        });
        
        this.tbar = new Ext.Toolbar({
            items: [
                this.accountTypeSelector,
                this.comboPanel
            ],
            layout: 'column'
        });
    },
    
    /**
     * @return {Ext.Action}
     */
    getAccountTypeSelector: function() {
        // define actions
        var userActionCfg = {
            text: _('Search User'),
            scope: this,
            iconCls: 'tinebase-accounttype-user',
            handler: this.onSwitchCombo.createDelegate(this, ['contact', 'tinebase-accounttype-user'])
        };
        var groupActionCfg = {
            text: _('Search Group'),
            scope: this,
            iconCls: 'tinebase-accounttype-group',
            handler: this.onSwitchCombo.createDelegate(this, ['group', 'tinebase-accounttype-group'])
        };
        var anyoneActionCfg = {
            text: _('Add Anyone'),
            scope: this,
            newRecordClass: this.recordClass,
            iconCls: 'tinebase-accounttype-addanyone',
            handler: function() {
                // add anyone
                var recordData = {};
                recordData[this.recordPrefix + 'type'] = 'anyone';
                recordData[this.recordPrefix + 'name'] = _('Anyone');
                recordData[this.recordPrefix + 'id'] = 0;
                var record = new this.recordClass(recordData, 0);
                
                // check if already in
                if (! this.store.getById(record.id)) {
                    this.store.add([record]);
                }
            }
        };
        
        // set items
        var items = [];
        switch (this.selectType) {
            case 'both':
                items = items.concat([userActionCfg, groupActionCfg]);
                if (this.selectAnyone) {
                    items.push(anyoneActionCfg);
                }
                break;
            case 'user':
                items = userActionCfg;
                break;
            case 'group':
                items = groupActionCfg;
                break;
        }
        
        // create action
        return new Ext.Action({
            width: 20,
            text: '',
            disabled: false,
            iconCls: (this.selectTypeDefault == 'user') ? 'tinebase-accounttype-user' : 'tinebase-accounttype-group',
            menu: new Ext.menu.Menu({
                items: items
            }),
            scope: this
        });
    },

    /**
     * @return {Tine.Addressbook.SearchCombo}
     */
    getContactSearchCombo: function() {
        return new Tine.Addressbook.SearchCombo({
            accountsStore: this.store,
            emptyText: _('Search for users ...'),
            newRecordClass: this.recordClass,
            recordPrefix: this.recordPrefix,
            internalContactsOnly: true,
            additionalFilters: [{field: 'user_status', operator: 'equals', value: this.userStatus}],
            onSelect: this.onAddRecordFromCombo
        })
    },
    
    /**
     * @return {Tine.Tinebase.widgets.form.RecordPickerComboBox}
     */
    getGroupSearchCombo: function() {
        return new Tine.Tinebase.widgets.form.RecordPickerComboBox({
            //anchor: '100%',
            accountsStore: this.store,
            blurOnSelect: true,
            recordClass: Tine.Tinebase.Model.Group,
            newRecordClass: this.recordClass,
            recordPrefix: this.recordPrefix,
            emptyText: _('Search for groups ...'),
            onSelect: this.onAddRecordFromCombo
        });        
    },
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns:  [
                //{id: 'type', header: '',        dataIndex: this.recordPrefix + 'type', width: 35, renderer: Tine.Tinebase.common.accountTypeRenderer},
                {id: 'name', header: _('Name'), dataIndex: this.recordPrefix + 'name', renderer: Tine.Tinebase.common.accountRenderer}
            ].concat(this.configColumns)
        });
    },
    
    /**
     * @param {Record} recordToAdd
     * 
     * TODO make reset work correctly -> show emptyText again
     */
    onAddRecordFromCombo: function(recordToAdd) {
        var recordData = {};
        
        if (recordToAdd.data.account_id) {
            // user account record
            recordData[this.recordPrefix + 'id'] = recordToAdd.data.account_id;
            recordData[this.recordPrefix + 'type'] = 'user';
            recordData[this.recordPrefix + 'name'] = recordToAdd.data.n_fileas;
            recordData[this.recordPrefix + 'data'] = recordToAdd.data;
            var record = new this.newRecordClass(recordData, recordToAdd.data.account_id);
            
        } else if (recordToAdd.data.name) {
            // group account
            recordData[this.recordPrefix + 'id'] = recordToAdd.id;
            recordData[this.recordPrefix + 'type'] = 'group';
            recordData[this.recordPrefix + 'name'] = recordToAdd.data.name;
            recordData[this.recordPrefix + 'data'] = recordToAdd.data;
            var record = new this.newRecordClass(recordData, recordToAdd.id);
        } 
        
        // check if already in
        if (! this.accountsStore.getById(record.id)) {
            this.accountsStore.add([record]);
        }
        this.collapse();
        this.clearValue();
        this.reset();
    },
    
    /**
     * 
     * @param {String} show
     * @param {String} iconCls
     * 
     * TODO fix width hack (extjs bug?)
     */
    onSwitchCombo: function(show, iconCls) {
        var showCombo = (show == 'contact') ? this.contactSearchCombo : this.groupSearchCombo;
        var hideCombo = (show == 'contact') ? this.groupSearchCombo : this.contactSearchCombo;
        
        if (! showCombo.isVisible()) {
            var width = hideCombo.getWidth();
            
            hideCombo.hide();
            showCombo.show();
            
            // adjust width
            showCombo.setWidth(width - 1);
            showCombo.setWidth(showCombo.getWidth() + 1);
            
            this.accountTypeSelector.setIconClass(iconCls);
        }
    }
});


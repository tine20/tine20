/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext, Tine*/

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
     * @cfg {bool}
     * show hidden (user) contacts / (group) lists
     */
    showHidden: false,

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
    initComponent: function () {
        this.recordPrefix = (this.hasAccountPrefix) ? 'account_' : '';
        
        this.recordClass = this.recordClass || Tine.Tinebase.Model.Account;
        this.groupRecordClass = this.groupRecordClass || Tine.Addressbook.Model.List;
        
        Tine.widgets.account.PickerGridPanel.superclass.initComponent.call(this);

        this.store.sort(this.recordPrefix + 'name');
    },

    /**
     * init top toolbar
     */
    initTbar: function() {
        this.accountTypeSelector = this.getAccountTypeSelector();
        this.contactSearchCombo = this.getContactSearchCombo();
        this.groupSearchCombo = this.getGroupSearchCombo();
        
        var items = [];
        switch (this.selectType) 
        {
        case 'both':
            items = items.concat([this.contactSearchCombo, this.groupSearchCombo]);
            if (this.selectTypeDefault === 'user') {
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
     * define actions
     * 
     * @return {Ext.Action}
     */
    getAccountTypeSelector: function () {
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
            iconCls: 'tinebase-accounttype-addanyone',
            handler: this.onAddAnyone
        };
        
        // set items
        var items = [];
        switch (this.selectType) 
        {
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
            iconCls: (this.selectTypeDefault === 'user') ? 'tinebase-accounttype-user' : 'tinebase-accounttype-group',
            menu: new Ext.menu.Menu({
                items: items
            }),
            scope: this
        });
    },
    
    /**
     * add anyone to grid
     */
    onAddAnyone: function() {
        var recordData = (this.recordDefaults !== null) ? this.recordDefaults : {};
        recordData[this.recordPrefix + 'type'] = 'anyone';
        recordData[this.recordPrefix + 'name'] = _('Anyone');
        recordData[this.recordPrefix + 'id'] = 0;
        var record = new this.recordClass(recordData, 0);
        
        // check if already in
        if (! this.store.getById(record.id)) {
            this.store.add([record]);
        }
    },
    
    /**
     * @return {Tine.Addressbook.SearchCombo}
     */
    getContactSearchCombo: function () {
        return new Tine.Addressbook.SearchCombo({
            accountsStore: this.store,
            emptyText: _('Search for users ...'),
            newRecordClass: this.recordClass,
            newRecordDefaults: this.recordDefaults,
            recordPrefix: this.recordPrefix,
            userOnly: true,
            onSelect: this.onAddRecordFromCombo,
            additionalFilters: (this.showHidden) ? [{field: 'showDisabled', operator: 'equals', value: true}] : []
        });
    },
    
    /**
     * @return {Tine.Tinebase.widgets.form.RecordPickerComboBox}
     */
    getGroupSearchCombo: function () {
        return new Tine.Tinebase.widgets.form.RecordPickerComboBox({
            //anchor: '100%',
            accountsStore: this.store,
            blurOnSelect: true,
            recordClass: this.groupRecordClass,
            newRecordClass: this.recordClass,
            newRecordDefaults: this.recordDefaults,
            recordPrefix: this.recordPrefix,
            emptyText: _('Search for groups ...'),
            onSelect: this.onAddRecordFromCombo
        });
    },
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function () {
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
    onAddRecordFromCombo: function (recordToAdd) {
        var recordData = {},
            record;
        
        // account record selected
        if (recordToAdd.data.account_id) {
            // user account record
            recordData[this.recordPrefix + 'id'] = recordToAdd.data.account_id;
            recordData[this.recordPrefix + 'type'] = 'user';
            recordData[this.recordPrefix + 'name'] = recordToAdd.data.n_fileas;
            recordData[this.recordPrefix + 'data'] = recordToAdd.data;

            record = new this.newRecordClass(Ext.applyIf(recordData, this.newRecordDefaults), recordToAdd.data.account_id);
        } 
        // group or addressbook list record selected
        else if (recordToAdd.data.group_id || recordToAdd.data.id) {
            recordData[this.recordPrefix + 'id'] = recordToAdd.data.group_id || recordToAdd.data.id;
            recordData[this.recordPrefix + 'type'] = 'group';
            recordData[this.recordPrefix + 'name'] = recordToAdd.data.name;
            recordData[this.recordPrefix + 'data'] = recordToAdd.data;
            
            record = new this.newRecordClass(Ext.applyIf(recordData, this.newRecordDefaults), recordToAdd.id);
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
    onSwitchCombo: function (show, iconCls) {
        var showCombo = (show === 'contact') ? this.contactSearchCombo : this.groupSearchCombo;
        var hideCombo = (show === 'contact') ? this.groupSearchCombo : this.contactSearchCombo;
        
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
Ext.reg('tinerecordpickergrid', Tine.widgets.account.PickerGridPanel);

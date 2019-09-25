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
     * @cfg {String} one of 'user', 'group', 'both', 'myself'
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
     * add button to add the current user
     */
    selectMyself: false,

    /**
     * @cfg {bool}
     * add role selection
     */
    selectRole: false,

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
        this.roleRecordClass = this.roleRecordClass || Tine.Tinebase.Model.Role;

        if (Tine.Tinebase.configManager.get('anyoneAccountDisabled')) {
            Tine.log.info('Tine.widgets.account.PickerGridPanel::initComponent() -> select anyone disabled in config');
            this.selectAnyone = false;
        }
        Tine.widgets.account.PickerGridPanel.superclass.initComponent.call(this);

        this.store.sort(this.recordPrefix + 'name');
    },

    /**
     * init top toolbar
     */
    initTbar: function() {
        this.accountTypeSelector = this.getAccountTypeSelector();

        var items = [this.getSearchCombo('user'), this.getSearchCombo('group'), this.getSearchCombo('role')];
        var combo = this.getSearchCombo(this.selectTypeDefault).show();
        combo.setDisabled(this.selectType === 'myself');

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
            text: i18n._('Search User'),
            scope: this,
            iconCls: 'tinebase-accounttype-user',
            handler: this.onSwitchCombo.createDelegate(this, ['contact', 'tinebase-accounttype-user'])
        };
        var groupActionCfg = {
            text: i18n._('Search Group'),
            scope: this,
            iconCls: 'tinebase-accounttype-group',
            handler: this.onSwitchCombo.createDelegate(this, ['group', 'tinebase-accounttype-group'])
        };
        var roleActionCfg = {
            text: i18n._('Search Role'),
            scope: this,
            iconCls: 'tinebase-accounttype-role',
            handler: this.onSwitchCombo.createDelegate(this, ['role', 'tinebase-accounttype-role'])
        };
        var anyoneActionCfg = {
            text: i18n._('Add Anyone'),
            scope: this,
            iconCls: 'tinebase-accounttype-addanyone',
            handler: this.onAddAnyone
        };
        var myselfActionCfg = {
            text: i18n._('Add Myself'),
            scope: this,
            iconCls: 'tinebase-accounttype-user',
            handler: this.onAddMyself.createDelegate(this)
        };

        // set items
        var items = [];

        switch (this.selectType) {
            case 'both':
                items = items.concat([userActionCfg, groupActionCfg]);
                if (this.selectRole) {
                    items.push(roleActionCfg)
                }
                if (this.selectAnyone) {
                    items.push(anyoneActionCfg);
                }
                break;
            case 'user':
                items = [userActionCfg];
                break;
            case 'group':
                items = [groupActionCfg];
                break;
            case 'myself':
                items = [myselfActionCfg];
                break;
        }

        if (this.selectType !== 'myself' && this.selectMyself === true) {
            items.push(myselfActionCfg);
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
        recordData[this.recordPrefix + 'name'] = i18n._('Anyone');
        recordData[this.recordPrefix + 'id'] = 0;
        var record = new this.recordClass(recordData, 0);

        // check if already in
        if (! this.store.getById(record.id)) {
            this.store.add([record]);
        }
    },

    onAddMyself: function () {
        var currentUser = Tine.Tinebase.registry.get('currentAccount'),
            record,
            recordData = (this.recordDefaults !== null) ? this.recordDefaults : {};

        // user record
        recordData[this.recordPrefix + 'id'] = currentUser.accountId;
        recordData[this.recordPrefix + 'type'] = 'user';
        recordData[this.recordPrefix + 'name'] = currentUser.accountDisplayName;
        recordData[this.recordPrefix + 'data'] = currentUser;


        record = new this.recordClass(recordData, currentUser.accountId);

        // check if already in
        if (! this.store.getById(record.id)) {
            this.store.add([record]);
        }
    },

    getSearchCombo: function(type) {
        type = type == 'user' ? 'contact' : type;
        var combo = this['get' + Ext.util.Format.capitalize(type) + 'SearchCombo']();

        // This combobox doesn't need a validator.
        combo.validator = function () {
            return true;
        };

        return combo;
    },

    /**
     * @return {Tine.Addressbook.SearchCombo}
     */
    getContactSearchCombo: function () {
        if (! this.contactSearchCombo) {
            this.contactSearchCombo = new Tine.Addressbook.SearchCombo({
                hidden: true,
                accountsStore: this.store,
                emptyText: i18n._('Search for users ...'),
                newRecordClass: this.recordClass,
                newRecordDefaults: this.recordDefaults,
                recordPrefix: this.recordPrefix,
                userOnly: true,
                onSelect: this.onAddRecordFromCombo,
                additionalFilters: (this.showHidden) ? [{field: 'showDisabled', operator: 'equals', value: true}] : []
            });
        }

        return this.contactSearchCombo;
    },

    /**
     * @return {Tine.Tinebase.widgets.form.RecordPickerComboBox}
     */
    getGroupSearchCombo: function () {
        if (! this.groupSearchCombo) {
            this.groupSearchCombo = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
                hidden: true,
                accountsStore: this.store,
                blurOnSelect: true,
                recordClass: this.groupRecordClass,
                newRecordClass: this.recordClass,
                newRecordDefaults: this.recordDefaults,
                recordPrefix: this.recordPrefix,
                emptyText: i18n._('Search for groups ...'),
                onSelect: this.onAddRecordFromCombo,
                additionalFilters: [{field: 'type', operator: 'equals', value: 'group'}]
            });
        }

        return this.groupSearchCombo;
    },

    getRoleSearchCombo: function() {
        if (! this.roleSearchCombo) {
            this.roleSearchCombo = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
                hidden: true,
                accountsStore: this.store,
                blurOnSelect: true,
                recordClass: this.roleRecordClass,
                newRecordClass: this.recordClass,
                newRecordDefaults: this.recordDefaults,
                recordPrefix: this.recordPrefix,
                emptyText: i18n._('Search for roles ...'),
                onSelect: this.onAddRecordFromCombo
            });
        }

        return this.roleSearchCombo;
    },

    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function () {
        if (! this.colModel) {
            this.colModel = new Ext.grid.ColumnModel({
                defaults: {
                    sortable: true
                },
                columns: [
                    {
                        id: 'name',
                        header: i18n._('Name'),
                        width: 200,
                        dataIndex: this.recordPrefix + 'name',
                        renderer: Tine.Tinebase.common.accountRenderer
                    }
                ].concat(this.configColumns)
            });
        }

        return this.colModel;
    },

    /**
     * @param {Record} recordToAdd
     *
     * TODO make reset work correctly -> show emptyText again
     */
    onAddRecordFromCombo: function (recordToAdd) {
        var recordData = {},
            record,
            type = String(recordToAdd.constructor.getMeta('modelName')).toLowerCase();

        // role record selected
        if (type == 'role') {
            recordData[this.recordPrefix + 'id'] = recordToAdd.data.id;
            recordData[this.recordPrefix + 'type'] = 'role';
            recordData[this.recordPrefix + 'name'] = recordToAdd.data.name;
            recordData[this.recordPrefix + 'data'] = recordToAdd.data;
        }
        // account record selected
        else if (recordToAdd.data.account_id) {
            // user account record
            recordData[this.recordPrefix + 'id'] = recordToAdd.data.account_id;
            recordData[this.recordPrefix + 'type'] = 'user';
            recordData[this.recordPrefix + 'name'] = recordToAdd.data.n_fileas;
            recordData[this.recordPrefix + 'data'] = recordToAdd.data;
        }
        // group or addressbook list record selected
        else if (recordToAdd.data.group_id || recordToAdd.data.id) {
            recordData[this.recordPrefix + 'id'] = recordToAdd.data.group_id || recordToAdd.data.id;
            recordData[this.recordPrefix + 'type'] = 'group';
            recordData[this.recordPrefix + 'name'] = recordToAdd.data.name;
            recordData[this.recordPrefix + 'data'] = recordToAdd.data;
        }

        record = new this.newRecordClass(Ext.applyIf(recordData, this.newRecordDefaults), recordData[this.recordPrefix + 'id']);

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
     */
    onSwitchCombo: function (show, iconCls) {
        Ext.each(['contact', 'group', 'role'], function(type) {
            this.getSearchCombo(type).setVisible(show == type);
        }, this);

        this.getSearchCombo(show).setWidth('auto');
        this.accountTypeSelector.setIconClass(iconCls);

    }
});
Ext.reg('tinerecordpickergrid', Tine.widgets.account.PickerGridPanel);

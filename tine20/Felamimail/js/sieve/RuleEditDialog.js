/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail.sieve');

/**
 * @namespace   Tine.Felamimail.sieve
 * @class       Tine.Felamimail.sieve.RuleEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Sieve Filter Dialog</p>
 * <p>This dialog is editing a filter rule.</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RuleEditDialog
 */
Tine.Felamimail.sieve.RuleEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {Tine.Felamimail.Model.Account}
     */
    account: null,
    
    /**
     * @private
     */
    windowNamePrefix: 'RuleEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Rule,
    mode: 'local',
    loadRecord: true,
    tbarItems: [],
    evalGrants: false,
    conjunctionCombo: null,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * 
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Felamimail.sieve.RuleEditDialog.superclass.onRender.call(this, ct, position);
        
        this.onChangeType.defer(250, this);
    },
    
    /**
     * Change type card layout depending on selected combo box entry and set field value
     */
    onChangeType: function() {
        var type = this.actionTypeCombo.getValue();
        
        var cardLayout = Ext.getCmp(this.idPrefix + 'CardLayout').getLayout();
        if (cardLayout !== 'card') {
            cardLayout.setActiveItem(this.idPrefix + type);
            if (this.record.get('action_type') == type) {
                var field = this.getForm().findField('action_argument_' + type);
                if (field !== null) {
                    switch (type) {
                        case 'redirect':
                           var data = this.record.get('action_argument'),
                               checkbox = this.getForm().findField('action_argument_redirect_copy');
                           field.setValue(data.emails);
                           checkbox.setValue(data.copy);
                           break;
                        default:
                           field.setValue(this.record.get('action_argument'));
                    }
                }
            }
        }
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        var title = this.app.i18n._('Edit Filter Rule');
        this.window.setTitle(title);
        
        this.getForm().loadRecord(this.record);

        this.conditionsPanel.conjunctionOperator = this.record.get('conjunction') == 'anyof' ? 'or' : 'and';
        
        this.hideLoadMask();
    },
    
    /**
     * @private
     */
    onRecordUpdate: function() {
        Tine.Felamimail.sieve.RuleEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('conditions', this.getConditions());

        var argumentFieldName = 'action_argument_' + this.actionTypeCombo.getValue();
            argumentField = this.getForm().findField(argumentFieldName),
            argumentValue = (argumentField !== null) ? argumentField.getValue() : '';

        // add additional action arguments
        if (argumentFieldName === 'action_argument_redirect') {
            argumentValue = {
                emails: argumentValue,
                copy: this.getForm().findField('action_argument_redirect_copy').getValue()
            };
        }
        
        this.record.set('action_argument', argumentValue);
    },
    
    /**
     * get conditions and do the mapping
     * 
     * @return {Array}
     */
    getConditions: function() {
        var conditions = this.conditionsPanel.getAllFilterData();
        var result = [],
            i = 0, 
            condition,
            test,
            comperator,
            header;
            
        for (i = 0; i < conditions.length; i++) {
            // set defaults
            comperator = conditions[i].operator;
            header = conditions[i].field;
            test = 'header';

            switch (conditions[i].field) {
                case 'from':
                case 'to':
                    test = 'address';
                    break;
                case 'fromheader':
                    header = 'From';
                    break;
                case 'size':
                    test = 'size';
                    comperator = (conditions[i].operator == 'greater') ? 'over' : 'under';
                    break;
                case 'header':
                    header = conditions[i].operator;
                    comperator = 'contains';
                    break;
                case 'headerregex':
                    header = conditions[i].operator;
                    comperator = 'regex';
                    break;
            }
            condition = {
                test: test,
                header: header,
                comperator: comperator,
                key: conditions[i].value
            };
            result.push(condition);
        }
        
        return result;
    },
    
    /**
     * get conditions filter data (reverse of getConditions)
     * 
     * @return {Array}
     */
    getConditionsFilter: function() {
        var conditions = this.record.get('conditions');
        var result = [],
            i = 0, 
            filter,
            operator,
            field;
            
        for (i = 0; i < conditions.length; i++) {
            
            field = conditions[i].header;
            switch (field) {
                case 'size':
                    operator = (conditions[i].comperator == 'over') ? 'greater' : 'less';
                    break;
                case 'from':
                case 'to':
                case 'subject':
                    operator = conditions[i].comperator;
                    break;
                case 'cc':
                    field = 'cc'
                    break;
                default:
                    if (field == 'From') {
                        operator = conditions[i].comperator;
                        field = 'fromheader';
                    } else {
                        operator = field;
                        field = (conditions[i].comperator == 'contains') ? 'header' : 'headerregex';
                    }
            }
            filter = {
                field: field,
                operator: operator,
                value: conditions[i].key
            };
            result.push(filter);
        }
        
        return result;
    },
    
    /**
     * sets the conjunction operator of the filtertoolbar to show the correct text for anyof or allof
     */
    onConjunctionChange: function() {
        this.conditionsPanel.conjunctionOperator = this.conjunctionCombo.getValue() == 'anyof' ? 'or' : 'and';
        this.conditionsPanel.onFilterRowsChange();
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initialization is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        
        this.conditionsPanel = new Tine.Felamimail.sieve.RuleConditionsPanel({
            filters: this.getConditionsFilter()
        });
        
        this.actionTypeCombo = new Ext.form.ComboBox({
            hideLabel       : true,
            name            : 'action_type',
            typeAhead       : false,
            triggerAction   : 'all',
            lazyRender      : true,
            editable        : false,
            mode            : 'local',
            forceSelection  : true,
            value           : 'discard',
            columnWidth     : 0.4,
            store: Tine.Felamimail.sieve.RuleEditDialog.getActionTypes(this.app),
            listeners: {
                scope: this,
                change: this.onChangeType,
                select: this.onChangeType
            }
        });
        
        this.conjunctionCombo = new Ext.form.ComboBox({
            hideLabel  : false,
            width: 250,
            fieldLabel : '<span class="felamimail-sieverule-conjunction">' + this.app.i18n._('Apply filter if') + '</span>',
            labelSeparator: '',
            name       : 'conjunction',
            store      : [
                ['allof', this.app.i18n._('all conditions match')],
                ['anyof', this.app.i18n._('any condition matches')]
            ],
            value      : 'allof',
            listeners: {
                scope: this,
                change: this.onConjunctionChange,
                select: this.onConjunctionChange
            }
        });
        
        this.idPrefix = Ext.id();
        
        return [{
            xtype: 'panel',
            layout: 'border',
            autoScroll: true,
            items: [
            {
                region: 'north',
                border: false,
                xtype: 'panel',
                autoScroll: true,
                height: 200,
                items: [{
                    layout: 'form',
                    labelWidth: 200,
                    frame: true,
                    region: 'center',
                    autoScroll: true,
                    border: false,
                    items: [
                        this.conjunctionCombo
                    ]
                }, this.conditionsPanel
                ],
                listeners: {
                    scope: this,
                    afterlayout: function(ct, layout) {
                        ct.suspendEvents();
                        if (this.conditionsPanel.getHeight() < 170) {
                            ct.setHeight(this.conditionsPanel.getHeight() + 35);
                        }
                        ct.ownerCt.layout.layout();
                        ct.resumeEvents();
                    }
                }
            }, {
                title: this.app.i18n._('Do this action:'),
                region: 'center',
                border: false,
                frame: true,
                xtype: 'panel',
                layout: 'column',
                items: [
                    this.actionTypeCombo,
                    // TODO try to add a spacer/margin between the two input fields
                /*{
                    // spacer
                    columnWidth: 0.1,
                    layout: 'fit',
                    title: '',
                    items: []
                }, */{
                    id: this.idPrefix + 'CardLayout',
                    layout: 'card',
                    activeItem: this.idPrefix + 'fileinto',
                    border: false,
                    columnWidth: 0.5,
                    xtype: 'panel',
                    defaults: {
                        border: false
                    },
                    items: [{
                        id: this.idPrefix + 'fileinto',
                        layout: 'form',
                        items: [{
                            name: 'action_argument_fileinto',
                            xtype: 'felamimailfolderselect',
                            width: 200,
                            hideLabel: true,
                            account: this.account
                        }]
                    }, {
                        // TODO add email validator?
                        id: this.idPrefix + 'redirect',
                        layout: 'form',
                        items: [{
                            name: 'action_argument_redirect',
                            xtype: 'textfield',
                            emptyText: 'test@example.org',
                            width: 200,
                            hideLabel: true
                        }, {
                            name: 'action_argument_redirect_copy',
                            xtype: 'checkbox',
                            fieldLabel: this.app.i18n._('Keep a copy')
                        }]
                    }, {
                        id: this.idPrefix + 'reject',
                        layout: 'form',
                        items: [{
                            name: 'action_argument_reject',
                            xtype: 'textarea',
                            width: 300,
                            height: 60,
                            hideLabel: true
                        }]
                    }, {
                        id: this.idPrefix + 'vacation',
                        layout: 'form',
                        items: [{
                            name: 'action_argument_vacation',
                            xtype: 'textarea',
                            width: 300,
                            height: 60,
                            hideLabel: true
                        }]
                    }, {
                        id: this.idPrefix + 'discard',
                        layout: 'fit',
                        items: []
                    }]
                }]
            }]
        }];
    }
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.sieve.RuleEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 700,
        height: 300,
        name: Tine.Felamimail.sieve.RuleEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.sieve.RuleEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

/**
 * get action types for action combo and action type renderer
 * 
 * @param {} app
 * @return {Array}
 */
Tine.Felamimail.sieve.RuleEditDialog.getActionTypes = function(app) {
    return [
        ['fileinto',    app.i18n._('Move mail to folder')],
        ['redirect',    app.i18n._('Redirect mail to address')],
        ['reject',      app.i18n._('Reject mail with this text')],
        ['discard',     app.i18n._('Discard mail')],
        ['vacation',    app.i18n._('Auto-Reply mail with this text')]
        //['keep',        app.i18n._('Keep mail')],
    ];
};

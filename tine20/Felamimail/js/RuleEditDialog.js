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
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.RuleEditDialog
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
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RuleEditDialog
 */
Tine.Felamimail.RuleEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'RuleEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Rule,
    //recordProxy: Tine.Felamimail.vacationBackend,
    mode: 'local',
    loadRecord: true,
    tbarItems: [],
    evalGrants: false,
    
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
        Tine.Felamimail.RuleEditDialog.superclass.onRender.call(this, ct, position);
        
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
                    field.setValue(this.record.get('action_argument'));
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
        
        Tine.log.debug(this.record);
        this.getForm().loadRecord(this.record);
        
        this.loadMask.hide();
    },
    
    /**
     * @private
     */
    onRecordUpdate: function() {
        Tine.Felamimail.RuleEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('conditions', this.getConditions());
        
        var argumentField = this.getForm().findField('action_argument_' + this.actionTypeCombo.getValue()),
            argumentValue = (argumentField !== null) ? argumentField.getValue() : '';
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
            comperator;
            
        for (i = 0; i < conditions.length; i++) {
            switch (conditions[i].field) {
                case 'from':
                case 'to':
                    test = 'address';
                    break;
                case 'size':
                    test = 'size';
                    break;
                default:
                    test = 'header';
            }
            switch (conditions[i].field) {
                case 'size':
                    comperator = (conditions[i].operator == 'greater') ? 'over' : 'under';
                    break;
                default:
                    comperator = conditions[i].operator;
            }
            condition = {
                test: test,
                header: conditions[i].field,
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
            operator;
            
        for (i = 0; i < conditions.length; i++) {
            switch (conditions[i].header) {
                case 'size':
                    operator = (conditions[i].comperator == 'over') ? 'greater' : 'less';
                    break;
                default:
                    operator = conditions[i].comperator;
            }
            filter = {
                field: conditions[i].header,
                operator: operator,
                value: conditions[i].key
            };
            result.push(filter);            
        }
        
        return result;     
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        
        this.conditionsPanel = new Tine.Felamimail.RuleConditionsPanel({
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
            store: [
                ['fileinto',    this.app.i18n._('Move mail to folder')],
                ['redirect',    this.app.i18n._('Redirect mail to address')],
                ['reject',      this.app.i18n._('Reject mail with this text')],
                ['discard',     this.app.i18n._('Discard mail')]
                //['keep',        this.app.i18n._('Keep mail')],
            ],
            listeners: {
                scope: this,
                change: this.onChangeType,
                select: this.onChangeType
            }
        });
        
        this.idPrefix = Ext.id();
        
        return [{
            xtype: 'panel',
            layout: 'border',
            autoScroll: true,
            items: [
            {
                title: this.app.i18n._('If all of the following conditions are met:'),
                region: 'north',
                border: false,
                items: [
                    this.conditionsPanel
                ],
                xtype: 'panel',
                listeners: {
                    scope: this,
                    afterlayout: function(ct, layout) {
                        ct.suspendEvents();
                        ct.setHeight(this.conditionsPanel.getHeight()+30);
                        ct.ownerCt.layout.layout();
                        ct.resumeEvents();
                    }
                }
            }, {
                title: this.app.i18n._('Do this action:'),
                region: 'center',
                border: false,
                frame: true,
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
                            hideLabel: true
                        }]
                    }, {
                        id: this.idPrefix + 'redirect',
                        layout: 'form',
                        items: [{
                            name: 'action_argument_redirect',
                            xtype: 'textfield',
                            width: 200,
                            hideLabel: true
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
Tine.Felamimail.RuleEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 700,
        height: 300,
        name: Tine.Felamimail.RuleEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.RuleEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

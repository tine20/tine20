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
 * TODO         add more form fields (action comboboxes)
 * TODO         make action work
 * TODO         add conditions panel again
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
        
        //Tine.log.debug(this.record);
        this.getForm().loadRecord(this.record);
        
        this.loadMask.hide();
    },
        
    /**
     * @private
     */
    // TODO for testing purposes. remove it later!
    /*
    onSaveAndClose: function(button, event){
        this.onApplyChanges(button, event, false);
        this.fireEvent('saveAndClose');
    },
    */
    
    /**
     * @private
     */
    onRecordUpdate: function() {
        Tine.Felamimail.RuleEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('conditions', this.getConditions());
        
        Tine.log.debug(this.record);

        //var form = this.getForm();
        // TODO get action
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
        //Tine.log.debug(result);
        
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
        //Tine.log.debug(result);
        
        return result;     
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     * 
     * TODO add conditions panel again
     * TODO switch action_argument input field if action_type combo changes (for example to the tree folder selection)
     */
    getFormItems: function() {
        
        this.conditionsPanel = new Tine.Felamimail.RuleConditionsPanel({
            filters: this.getConditionsFilter()
        });
        
        this.actionTypeCombo = new Ext.form.ComboBox({
            //fieldLabel: this.app.i18n._('Do this action:'),
            hideLabel       : true,
            name            : 'action_type',
            typeAhead       : false,
            triggerAction   : 'all',
            lazyRender      : true,
            editable        : false,
            mode            : 'local',
            forceSelection  : true,
            value           : 'discard',
            anchor          : '90%',
            columnWidth     : 0.5,
            store: [
                ['discard',     this.app.i18n._('Discard mail')],
                ['fileinto',    this.app.i18n._('Move mail to folder')]
                // TODO activate more actions
                //['keep',        this.app.i18n._('Keep mail')],
                //['reject',      this.app.i18n._('Reject mail')],
                //['redirect',    this.app.i18n._('Redirect mail')]
            ]
        });
        
        this.idPrefix = Ext.id();
        
        return [{
            xtype: 'panel',
            layout: 'border',
            items: [
            {
                title: this.app.i18n._('If all of the following conditions are met:'),
                region: 'north',
                border: false,
                items: [
                    //this.conditionsPanel
                ],
                listeners: {
                    scope: this,
                    afterlayout: function(ct) {
                        ct.suspendEvents();
                        //ct.setHeight(this.conditionsPanel.getHeight()+30);
                        ct.layout.layout();
                        ct.resumeEvents();
                    }
                }
            },
            {
                title: this.app.i18n._('Do this action:'),
                region: 'center',
                border: false,
                autoHeight: true,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    xtype:'textfield',
                    anchor: '90%',
                    labelSeparator: '',
                    maxLength: 256,
                    columnWidth: 0.5,
                    hideLabel: true
                },
                items: [[
                    this.actionTypeCombo,
                {
                    xtype:'textfield',
                    anchor: '90%',
                    name: 'action_argument'
                    //fieldLabel: this.app.i18n._('Move to folder')
                }
                
                // TODO try to make this work
                /*{
                    id: this.idPrefix + 'ArgumentCardLayout',
                    layout: 'card',
                    activeItem: this.idPrefix + 'standard',
                    border: false,
                    width: '100%',
                    defaults: {
                        border: false
                    },
                    items: [{
                        // nothing in here yet
                        id: this.idPrefix + 'standard',
                        layout: 'form',
                        items: []
                    }, {
                        // fileinto config options
                        id: this.idPrefix + 'fileinto',
                        layout: 'form',
                        autoHeight: 'auto',
                        defaults: {
                            width: 100,
                            xtype: 'textfield'
                        },
                        items: [{
                            // TODO folder selection combo
                            name: 'action_argument'
                        }]
                    }]
                }*/
                
                ]]
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
        width: 800,
        height: 400,
        name: Tine.Felamimail.RuleEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.RuleEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

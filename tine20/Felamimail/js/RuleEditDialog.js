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
 * TODO         set conditions on init
 * TODO         add more form fields (action comboboxes)
 * TODO         make action work
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
        
        // TODO set conditions
        
        //Tine.log.debug(this.record);
        this.getForm().loadRecord(this.record);
        
        this.loadMask.hide();
    },
        
    /**
     * @private
     */
    onSaveAndClose: function(button, event){
        this.onApplyChanges(button, event, false);
        this.fireEvent('saveAndClose');
    },
    
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
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        
        this.conditionsPanel = new Tine.Felamimail.RuleConditionsPanel({
            // TODO add preset filters
            filters: []
        });
        
        return [{
            xtype: 'panel',
            layout: 'border',
            items: [
            {
                region: 'north',
                xtype: 'fieldset',
                title: this.app.i18n._('Conditions'),
                border: false,
                items: this.conditionsPanel,
                listeners: {
                    scope: this,
                    afterlayout: function(ct) {
                        ct.suspendEvents();
                        ct.setHeight(this.conditionsPanel.getHeight()+35);
                        ct.ownerCt.layout.layout();
                        ct.resumeEvents();
                    }
                }
            }, {
                region: 'center',
                title: this.app.i18n._('Action'),
                xtype: 'fieldset',
                autoHeight: true,
                layout: 'form',
                border: false,
                anchor: '90%',
                defaults: {
                    xtype: 'textfield',
                    anchor: '90%'
                },
                items: [{
                    name: 'action_argument',
                    fieldLabel: this.app.i18n._('Move to folder')
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
        width: 800,
        height: 400,
        name: Tine.Felamimail.RuleEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.RuleEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

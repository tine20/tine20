/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Timetracker');

/**
 * Timetracker Edit Dialog
 */
Tine.Timetracker.TimesheetEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    
    /**
     * @private
     */
    windowNamePrefix: 'TimesheetEditWindow_',
    appName: 'Timetracker',
    recordClass: Tine.Timetracker.Model.Timesheet,
    recordProxy: Tine.Timetracker.timesheetBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function(record) {
        this.onTimeaccountUpdate();
    	Tine.Timetracker.TimesheetEditDialog.superclass.updateToolbars.call(this, record, 'timeaccount_id');
    },
    
    /**
     * this gets called when initializing and if a new timeaccount is chosen
     * 
     * @param {} field
     * @param {} timeaccount
     */
    onTimeaccountUpdate: function(field, timeaccount) {
    	// check for manage_timeaccounts right
    	var manageRight = Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts');
    	
        var grants = timeaccount ? timeaccount.get('account_grants') : (this.record.get('timeaccount_id') ? this.record.get('timeaccount_id').account_grants : {});
        if (grants) {
            this.getForm().findField('account_id').setDisabled(! (grants.book_all || grants.manage_all || manageRight));
            this.getForm().findField('is_billable').setDisabled(! (grants.manage_billable || grants.manage_all || manageRight));
            this.getForm().findField('is_cleared').setDisabled(! (/*grants.manage_billable ||*/ grants.manage_all || manageRight));
        }
        
        if (timeaccount && timeaccount.data.is_billable == "0" || this.record.get('timeaccount_id').is_billable == "0") {
        	this.getForm().findField('is_billable').setDisabled(true);
        	if (this.record.id == 0) {
        	   // set to 0 be default for new records
        	   this.getForm().findField('is_billable').setValue(0);
        	}
        }
    },

    /**
     * this gets called when initializing and if cleared checkbox is changed
     * 
     * @param {} field
     * @param {} newValue
     * 
     * @todo    don't call this when dialog is opened
     */
    onClearedUpdate: function(field, checked) {
    	//console.log(this.record);
        //console.log(checked);
    	/*
    	
    	if (checked && this.getForm().findField('billed_in').getValue() == '') {
    		// open modal window to type in billed in value
            Ext.Msg.prompt(
                this.app.i18n._('Billed in ...'),
                this.app.i18n._('Billed in ...'), 
                function(btn, text) {
                    if (btn == 'ok'){
                    	//console.log(text);
                        this.getForm().findField('billed_in').setValue(text);
                    }
                },
                this
            );                		
    	} else {
    		this.getForm().findField('billed_in').setValue('');
    	}
    	*/
    	
    	/*
    	if (timeaccount && timeaccount.data.is_billable == "0" || this.record.get('timeaccount_id').is_billable == "0") {
            this.getForm().findField('is_billable').setDisabled(true);
            if (this.record.id == 0) {
               // set to 0 be default for new records
               this.getForm().findField('is_billable').setValue(0);
            }
        }
        */
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() { 
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            items:[
                {            	
                title: this.app.i18n._('Timesheet'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: .333
                    },
                    items: [[new Tine.Timetracker.TimeAccountSelect({
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Time Account'),
                        emptyText: this.app.i18n._('Select Time Account...'),
                        loadingText: this.app.i18n._('Searching...'),
                        allowBlank: false,
                        name: 'timeaccount_id',
                        listeners: {
                            scope: this,
                            select: this.onTimeaccountUpdate
                        }
                    })], [{
                        fieldLabel: this.app.i18n._('Duration'),
                        name: 'duration',
                        allowBlank: false,
                        xtype: 'tinedurationspinner'
                        }, {
                        fieldLabel: this.app.i18n._('Date'),
                        name: 'start_date',
                        allowBlank: false,
                        xtype: 'datefield'
                        }, {
                        fieldLabel: this.app.i18n._('Start'),
                        emptyText: this.app.i18n._('not set'),
                        name: 'start_time',
                        xtype: 'timefield'/*,
                        listeners: {
                            scope: this, 
                            'expand': function(field) {
                                if (! field.getValue()) {
                                    var now = new Date().getHours();
                                    //console.log(field.store.find('text', '18:00'));
                                    field.select(field.store.find('text', '18:00'));
                                    
                                }
                            }
                        }*/
                    }], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Description'),
                        emptyText: this.app.i18n._('Enter description...'),
                        name: 'description',
                        allowBlank: false,
                        xtype: 'textarea',
                        height: 150
                    }], [new Tine.widgets.AccountpickerField({
                        allowBlank: false,
                        columnWidth: 1,
                        disabled: true,
                        fieldLabel: this.app.i18n._('Account'),
                        name: 'account_id'
                    }), {
                        columnWidth: .25,
                        hideLabel: true,
                        disabled: true,
                        boxLabel: this.app.i18n._('Billable'),
                        name: 'is_billable',
                        xtype: 'checkbox'
                    }, {
                        columnWidth: .25,
                        hideLabel: true,
                        disabled: true,
                        boxLabel: this.app.i18n._('Cleared'),
                        name: 'is_cleared',
                        xtype: 'checkbox',
                        listeners: {
                            scope: this,
                            check: this.onClearedUpdate
                        }                        
                    }, {
                        columnWidth: .5,
                        //hideLabel: true,
                        disabled: true,
                        emptyText: this.app.i18n._('not billed yet...'),
                        fieldLabel: this.app.i18n._('Billed In') + ' ...',
                        name: 'billed_in',
                        xtype: 'textfield'
                    }]] 
                }, {
                    // activities and tags
                    layout: 'accordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Tine.widgets.activities.ActivitiesPanel({
                            app: 'Timetracker',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'Timetracker',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, new Tine.widgets.customfields.CustomfieldsPanel ({
                recordClass: this.recordClass,
                quickHack: this
            }), new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});

/**
 * Timetracker Edit Popup
 */
Tine.Timetracker.TimesheetEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Timetracker.TimesheetEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Timetracker.TimesheetEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
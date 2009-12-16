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
    getDefaultsAgain: false,
    
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
    	
        var notBillable = false;
        var notClearable = false;

        var grants = timeaccount ? timeaccount.get('account_grants') : (this.record.get('timeaccount_id') ? this.record.get('timeaccount_id').account_grants : {});
        if (grants) {
            this.getForm().findField('account_id').setDisabled(! (grants.book_all || grants.manage_all || manageRight));
            notBillable = ! (grants.manage_billable || grants.manage_all || manageRight);
            notClearable = ! (/*grants.manage_billable ||*/ grants.manage_all || manageRight);
            this.getForm().findField('billed_in').setDisabled(! (grants.manage_all || manageRight));
        }

        if (timeaccount) {
            notBillable = notBillable || timeaccount.data.is_billable == "0" || this.record.get('timeaccount_id').is_billable == "0";
            
            // clearable depends on timeaccount is_billable as well (changed by ps / 2009-09-01, behaviour was inconsistent)
            notClearable = notClearable || timeaccount.data.is_billable == "0" || this.record.get('timeaccount_id').is_billable == "0";
        }
        
        this.getForm().findField('is_billable').setDisabled(notBillable);
        this.getForm().findField('is_cleared').setDisabled(notClearable);
        
    	if (this.record.id == 0 && timeaccount) {
    	    // set is_billable for new records according to the timeaccount setting
    	    this.getForm().findField('is_billable').setValue(timeaccount.data.is_billable);
    	}
    },

    /**
     * this gets called when initializing and if cleared checkbox is changed
     * 
     * @param {} field
     * @param {} newValue
     * 
     * @todo    add prompt later?
     */
    onClearedUpdate: function(field, checked) {
    	
        this.getForm().findField('billed_in').setDisabled(! checked);

        /*
    	if (checked && this.getForm().findField('billed_in').getValue() == '') {
    		// open modal window to type in billed in value
            Ext.Msg.prompt(
                this.app.i18n._('Billed in ...'),
                this.app.i18n._('Billed in ...'), 
                function(btn, text) {
                    if (btn == 'ok'){
                        this.getForm().findField('billed_in').setValue(text);
                    }
                },
                this
            );                		
    	} else {
    		this.getForm().findField('billed_in').setValue('');
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
                    }], [new Tine.Addressbook.SearchCombo({
                        allowBlank: false,
                        columnWidth: 1,
                        disabled: true,
                        useAccountRecord: true,
                        internalContactsOnly: true,
                        nameField: 'n_fileas',
                        fieldLabel: this.app.i18n._('Account'),
                        name: 'account_id'
                    }), {
                        columnWidth: .25,
                        //hideLabel: false,
                        disabled: true,
                        boxLabel: this.app.i18n._('Billable'),
                        name: 'is_billable',
                        xtype: 'checkbox'
                    }, {
                        columnWidth: .25,
                        //hideLabel: true,
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
                        disabled: true,
                        //emptyText: this.app.i18n._('not cleared yet...'),
                        fieldLabel: this.app.i18n._('Cleared In'),
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
            }, new Tine.Tinebase.widgets.customfields.CustomfieldsPanel ({
                recordClass: this.recordClass,
                disabled: (Tine[this.appName].registry.get('customfields').length == 0),
                quickHack: this
            }), new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
    
    /**
     * show error if request fails
     * 
     * @param {} response
     * @param {} request
     * @private
     * 
     */
    onRequestFailed: function(response, request) {
        if (response.code && response.code == 902) {
            // deadline exception
            Ext.MessageBox.alert(
                this.app.i18n._('Failed'), 
                String.format(this.app.i18n._('Could not save {0}.'), this.i18nRecordName) 
                    + ' ( ' + this.app.i18n._('Booking deadline for this Timeaccount has been exceeded.') /* + ' ' + response.message  */ + ')'
            ); 
        } else {
            // call default exception handler
            Tine.Tinebase.ExceptionHandler.handleRequestException(response);
        }
        this.loadMask.hide();
    }
});

/**
 * Timetracker Edit Popup
 */
Tine.Timetracker.TimesheetEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 500,
        name: Tine.Timetracker.TimesheetEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Timetracker.TimesheetEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
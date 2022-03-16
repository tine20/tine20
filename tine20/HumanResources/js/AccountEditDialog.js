/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.AccountEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Account Edit Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * Create a new Tine.HumanResources.AccountEditDialog
 */
Tine.HumanResources.AccountEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /*
     * @private
     */
    evalGrants: false,

    windowWidth: 600,
    windowHeight: 560,
    
    /**
     * inits the component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources')
        Tine.HumanResources.AccountEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        Tine.HumanResources.AccountEditDialog.superclass.onRecordLoad.call(this);

        var employee = this.record.get('employee_id'),
            name = employee ? employee.n_fn : 'unknown';

        this.window.setTitle(String.format(this.app.i18n._('Edit {0} for {1} - {2}'), this.i18nRecordName, Ext.util.Format.htmlEncode(name), this.record.get('year')));
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
        // columnForm defaults
        var cfDefaults = {xtype: 'textfield', readOnly: true, columnWidth: .5, anchor: '100%'};
        return {
            xtype: 'tabpanel',
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            activeTab: 0,
            border: false,
            items: [{
                title: this.app.i18n._('Summary'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        layout: 'hfit',
                        autoHeight: true,
                        title: this.app.i18n._('Vacation'),
                        items: [{
                            xtype: 'columnform',
                            formDefaults: cfDefaults,
                            labelAlign: 'top',
                            items: [[
                                {fieldLabel: this.app.i18n._('Possible vaction days'), name: 'possible_vacation_days', columnWidth: 1/4 },
                                {fieldLabel: this.app.i18n._('Requested vaction days'), name: 'scheduled_requested_vacation_days', columnWidth: 1/4 },
                                {fieldLabel: this.app.i18n._('Taken vaction days'), name: 'scheduled_taken_vacation_days', columnWidth: 1/4 },
                                {fieldLabel: this.app.i18n._('Remaining vaction days'), name: 'scheduled_remaining_vacation_days', columnWidth: 1/4 },
                            ]]
                        }]
                    }, {
                        xtype: 'fieldset',
                        layout: 'hfit',
                        autoHeight: true,
                        title: this.app.i18n._('Sickness'),
                        items: [{
                            xtype: 'columnform',
                            formDefaults: cfDefaults,
                            labelAlign: 'top',
                            items: [[
                                {fieldLabel: this.app.i18n._('Excused sickness days'), name: 'excused_sickness' },
                                {fieldLabel: this.app.i18n._('Unexcused sickness days'), name: 'unexcused_sickness' }
                            ]]
                        }]
                    }, {
                        xtype: 'fieldset',
                        layout: 'hfit',
                        autoHeight: true,
                        title: this.app.i18n._('Working Time'),
                        items: [{
                            xtype: 'columnform',
                            formDefaults: cfDefaults,
                            labelAlign: 'top',
                            items: [[
                                {fieldLabel: this.app.i18n._('Days to work'), name: 'working_days' },
                                {fieldLabel: this.app.i18n._('Hours to work'), name: 'working_hours' }
                            ], [
                                {fieldLabel: this.app.i18n._('Days to work after vacation and sickness'), name: 'working_days_real' },
                                {fieldLabel: this.app.i18n._('Hours to work after vacation and sickness'), name: 'working_hours_real' }
                            ]]
                        }]
                    }, {
                        xtype: 'fieldset',
                        layout: 'hfit',
                        autoHeight: true,
                        title: this.app.i18n._('Miscellaneous'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            items: [[{
                                xtype: 'textarea',
                                height: 180,
                                disabled: false,
                                readOnly: false,
                                fieldLabel: this.app.i18n._('Description'),
                                name: 'description', 
                                columnWidth: 1 
                            }]]
                        }]
                    }]
                }]},
                new Tine.widgets.activities.ActivitiesTabPanel({
                    app: this.appName,
                    record_id: this.record.id,
                    record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
                }) 
            ]
        };
    }
});

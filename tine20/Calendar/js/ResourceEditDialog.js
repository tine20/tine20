/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class     Tine.Calendar.ResourceEditDialog
 * @extends   Tine.widgets.dialog.EditDialog
 * Resources Grid Panel <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.ResourceEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    recordClass: Tine.Calendar.Model.Resource,
    windowNamePrefix: 'ResourceEditWindow_',
    evalGrants: true,
    showContainerSelector: false,
    tbarItems: [],
    requiredSaveGrant: 'resourceEditGrant',

    /**
     *
     * @returns {{xtype: string, border: boolean, plain: boolean, activeTab: number, border: boolean, items: [null,null,null]}}
     */
    getFormItems: function() {
        this.grantsGridPanel = new Tine.widgets.container.GrantsGrid({
            recordClass: Tine.Calendar.Model.ResourceGrants,
            grantContainer: {
                application_id: this.app.id,
                type: Tine.Tinebase.container.TYPE_SHARED,
                model: 'Calendar_Model_Event',
                xprops: {Tinebase: {Container: {GrantsModel: 'Calendar_Model_ResourceGrants'}}}
            }
        });

        return {
            xtype: 'tabpanel',
            plain:true,
            activeTab: 0,
            border: false,
            items:[
                {
                title: this.app.i18n.n_('Resource', 'Resources', 1),
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
                    items: [[{
                        xtype: 'textfield',
                        fieldLabel: this.app.i18n._('Name'),
                        allowBlank: false,
                        name: 'name'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: this.app.i18n._('Email'),
                        allowBlank: false,
                        name: 'email',
                        vtype: 'email'
                    },  new Tine.Tinebase.widgets.keyfield.ComboBox({
                        app: 'Calendar',
                        keyFieldName: 'resourceTypes',
                        fieldLabel: this.app.i18n._('Type'),
                        name: 'type'
                    })], [{
                        xtype: 'textfield',
                        fieldLabel: this.app.i18n._('Calendar Hierarchy/Name'),
                        allowBlank: true,
                        columnWidth: 1,
                        name: 'hierarchy'
                    }], [
                        new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Calendar',
                            keyFieldName: 'attendeeStatus',
                            fieldLabel: this.app.i18n._('Default attendee status'),
                            name: 'status',
                            value: 'NEEDS-ACTION'
                    }), new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Calendar',
                            keyFieldName: 'attendeeStatus',
                            fieldLabel: this.app.i18n._('Default attendee status with status grant'),
                            name: 'status_with_grant',
                            value: 'NEEDS-ACTION'
                    }), new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Calendar',
                            keyFieldName: 'freebusyTypes',
                            fieldLabel: this.app.i18n._('Busy Type'),
                            name: 'busy_type'
                        }), {
                            xtype: 'numberfield',
                            fieldLabel: this.app.i18n._('Maximum number of attendee'),
                            allowNegative: false,
                            allowBlank: true,
                            name: 'max_number_of_people'
                        }], [{
                            xtype: 'tinerelationpickercombo',
                            fieldLabel: this.app.i18n._('Site'),
                            editDialog: this,
                            allowBlank: true,
                            app: 'Addressbook',
                            recordClass: Tine.Addressbook.Model.Contact,
                            relationType: 'SITE',
                            relationDegree: 'child',
                            ref: '../../../../../site',
                            listeners: {
                                scope: this,
                                select: this.onSiteSelect
                            }
                        }, {
                            xtype: 'tinerelationpickercombo',
                            fieldLabel: this.app.i18n._('Resource Location'),
                            editDialog: this,
                            allowBlank: true,
                            app: 'Addressbook',
                            recordClass: Tine.Addressbook.Model.Contact,
                            relationType: 'LOCATION',
                            relationDegree: 'child',
                            ref: '../../../../../location'
                        }, {
                            xtype: 'checkbox',
                            fieldLabel: this.app.i18n._('Suppress notification'),
                            name: 'suppress_notification'
                        }, {
                            xtype: 'colorfield',
                            allowBlank: true,
                            fieldLabel: this.app.i18n._('Color'),
                            name: 'color'
                        }],
                        [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Description'),
                        emptyText: this.app.i18n._('Enter description...'),
                        name: 'description',
                        xtype: 'textarea',
                        height: 200
                    }]] 
                }, {
                    // activities and tags
                    layout: 'ux.multiaccordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [new Tine.widgets.tags.TagPanel({
                            app: 'Calendar',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, {
                title: this.app.i18n._('Grants'),
                layout: 'fit',
                items: this.grantsGridPanel
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },

    onAfterRecordLoad: function () {
        Tine.Calendar.ResourceEditDialog.superclass.onAfterRecordLoad.apply(this, arguments);

        if (this.record.data && this.record.data.grants) {
            this.grantsGridPanel.getStore().loadData({results: this.record.data.grants});
        }
        this.getForm().items.each(function (f) {
            if (f.isFormField && f.requiredGrant !== undefined) {
                var hasRequiredGrant = _.get(this.record, this.recordClass.getMeta('grantsPath') + '.resourceEditGrant');

                f.setDisabled(!hasRequiredGrant);
            }
        }, this);
    },
    
    onRecordUpdate: function() {
        var grantsData = [];
        
        this.grantsGridPanel.getStore().each(function(r){
            grantsData.push(r.data);
        }, this);
        
        this.record.set('grants', '');
        this.record.set('grants', grantsData);
        
        Tine.Calendar.ResourceEditDialog.superclass.onRecordUpdate.apply(this, arguments);
    },
    
    onSiteSelect: function(field, newValue) {
        
        if (!this.location.getValue()) {
            this.location.setValue(newValue);
        }
    }
    
});

/**
 * Opens a new resource edit dialog window
 * 
 * @return {Ext.ux.Window}
 */
Tine.Calendar.ResourceEditDialog.openWindow = function (config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    var window = Tine.WindowFactory.getWindow({
        width: 1024,
        height: 430,
        name: Tine.Calendar.ResourceEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Calendar.ResourceEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

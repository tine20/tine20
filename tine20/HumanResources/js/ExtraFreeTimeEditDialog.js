/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.ExtraFreeTimeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>ExtraFreeTime Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * Create a new Tine.HumanResources.ExtraFreeTimeEditDialog
 */
Tine.HumanResources.ExtraFreeTimeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'ExtraFreeTimeEditWindow_',
    appName: 'HumanResources',
    recordClass: Tine.HumanResources.Model.ExtraFreeTime,
    recordProxy: Tine.HumanResources.freetimeBackend,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    showContainerSelector: false,
    mode: 'local',
    loadRecord: false,
    windowWidth: 400,
    windowHeight: 350,
    /**
     * show private Information (autoset due to rights)
     * @type 
     */
    showPrivateInformation: null,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: Ext.emptyFn,
    
    /**
     * inits the component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources')
        Tine.HumanResources.ExtraFreeTimeEditDialog.superclass.initComponent.call(this);
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
        
        if (Ext.isString(this.record)) {
            this.record = this.recordProxy.recordReader({responseText: this.record});
        }
        
        Tine.HumanResources.ExtraFreeTimeEditDialog.superclass.onRecordLoad.call(this);
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
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n._('Extra free time'),
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
                        autoHeight: true,
                        title: this.app.i18n._('Extra free time'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: {
                                xtype:'numberfield',
                                anchor: '100%',
                                labelSeparator: '',
                                allowBlank: false,
                                columnWidth: 1
                            },
                            items: [[
                                {name: 'days', fieldLabel: this.app.i18n._('Days')}],[
                                {   xtype: 'widget-keyfieldcombo',
                                    app: 'HumanResources',
                                    keyFieldName: 'extraFreetimeType',
                                    fieldLabel: this.app.i18n._('Type'),
                                    name: 'type'
                                }
                                ]
                                ]
                        }]
                    }]
                }, 
                {
                // activities and tags
                layout: 'accordion',
                animate: true,
                region: 'east',
                width: 210,
                split: true,
                collapsible: true,
                collapseMode: 'mini',
                header: false,
                margins: '0 5 0 5',
                border: true,
                items: [
                    new Ext.Panel({
                        title: this.app.i18n._('Description'),
                        iconCls: 'descriptionIcon',
                        layout: 'form',
                        labelAlign: 'top',
                        border: false,
                        items: [{
                            style: 'margin-top: -4px; border 0px;',
                            labelSeparator: '',
                            xtype: 'textarea',
                            name: 'description',
                            hideLabel: true,
                            grow: false,
                            preventScrollbars: false,
                            anchor: '100% 100%',
                            emptyText: this.app.i18n._('Enter description'),
                            requiredGrant: 'editGrant'
                        }]
                    }),
                    new Tine.widgets.activities.ActivitiesPanel({
                        app: 'HumanResources',
                        showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'HumanResources',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })
                ]
            }
                
                ]
            }, 
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
                }) 
            ]
        };
    },
    
    /**
     * updates the statusbox wrap
     * 
     * @param {Tine.Tinebase.widgets.keyfield.ComboBox} the calling combo
     * @param {Tine.Tinebase.data.Record} the selected record
     * @param {Integer} the index of the selected value of the typecombo store
     */
    updateStatusBox: function(typeCombo, keyfieldRecord, index) {
        this.statusBoxWrap.layout.setActiveItem(index);
    },
    
    /**
     * initializes the status box
     */
    initStatusBox: function() {
        var isSickness = this.fixedFields.get('type') == 'SICKNESS';
        this.updateStatusBox(null, null, isSickness ? 0 : 1);
        var fieldName = isSickness ? 'sicknessStatus' : 'vacationStatus';
        this.getForm().findField(fieldName).setValue(this.record.get('status'));
    }
});
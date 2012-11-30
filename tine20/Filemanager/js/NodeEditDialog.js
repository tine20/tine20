/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

/**
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.NodeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Node Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Filemanager.NodeEditDialog
 */
Tine.Filemanager.NodeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'NodeEditWindow_',
    appName: 'Filemanager',
    recordClass: Tine.Filemanager.Model.Node,
    recordProxy: Tine.Filemanager.fileRecordBackend,
    tbarItems: null,
    evalGrants: true,
    showContainerSelector: false,
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Filemanager');
        this.downloadAction = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: false,
            actionType: 'download',
            text: this.app.i18n._('Save locally'),
            handler: this.onDownload,
            iconCls: 'action_filemanager_save_all',
            disabled: false,
            scope: this
        });
        
        this.tbarItems = [{xtype: 'widget-activitiesaddbutton'}, this.downloadAction];
        
        Tine.Filemanager.NodeEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * download file
     */
    onDownload: function() {
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Filemanager.downloadFile',
                requestType: 'HTTP',
                path: '',
                id: this.record.get('id')
            }
        }).start();
    },
    
    /**
     * returns dialog
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        var formFieldDefaults = {
            xtype:'textfield',
            anchor: '100%',
            labelSeparator: '',
            columnWidth: .5,
            readOnly: true,
            disabled: true
        };
        
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
                title: this.app.i18n._('Node'),
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
                        title: this.app.i18n._('Node'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: formFieldDefaults,
                            items: [[{
                                    fieldLabel: this.app.i18n._('Name'),
                                    name: 'name',
                                    allowBlank: false,
                                    readOnly: false,
                                    columnWidth: .75,
                                    disabled: false
                                }, {
                                    fieldLabel: this.app.i18n._('Type'),
                                    name: 'contenttype',
                                    columnWidth: .25
                                }],[
                                Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                    userOnly: true,
                                    useAccountRecord: true,
                                    blurOnSelect: true,
                                    fieldLabel: this.app.i18n._('Created By'),
                                    name: 'created_by'
                                }), {
                                    fieldLabel: this.app.i18n._('Creation Time'),
                                    name: 'creation_time',
                                    xtype: 'datefield'
                                }
                                ],[
                                Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                    userOnly: true,
                                    useAccountRecord: true,
                                    blurOnSelect: true,
                                    fieldLabel: this.app.i18n._('Modified By'),
                                    name: 'last_modified_by'
                                }), {
                                    fieldLabel: this.app.i18n._('Last Modified'),
                                    name: 'last_modified_time',
                                    xtype: 'datefield'
                                }
                                ]]
                        }]
                    }
                    
                    ]
                }, {
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
                            app: 'Filemanager',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'Filemanager',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, 
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
                })
            ]
        };
    }
});

/**
 * Filemanager Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Filemanager.NodeEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 570,
        name: Tine.Filemanager.NodeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Filemanager.NodeEditDialog',
        contentPanelConstructorConfig: config
    });
    
    return window;
};

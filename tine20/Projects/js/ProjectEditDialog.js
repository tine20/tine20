/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Projects');

/**
 * @namespace   Tine.Projects
 * @class       Tine.Projects.ProjectEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Project Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Projects.ProjectEditDialog
 */
Tine.Projects.ProjectEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'ProjectEditWindow_',
    appName: 'Projects',
    recordClass: Tine.Projects.Model.Project,
    recordProxy: Tine.Projects.recordBackend,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: true,
    showContainerSelector: true,
    hideRelationsPanel: true,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * executed after record got updated from proxy
     * @private
     */
    onAfterRecordLoad: function() {
        Tine.Projects.ProjectEditDialog.superclass.onAfterRecordLoad.call(this);
        this.contactLinkPanel.onRecordLoad(this.record);
    },
    
    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * 
     * @private
     */
    onRecordUpdate: function() {
        Tine.Projects.ProjectEditDialog.superclass.onRecordUpdate.call(this);
        this.record.set('relations', this.contactLinkPanel.getData());
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
        this.contactLinkPanel = new Tine.widgets.grid.LinkGridPanel({
            app: this.app,
            searchRecordClass: Tine.Addressbook.Model.Contact,
            title: this.app.i18n._('Attendee'),
            typeColumnHeader: this.app.i18n._('Role'),
            searchComboClass: Tine.Addressbook.SearchCombo,
            searchComboConfig: {
                relationDefaults: {
                    type: this.app.getRegistry().get('config')['projectAttendeeRole'].definition['default'],
                    own_model: 'Projects_Model_Project',
                    related_model: 'Addressbook_Model_Contact',
                    own_degree: 'sibling',
                    related_backend: 'Sql'
                }
            },
            relationTypesKeyfieldName: 'projectAttendeeRole'
        });
        
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
                title: this.app.i18n._('Project'),
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
                        title: this.app.i18n._('Project'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: {
                                xtype:'textfield',
                                anchor: '100%',
                                labelSeparator: '',
                                columnWidth: .333
                            },
                            items: [[{
                                    columnWidth: 1,
                                    fieldLabel: this.app.i18n._('Title'),
                                    name: 'title',
                                    allowBlank: false
                                }], [{
                                    columnWidth: .5,
                                    fieldLabel: this.app.i18n._('Number'),
                                    name: 'number'
                                }, new Tine.Tinebase.widgets.keyfield.ComboBox({
                                    columnWidth: .5,
                                    app: 'Projects',
                                    keyFieldName: 'projectStatus',
                                    fieldLabel: this.app.i18n._('Status'),
                                    name: 'status'
                                })]
                            ]
                        }]
                    }, {
                        xtype: 'tabpanel',
                        deferredRender: false,
                        activeTab: 0,
                        border: false,
                        height: 250,
                        form: true,
                        items: [
                            this.contactLinkPanel
                        ]
                    }]
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
                            app: 'Projects',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'Projects',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});

/**
 * Projects Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Projects.ProjectEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Projects.ProjectEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Projects.ProjectEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

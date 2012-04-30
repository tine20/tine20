/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.EmployeeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Employee Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.EmployeeEditDialog
 */
Tine.HumanResources.EmployeeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'EmployeeEditWindow_',
    appName: 'HumanResources',
    recordClass: Tine.HumanResources.Model.Employee,
    recordProxy: Tine.HumanResources.recordBackend,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: true,
    showContainerSelector: true,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
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
        // add selections to record

        Tine.HumanResources.EmployeeEditDialog.superclass.onRecordLoad.call(this);
        
        if (this.record && this.selectedRecords && this.selectedRecords.length > 0) {
            var oldRelations = this.record.get('relations');
            
            var relations = oldRelations ? oldRelations : [];

            Ext.each(this.selectedRecords, function(contact) {
                var rec = new Tine.Addressbook.Model.Contact(contact, contact.id);
                var rel = new Tine.Tinebase.Model.Relation({
                    own_degree: 'sibling',
                    own_id: null,
                    own_model: 'HumanResources_Model_Employee',
                    related_backend: 'Sql',
                    related_id: contact.id,
                    related_model: 'Addressbook_Model_Contact',
                    related_record: rec.data,
                    type: this.attendeeRole ? this.attendeeRole : 'COWORKER'
                });
            
                relations.push(rel.data);
            
            },this);
            
            this.record.set('relations',relations);
            this.selectedRecords = [];
        }
    },
    
    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * 
     * @private
     */
    onRecordUpdate: function() {
        Tine.HumanResources.EmployeeEditDialog.superclass.onRecordUpdate.call(this);
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
//        this.contactLinkPanel = new Tine.widgets.grid.LinkGridPanel({
//            app: this.app,
//            searchRecordClass: Tine.Addressbook.Model.Contact,
//            title: this.app.i18n._('Attendee'),
//            typeColumnHeader: this.app.i18n._('Role'),
//            searchComboClass: Tine.Addressbook.SearchCombo,
//            searchComboConfig: {
//                relationDefaults: {
//                    type: this.app.getRegistry().get('config')['projectAttendeeRole'].definition['default'],
//                    own_model: 'HumanResources_Model_Employee',
//                    related_model: 'Addressbook_Model_Contact',
//                    own_degree: 'sibling',
//                    related_backend: 'Sql'
//                }
//            },
//            relationTypesKeyfieldName: 'projectAttendeeRole'
//        });
        
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
                title: this.app.i18n._('Employee'),
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
                        title: this.app.i18n._('Employee'),
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
                                }]
                            ]
                        }]
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
 * HumanResources Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.HumanResources.EmployeeEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.HumanResources.EmployeeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.HumanResources.EmployeeEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Courses');

Tine.Courses.CourseEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @private
     */
    windowNamePrefix: 'CourseEditWindow_',
    appName: 'Courses',
    recordClass: Tine.Courses.Model.Course,
    recordProxy: Tine.Courses.coursesBackend,
    loadRecord: false,
    evalGrants: false,
    
    /**
     * initComponent
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Courses');
        
        this.action_import = new Ext.Action({
            iconCls: 'action_import',
            disabled: true,
            text: this.app.i18n._('Import course members'),
            plugins: [new Ext.ux.file.BrowsePlugin({})],
            scope: this,
            handler: this.onFileSelect
        });
        
        this.action_addNewMember = new Ext.Action({
            iconCls: 'action_add',
            disabled: true,
            text: this.app.i18n._('Add new member'),
            scope: this,
            handler: this.onAddNewMember
        });
        
        this.tbarItems = [
            this.action_import,
            this.action_addNewMember,
            {xtype: 'widget-activitiesaddbutton'}
            
        ];
        Tine.Courses.CourseEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * onFileSelect
     * 
     * @param {} fileSelector
     * 
     * TODO wrap this into a uploadAction widget
     */
    onFileSelect: function(fileSelector) {
        
        var files = fileSelector.getFileList();
        this.loadMask.show();
        var upload = new Ext.ux.file.Upload({
            file: files[0],
            fileSelector: fileSelector
        });
        
        upload.on('uploadcomplete', function(uploader, record){
            var tempFile = record.get('tempFile');
            Ext.Ajax.request({
                scope: this,
                timeout: 1200000, // 20 minutes
                params: {
                    method: 'Courses.importMembers',
                    tempFileId: tempFile.id,
                    groupId: this.record.data.group_id,
                    courseId: this.record.data.id
                },
                success: this.onMembersImport,
                failure: function() {}
            });
            
        }, this);
        
        upload.on('uploadfailure', function(uploader, record){
            
        }, this);
        
        this.loadMask.show();
        var uploadKey = Tine.Tinebase.uploadManager.queueUpload(upload);
        var fileRecord = Tine.Tinebase.uploadManager.upload(uploadKey);
    },
    
    /**
     * onAddNewMember
     */
    onAddNewMember: function(fileSelector) {
        Tine.Courses.AddMemberDialog.openWindow({
            courseData: this.record.data,
            app: this.app,
            listeners: {
                scope: this,
                'update': this.onMembersImport
            }
        });
    },
    
    /**
     * update members grid
     */
    onMembersImport: function(response) {
        Tine.log.debug('Tine.Courses.CourseEditDialog::onMembersImport');
        Tine.log.debug(response);
        
        var members = (response.responseText) ? Ext.util.JSON.decode(response.responseText) : response;
        if (members.results.length > 0) {
            this.membersStore.loadData({results: members.results});
        }
        this.loadMask.hide();
    },
    
    /**
     * overwrite update toolbars function (we don't have record members yet)
     */
    updateToolbars: function() {
    },
    
    /**
     * onRecordLoad
     */
    onRecordLoad: function() {
        var members = this.record.get('members') || [];
        if (members.length > 0) {
            this.membersStore.loadData({results: members});
        }
        
        // only activate import and ok buttons if editing existing course / user has the appropriate right
        var disabled = ! this.record.get('id') 
            || ! Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts')
            || ! Tine.Tinebase.common.hasRight('add_new_user', 'Courses');
        this.action_import.setDisabled(disabled);
        this.action_addNewMember.setDisabled(disabled);
        this.action_saveAndClose.setDisabled(!Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts'));
        
        Tine.Courses.CourseEditDialog.superclass.onRecordLoad.call(this);
    },
    
    /**
     * onRecordUpdate
     */
    onRecordUpdate: function() {
        Tine.Courses.CourseEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('members', '');
        
        var members = [];
        this.membersStore.each(function(_record){
            members.push(_record.data.id);
        });
        
        this.record.set('members', members);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        var internetAccessDeactivated = (! Tine.Courses.registry.get('config').internet_group || 
            Tine.Courses.registry.get('config').internet_group.value === null);
        
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
                title: this.app.i18n.ngettext('Course', 'Courses', 1),
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
                        columnWidth: 1
                    },
                    items: [[{
                        fieldLabel: this.app.i18n._('Course Name'), 
                        name:'name',
                        allowBlank: false
                    }, {
                        xtype:'reccombo',
                        name: 'type',
                        allowBlank: false,
                        fieldLabel: this.app.i18n._('Course / School Type'),
                        displayField: 'name',
                        store: new Ext.data.Store({
                           fields: Tine.Courses.Model.CourseType,
                           proxy:  Tine.Courses.courseTypeBackend,
                           reader: Tine.Courses.courseTypeBackend.getReader(),
                           remoteSort: true,
                           sortInfo: {field: 'name', dir: 'ASC'}
                        })
                    }, {
                        name: 'description',
                        fieldLabel: this.app.i18n._('Description'),
                        grow: false,
                        preventScrollbars:false,
                        xtype: 'textarea',
                        height: 60
                    }, new Tine.Tinebase.widgets.keyfield.ComboBox({
                        fieldLabel: this.app.i18n._('Internet Access'),
                        app: 'Courses',
                        keyFieldName: 'internetAccess',
                        value: 'OFF',
                        name: 'internet',
                        hideLabel: internetAccessDeactivated,
                        hidden: internetAccessDeactivated
                    })
//                    {
//                        hideLabel: true,
//                        boxLabel: this.app.i18n._('Fileserver Access'),
//                        name: 'fileserver',
//                        xtype: 'checkbox',
//                        columnWidth: 0.5
//                    }
                    ]]
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
                    new Tine.widgets.activities.ActivitiesPanel({
                        app: 'Courses',
                        showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'Courses',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })]
                }]
            }, {
                title: this.app.i18n._('Members'),
                layout: 'fit',
                items: [this.getMembersGrid()]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
    
    /**
     * get the members grid panel
     * 
     * @return {GridPanel} membersGrid
     */
    getMembersGrid: function() {
        if (! this.membersGrid) {
            this.membersStore =  new Ext.data.JsonStore({
                root: 'results',
                totalProperty: 'totalcount',
                id: 'id',
                fields: Tine.Tinebase.Model.Account
            });
            
            var columns = [{
                id: 'data',
                header: this.app.i18n._("Login"),
                width: 200,
                dataIndex: 'data',
                renderer: function(value) {
                    return (value.account_id) ? _('unknown') : value;
                }
            }];
            
            var action_resetPwd = new Ext.Action({
                text: _('Reset Password'),
                scope: this,
                handler: function(_button, _event) {
                    this.loadMask.show();
                    var accountObject = this.membersGrid.getSelectionModel().getSelected().data;
                    Ext.Ajax.request( {
                        params : {
                            method    : 'Courses.resetPassword',
                            account   : accountObject.id,
                            password  : this.record.data.name,
                            mustChange: true
                        },
                        scope: this,
                        success: function() {
                            this.loadMask.hide();
                        }
                    });
                },
                iconCls: 'action_password'
            });
            
            this.membersGrid = new Tine.widgets.account.PickerGridPanel({
                store: this.membersStore,
                contextMenuItems: [action_resetPwd],
                configColumns: columns,
                enableTbar: Tine.Tinebase.common.hasRight('add_existing_user', 'Courses')
            });
        }
        return this.membersGrid;
    }
});

/**
 * Courses Edit Popup
 */
Tine.Courses.CourseEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Courses.CourseEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Courses.CourseEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

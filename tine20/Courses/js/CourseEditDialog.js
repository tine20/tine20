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
    evalGrants: false,
    displayNotes: true,
    additionalGroups: [],

    /**
     * initComponent
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Courses');
        this.additionalGroups = Tine.Courses.registry.get('additionalGroupMemberships');
        this.initTbarActions();

        Tine.Courses.CourseEditDialog.superclass.initComponent.call(this);
    },

    initTbarActions: function() {
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
            this.action_addNewMember
        ];
    },

    /**
     * onFileSelect
     * 
     * @param {} fileSelector
     * 
     * TODO wrap this into a uploadAction widget
     */
    onFileSelect: function(fileSelector) {
        const files = fileSelector.getFileList();
        this.loadMask.show();
        
        let upload = new Ext.ux.file.Upload({
            file: files[0],
            fileSelector: fileSelector,
            id: Tine.Tinebase.uploadManager.generateUploadId()
        });
        
        upload.on('uploadcomplete', function(uploader, record){
            const tempFile = record.get('tempFile');
            Ext.Ajax.request({
                scope: this,
                timeout: 1200000, // 20 minutes
                params: {
                    method: 'Courses.importMembers',
                    tempFileId: tempFile.id,
                    groupId: this.record.data.group_id,
                    courseId: this.record.data.id
                },
                success: () => {
                    this.loadRemoteRecord();
                },
                failure: function() {}
            });
            
        }, this);
        
        upload.on('uploadfailure', function(uploader, record){
            
        }, this);
        
        this.loadMask.show();
        upload.upload();
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
            this.loadMembersIntoStore(members.results);
        }
        this.hideLoadMask();
    },
    
    /**
     * overwrite update toolbars function (we don't have record members yet)
     */
    updateToolbars: function() {
    },

    /**
     * load members into members store / handle additional groups
     *
     * @param Array members
     */
    loadMembersIntoStore: function(members) {
        if (members.length > 0) {
            _.each(members, function(member) {
                _.each(member.additionalGroups, function(groupId) {
                    member['group_' + groupId] = true;
                });
            });
            this.membersStore.loadData({results: members});
        }
    },

    /**
     * onRecordLoad
     */
    onRecordLoad: function() {
        let members = this.record.get('members') || [];
        this.loadMembersIntoStore(members);

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

        let members = [];
        let me = this;
        this.membersStore.each(function(_record){
            let additionalGroupMemberships = [];
            _.each(me.additionalGroups, function(group) {
                if (_record.get('group_' + group.id)) {
                    additionalGroupMemberships.push(group.id);
                }
            });
            _record.set('additionalGroups', additionalGroupMemberships);
            members.push(_record.data);
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
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
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
                           sortInfo: {field: 'name', direction: 'ASC'}
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
                        value: 'FILTERED',
                        name: 'internet',
                        hideLabel: internetAccessDeactivated,
                        hidden: internetAccessDeactivated
                    })
                    ]]
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
                    items: [
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
            let me = this;
            let membersFields = [
                {name: 'id'},
                {name: 'type'},
                {name: 'name'},
                {name: 'additionalGroups'},
                {name: 'data'}
            ];

            let columns = [{
                id: 'data',
                header: this.app.i18n._("Login"),
                width: 200,
                dataIndex: 'data',
                renderer: function(value) {
                    return (value.account_id) ? i18n._('unknown') : value;
                }
            }];

            // add configured groups here (as checkbox column) and additionalGroupMemberships fields to store model
            _.each(this.additionalGroups, function(group) {
                let fieldName = 'group_' + group.id;
                columns.push(new Ext.ux.grid.CheckColumn({
                    id: fieldName,
                    header: group.name,
                    width: 100,
                    dataIndex: fieldName,
                    readOnly: ! Tine.Tinebase.common.hasRight('set_additional_memberships', 'Courses', '')
                }));
                membersFields.push(fieldName);
            });

            this.membersStore =  new Ext.data.JsonStore({
                root: 'results',
                totalProperty: 'totalcount',
                id: 'id',
                fields: membersFields
            });

            const action_resetPwd = new Ext.Action({
                text: this.app.i18n._('Reset Password'),
                scope: this,
                handler: this.onResetPassword,
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
    },
    
    onResetPassword() {
        const passwordDialog = new Tine.Tinebase.widgets.dialog.PasswordDialog({
            allowEmptyPassword: false,
            locked: false,
            questionText: i18n._('Please enter the new Password.'),
            policyConfig: Tine.Tinebase.configManager.get('userPwPolicy')
        });
        passwordDialog.openWindow();

        passwordDialog.on('apply', function (password) {
            this.loadMask.show();
            const accountObject = this.membersGrid.getSelectionModel().getSelected().data;
            Tine.Courses.resetPassword(accountObject.id, password, true).finally(() => {
                this.hideLoadMask();
            });
        }, this);
    }
});

/**
 * Courses Edit Popup
 */
Tine.Courses.CourseEditDialog.openWindow = function (config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Courses.CourseEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Courses.CourseEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:TimeaccountEditDialog.js 7169 2009-03-05 10:37:38Z p.schuele@metaways.de $
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
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Courses');
        this.tbarItems = [
            {xtype: 'widget-activitiesaddbutton'},
            new Ext.ux.BrowseButton({
            	id: 'ImportButton',
            	iconCls: 'action_import',
            	disabled: true,
                text: this.app.i18n._('Import course members'),
                scope: this,
                handler: this.onFileSelect
            })
        ];
        Tine.Courses.CourseEditDialog.superclass.initComponent.call(this);
    },
    
    // todo: wrap this into a uploadAction widget
    onFileSelect: function(fileSelector) {
        this.loadMask.show();
        var uploader = new Ext.ux.file.Uploader({
            fileSelector: fileSelector
        });
        
        uploader.on('uploadcomplete', function(uploader, record){
        	var tempFile = record.get('tempFile');
            Ext.Ajax.request({
            	scope: this,
                timeout: 120000, 
                params: {
                    method: 'Courses.importMembers',
                    tempFileId: tempFile.id,
                    groupId: this.record.data.group_id,
                    courseId: this.record.data.id
                },
                success: this.onMembersImport,
                failure: function() {}
            });
        	
            //this.loadMask.hide();
        	//console.log(record.get('tempFile'));
            
        }, this);
        
        uploader.on('uploadfailure', function(uploader, record){
            
        }, this);
        
        this.loadMask.show();
        uploader.upload();
    },
    
    /**
     * update members grid
     */
    onMembersImport: function(response) {
        var members = Ext.util.JSON.decode(response.responseText);
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
    
    onRecordLoad: function() {
        var members = this.record.get('members') || [];
        if (members.length > 0) {
            this.membersStore.loadData({results: members});
        }
        
        // only activate import and ok buttons if editing existing course / user has the appropriate right
        var disabled = !this.record.get('id') || !Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts');
        Ext.getCmp('ImportButton').setDisabled(disabled);
        this.action_saveAndClose.setDisabled(!Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts'));
        
       	Tine.Courses.CourseEditDialog.superclass.onRecordLoad.call(this);        
    },
    
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
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
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
                    }, /*{
                        fieldLabel: this.app.i18n._('Course / School Type'), 
                        name:'type',
                        allowBlank: false
                    }*/ {
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
                        height: 60
                    }, {
                        hideLabel: true,
                        boxLabel: this.app.i18n._('Internet Access'),
                        name: 'internet',
                        xtype: 'checkbox',
                        columnWidth: 0.5
                    }, {
                        hideLabel: true,
                        boxLabel: this.app.i18n._('Fileserver Access'),
                        name: 'fileserver',
                        xtype: 'checkbox',
                        columnWidth: 0.5
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
     * @return {}
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
                width: 100,
                dataIndex: 'data'
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
                    /*
                    Ext.MessageBox.prompt(this.app.i18n._('Set new password'), this.app.i18n._('Please enter the new password:'), function(_button, _text) {
                    });
                    */
                },
                iconCls: 'action_password'
            });
            
            this.membersGrid = new Tine.widgets.account.PickerGridPanel({
                store: this.membersStore,
                contextMenuItems: [action_resetPwd],
                configColumns: columns
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
